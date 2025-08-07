<?php

namespace App\Services;

use App\Models\Ingredient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class NutritionService
{
    /**
     * Calculates the total macros (calories, protein, fat, carbs) for a given set of ingredients.
     * This method now implements the hybrid approach:
     * - Tries to fetch ingredient data from the local database first.
     * - If not found locally, searches the USDA API, stores the best match, and then uses it.
     *
     * @param array $ingredients An array of associative arrays, where each inner array represents an ingredient
     * with 'name' and 'grams' keys.
     * Example: [['name' => 'chicken breast', 'grams' => 150], ['name' => 'brown rice cooked', 'grams' => 200]]
     * @return array An associative array of total macros.
     */
    public function calculateTotalMacros(array $ingredients): array
    {
        $totalCalories = 0;
        $totalProtein = 0;
        $totalFat = 0;
        $totalCarbs = 0;

        foreach ($ingredients as $item) {
            $userProvidedName = $item['name'];
            $grams = $item['grams'] ?? 0;

            if ($grams <= 0) {
                \Log::warning("Skipping ingredient '{$userProvidedName}' with zero or negative grams.");
                continue;
            }

            // Step 1: Try to find the ingredient in our local database first
            // We use display_name or a normalized version of name for the initial search
            // as user input might be display_name oriented.
            // For canonical search, we'd use the 'name' field, but the user input might match 'display_name' better.
            $localIngredient = Ingredient::where('name', Str::lower($userProvidedName))
                                         ->orWhere('display_name', 'LIKE', '%' . $userProvidedName . '%')
                                         ->first();

            $ingredient = null;

            if ($localIngredient) {
                $ingredient = $localIngredient;
                \Log::info("Found ingredient '{$userProvidedName}' locally (ID: {$ingredient->id}).");
            } else {
                // Step 2: If not found locally, fetch from USDA API and store it
                $this->info("Ingredient '{$userProvidedName}' not found locally. Attempting to fetch from USDA...");
                $ingredient = $this->fetchAndStoreIngredientFromUsda($userProvidedName);

                if (!$ingredient) {
                    \Log::error("Failed to find or import ingredient: '{$userProvidedName}'. Skipping for macro calculation.");
                    continue; // Skip this ingredient if it couldn't be found/imported
                }
            }

            // Calculate macros for the given grams using the retrieved (local or newly imported) ingredient
            $totalCalories += ($ingredient->calories_per_100g / 100) * $grams;
            $totalProtein += ($ingredient->protein_per_100g / 100) * $grams;
            $totalFat += ($ingredient->fat_per_100g / 100) * $grams;
            $totalCarbs += ($ingredient->carbs_per_100g / 100) * $grams;
        }

        return [
            'calories' => round($totalCalories),
            'protein' => round($totalProtein),
            'fat' => round($totalFat),
            'carbs' => round($totalCarbs),
        ];
    }

    /**
     * Retrieves an ingredient's macros for a given amount in grams.
     * This helper function can now also use the hybrid logic if needed.
     *
     * @param string $ingredientName The canonical name of the ingredient (e.g., "chicken breast").
     * @param float $grams The amount in grams.
     * @return array|null An associative array of macros for the specified amount, or null if not found.
     */
    public function getIngredientMacros(string $ingredientName, float $grams): ?array
    {
        if ($grams <= 0) {
            return null;
        }

        // Try local first
        $localIngredient = Ingredient::where('name', Str::lower($ingredientName))
                                     ->orWhere('display_name', 'LIKE', '%' . $ingredientName . '%')
                                     ->first();

        $ingredient = $localIngredient ?? $this->fetchAndStoreIngredientFromUsda($ingredientName);

        if ($ingredient) {
            return [
                'name' => $ingredient->name,
                'display_name' => $ingredient->display_name,
                'grams' => round($grams),
                'calories' => round(($ingredient->calories_per_100g / 100) * $grams),
                'protein' => round(($ingredient->protein_per_100g / 100) * $grams),
                'fat' => round(($ingredient->fat_per_100g / 100) * $grams),
                'carbs' => round(($ingredient->carbs_per_100g / 100) * $grams),
            ];
        }

        return null;
    }

    /**
     * Attempts to fetch an ingredient from USDA FoodData Central,
     * stores it in the local database, and returns the Ingredient model.
     *
     * @param string $query The ingredient name to search for.
     * @return Ingredient|null The created Ingredient model, or null if not found/error.
     */
    private function fetchAndStoreIngredientFromUsda(string $query): ?Ingredient
    {
        $apiKey = config('services.usda.key');
        if (empty($apiKey)) {
            \Log::error('USDA FoodData Central API key not set in config/services.php. Cannot fetch ingredient from API.');
            return null;
        }

        $url = "https://api.nal.usda.gov/fdc/v1/foods/search";

        try {
            $response = Http::timeout(10)->get($url, [ // Add a timeout for API calls
                'api_key' => $apiKey,
                'query' => $query,
                'dataType' => ['Foundation Foods', 'SR Legacy', 'Branded Foods'],
                'pageSize' => 1, // Only need the top result for a single lookup
            ]);

            $response->throw(); // Throws an exception for 4xx/5xx responses

            $data = $response->json();
            $foods = $data['foods'] ?? [];

            if (empty($foods)) {
                \Log::info("USDA API: No food found for query '{$query}'.");
                return null;
            }

            // We take the first result as the "best match".
            // In a more complex app, you might have a more sophisticated selection logic
            // or even present options to the user.
            $food = $foods[0];

            $fdcId = $food['fdcId'] ?? null;
            $description = $food['description'] ?? $query; // Fallback to query if description is missing

            if (empty($fdcId)) {
                \Log::warning("USDA API: Food found for '{$query}' has no fdcId. Skipping storage.");
                return null;
            }

            $nutrients = collect($food['foodNutrients'] ?? []);

            $calories = $nutrients->firstWhere('nutrientName', 'Energy')['value'] ?? 0;
            $protein = $nutrients->firstWhere('nutrientName', 'Protein')['value'] ?? 0;
            $fat = $nutrients->firstWhere('nutrientName', 'Total lipid (fat)')['value'] ?? 0;
            $carbs = $nutrients->firstWhere('nutrientName', 'Carbohydrate, by difference')['value'] ?? 0;

            // --- Dynamic Inference Logic (as discussed) ---
            $ingredientType = 'other';
            if ($protein > 15 && $carbs < 10 && $fat < 10) {
                $ingredientType = 'protein_source';
            } elseif ($carbs > 15 && $protein < 10 && $fat < 10) {
                $ingredientType = 'carb_source';
            } elseif ($fat > 15 && $protein < 10 && $carbs < 10) {
                $ingredientType = 'fat_source';
            } elseif ($calories < 50 && $carbs < 10) {
                $ingredientType = 'vegetable';
            } elseif ($calories < 100 && $carbs > 10 && $protein < 5) {
                $ingredientType = 'fruit';
            }

            // Keyword-based refinements
            if (Str::contains(Str::lower($description), ['milk', 'yogurt', 'cheese', 'cream'])) {
                $ingredientType = 'dairy';
            } elseif (Str::contains(Str::lower($description), ['spice', 'herb', 'salt', 'pepper', 'garlic powder', 'onion powder'])) {
                $ingredientType = 'seasoning_herb';
            } elseif (Str::contains(Str::lower($description), ['oil', 'butter', 'ghee'])) {
                $ingredientType = 'fat_source';
            } elseif (Str::contains(Str::lower($description), ['flour', 'bread', 'pasta', 'rice', 'oat', 'quinoa', 'potato', 'sweet potato'])) {
                $ingredientType = 'carb_source';
            } elseif (Str::contains(Str::lower($description), ['bean', 'lentil', 'chickpea', 'tofu', 'tempeh', 'chicken', 'beef', 'pork', 'fish', 'shrimp', 'egg'])) {
                $ingredientType = 'protein_source';
            }


            // As discussed, setting these to empty arrays for now
            $allergens = json_encode([]);
            $commonUnits = json_encode([]);
            $cuisineAffinities = json_encode([]);

            // is_supplementary logic: true for most macro-contributing ingredients, false for seasonings/water.
            $isSupplementary = true;
            if (in_array($ingredientType, ['seasoning_herb', 'liquid']) || $calories < 5) {
                $isSupplementary = false;
            }

            // Store the ingredient in our local database
            $ingredient = Ingredient::updateOrCreate(
                ['fdc_id' => $fdcId], // Use FDC ID as the unique key for API-sourced ingredients
                [
                    'name' => Str::lower($description), // Canonical name for internal use
                    'display_name' => $description, // User-friendly name
                    'source_api' => 'USDA FoodData Central',
                    'calories_per_100g' => round($calories, 2),
                    'protein_per_100g' => round($protein, 2),
                    'fat_per_100g' => round($fat, 2),
                    'carbs_per_100g' => round($carbs, 2),
                    'common_units' => $commonUnits,
                    'cuisine_affinities' => $cuisineAffinities,
                    'allergens' => $allergens,
                    'ingredient_type' => $ingredientType,
                    'is_supplementary' => $isSupplementary,
                ]
            );

            \Log::info("Successfully imported '{$ingredient->display_name}' (ID: {$ingredient->id}, FDC ID: {$fdcId}) from USDA.");
            return $ingredient;

        } catch (Throwable $e) { // Catch any exception thrown during HTTP request or processing
            \Log::error("Failed to fetch or store ingredient '{$query}' from USDA API: " . $e->getMessage());
            // You might want to distinguish between API failures and data processing errors here
            return null;
        }
    }

  /**
     * Optimizes a list of ingredients to meet specific macro targets by adjusting quantities
     * and potentially swapping supplementary ingredients.
     *
     * @param array $initialIngredients An array of associative arrays, e.g., [['name' => 'chicken breast', 'grams' => 150]]
     * @param array $targetMacros Associative array with 'calories', 'protein', 'fat', 'carbs' (e.g., ['calories' => 500, 'protein' => 40, 'fat' => 20, 'carbs' => 30])
     * @param float $tolerancePercentage The percentage deviation allowed from target macros (e.g., 0.10 for +/- 10%)
     * @param array $options Optional settings:
     * - 'fixed_ingredients_names': array of ingredient names whose quantities should NOT be adjusted.
     * - 'allow_swaps': bool (whether to allow adding/swapping supplementary ingredients)
     * - 'max_adjust_percentage': float (max percentage an ingredient's quantity can be adjusted from its initial amount, e.g., 0.5 for +/- 50%)
     * - 'min_ingredient_grams': float (minimum grams an ingredient can be reduced to, e.g., 5 for 5g)
     * @return array Returns an array containing 'optimized_ingredients' and 'final_macros', or null if optimization fails.
     */
    public function optimizeIngredientsForMacros(
        array $initialIngredients,
        array $targetMacros,
        float $tolerancePercentage = 0.10, // Default to +/- 10% tolerance
        array $options = []
    ): ?array {
        $fixedIngredientNames = collect($options['fixed_ingredients_names'] ?? [])->map(fn($name) => Str::lower($name));
        $allowSwaps = $options['allow_swaps'] ?? true;
        $maxAdjustPercentage = $options['max_adjust_percentage'] ?? 0.5; // Max +/- 50% adjustment from initial
        $minIngredientGrams = $options['min_ingredient_grams'] ?? 5; // Minimum grams an ingredient can be reduced to

        $optimizedIngredients = collect();

        // 1. Load initial ingredients and calculate their baseline macros.
        foreach ($initialIngredients as $item) {
            $userProvidedName = $item['name'];
            $grams = $item['grams'] ?? 0;

            if ($grams <= 0) {
                $this->warning("Skipping initial ingredient '{$userProvidedName}' with zero or negative grams.");
                continue;
            }

            $ingredientModel = $this->fetchAndStoreIngredientFromUsda($userProvidedName);
            if (!$ingredientModel) {
                $this->error("Optimization: Could not load data for initial ingredient '{$userProvidedName}'. Skipping it.");
                continue;
            }

            $optimizedIngredients->push([
                'model' => $ingredientModel,
                'grams' => $grams,
                'original_grams' => $grams, // Store original grams for max_adjust_percentage
                'is_fixed' => $fixedIngredientNames->contains($ingredientModel->name),
            ]);
        }

        if ($optimizedIngredients->isEmpty()) {
            $this->warning("No valid initial ingredients provided for optimization.");
            return null;
        }

        $currentMacros = $this->calculateCurrentMealMacros($optimizedIngredients);

        $this->info("Initial Macros: " . json_encode($currentMacros));
        $this->info("Target Macros: " . json_encode($targetMacros));

        // 2. Iterative Adjustment Loop
        $maxIterations = 50;
        $iteration = 0;
        $stuckCounter = 0; // To detect if we're oscillating

        while (!$this->checkMacroTargets($currentMacros, $targetMacros, $tolerancePercentage) && $iteration < $maxIterations) {
            $iteration++;
            $this->info("--- Optimization Iteration {$iteration} ---");
            $this->info("Current Macros: " . json_encode($currentMacros));

            $somethingAdjusted = false;
            $initialMacrosThisIteration = $currentMacros; // Capture macros at start of iteration for stuck check

            // Step 2.1: Adjust quantities of *existing* ingredients
            // We iterate through a copy and then update the original collection
            $tempOptimizedIngredients = $optimizedIngredients->map(function ($item) use (
                $currentMacros,
                $targetMacros,
                $maxAdjustPercentage,
                $minIngredientGrams,
                $tolerancePercentage,
                &$somethingAdjusted
            ) {
                if ($item['is_fixed']) {
                    return $item; // Return fixed ingredients as is
                }

                $model = $item['model'];
                $originalGrams = $item['original_grams'];
                $currentGrams = $item['grams'];
                $newGrams = $currentGrams;

                // Determine overall macro needs (positive if under target, negative if over target)
                $macroNeeds = [
                    'protein' => $targetMacros['protein'] - $currentMacros['protein'],
                    'fat' => $targetMacros['fat'] - $currentMacros['fat'],
                    'carbs' => $targetMacros['carbs'] - $currentMacros['carbs'],
                    'calories' => $targetMacros['calories'] - $currentMacros['calories'],
                ];

                $totalGramsToAdjust = 0; // Accumulator for this ingredient

                // Prioritize adjusting for primary macros (P, F, C) if they are significantly off
                // We will try to adjust each ingredient for the macro it best influences AND is off target.
                foreach (['protein', 'fat', 'carbs'] as $macro) {
                    $target = $targetMacros[$macro] ?? 0;
                    $current = $currentMacros[$macro] ?? 0;

                    // Only consider adjusting if the macro is outside tolerance
                    if (!$this->isWithinTolerance($current, $target, $tolerancePercentage)) {
                        $macroPerGram = ($model->{"{$macro}_per_100g"} ?? 0) / 100;
                        $deviation = $current - $target; // Positive if over, negative if under

                        // Only adjust if ingredient actually contributes to this macro significantly
                        if (abs($macroPerGram) > 0.01) { // 0.01 to avoid division by near zero, and for tiny contributions
                            // Calculate required change in grams for this specific macro
                            $requiredGramsForMacro = $deviation / $macroPerGram;

                            // Apply a gentle adjustment factor
                            // The closer we are to target, the smaller the adjustment.
                            $adjustmentFactor = 0.2 * ($target > 0 ? abs($deviation / $target) : 1); // Dynamic dampening
                            $adjustmentFactor = min($adjustmentFactor, 0.5); // Cap max factor

                            $gramsChangeForThisMacro = -($requiredGramsForMacro * $adjustmentFactor); // Subtract because deviation is (current - target)

                            // Accumulate the change; a weighted average or sum could be more complex but better
                            // For simplicity, let's just make one "push" towards the most off primary macro, or calories if all primaries are ok.
                            // If we've already adjusted for the primary macros, then try calories.
                            if (abs($deviation) > 0 && abs($gramsChangeForThisMacro) > 0.5) { // Only adjust if significant change
                                $totalGramsToAdjust += $gramsChangeForThisMacro;
                            }
                        }
                    }
                }

                // If no primary macro adjustments were made or significant, try to adjust for calories
                if (abs($totalGramsToAdjust) < 1) { // If only minor adjustments planned based on P/F/C, adjust for calories
                    $deviation = $currentMacros['calories'] - $targetMacros['calories'];
                    if (!$this->isWithinTolerance($currentMacros['calories'], $targetMacros['calories'], $tolerancePercentage)) {
                        $caloriesPerGram = ($model->calories_per_100g ?? 0) / 100;
                        if (abs($caloriesPerGram) > 0.01) {
                            $requiredGramsForCalories = $deviation / $caloriesPerGram;
                            $adjustmentFactor = 0.2 * ($targetMacros['calories'] > 0 ? abs($deviation / $targetMacros['calories']) : 1);
                            $adjustmentFactor = min($adjustmentFactor, 0.5);
                            $gramsChangeForThisMacro = -($requiredGramsForCalories * $adjustmentFactor);
                            if (abs($gramsChangeForThisMacro) > 0.5) {
                                $totalGramsToAdjust += $gramsChangeForThisMacro;
                            }
                        }
                    }
                }


                $newGrams = $currentGrams + $totalGramsToAdjust;

                // Apply adjustment limits based on original grams
                $maxAllowedGrams = $originalGrams * (1 + $maxAdjustPercentage);
                $minAllowedGrams = $originalGrams * (1 - $maxAdjustPercentage);

                $newGrams = max($minIngredientGrams, min($maxAllowedGrams, $newGrams)); // Ensure min grams and max allowed
                $newGrams = round($newGrams); // Round to nearest gram

                if (abs($newGrams - $currentGrams) > 0) { // Only mark as adjusted if grams actually changed
                    $item['grams'] = $newGrams;
                    $somethingAdjusted = true;
                    $this->info("   Adjusted '{$model->display_name}' by " . round($totalGramsToAdjust) . "g (from {$currentGrams}g to {$newGrams}g).");
                }
                return $item;
            });

            // Update the main collection only after all adjustments for this iteration are calculated
            $optimizedIngredients = $tempOptimizedIngredients;


            // After adjusting quantities, recalculate current macros
            $currentMacros = $this->calculateCurrentMealMacros($optimizedIngredients);

            // Stuck detection: If macros haven't changed significantly in an iteration
            if (
                abs($currentMacros['calories'] - $initialMacrosThisIteration['calories']) < 1 &&
                abs($currentMacros['protein'] - $initialMacrosThisIteration['protein']) < 1 &&
                abs($currentMacros['fat'] - $initialMacrosThisIteration['fat']) < 1 &&
                abs($currentMacros['carbs'] - $initialMacrosThisIteration['carbs']) < 1
            ) {
                $stuckCounter++;
            } else {
                $stuckCounter = 0; // Reset if progress is made
            }

            // Break condition: if no adjustments or swaps were made in an iteration
            // AND targets are still not met (i.e., we are stuck)
            // Or if we are stuck for multiple consecutive iterations, break to avoid infinite loop
            if ((!$somethingAdjusted && !$this->checkMacroTargets($currentMacros, $targetMacros, $tolerancePercentage)) || $stuckCounter >= 5) {
                $this->warning("Optimization stuck: No significant progress made in iteration {$iteration} or stuck for 5+ iterations.");
                // Before breaking, try adding a supplementary ingredient if allowed and targets not met
                if ($allowSwaps && !$this->checkMacroTargets($currentMacros, $targetMacros, $tolerancePercentage)) {
                    $this->info("Trying to add supplementary ingredient as optimization is stuck...");
                    // Try to add a supplementary ingredient to fix the *most deficient* macro
                    $deviations = $this->getMacroDeviations($currentMacros, $targetMacros);
                    arsort($deviations); // Sort to get largest deficiencies first (negative values mean deficient)

                    $mostDeficientMacro = null;
                    foreach ($deviations as $macro => $deviationAmount) {
                        if ($deviationAmount < 0 && $targetMacros[$macro] > 0) { // If negative (undershot) and target is > 0
                            $mostDeficientMacro = $macro;
                            break;
                        }
                    }
                    if (!$mostDeficientMacro) { // If all primary macros are fine, but calories are off, or if positive deviation (overshot)
                         if (!$this->isWithinTolerance($currentMacros['calories'], $targetMacros['calories'], $tolerancePercentage)) {
                             $mostDeficientMacro = 'calories'; // Try to adjust calories if they are the main issue
                         }
                    }

                    if ($mostDeficientMacro) {
                         // Find the most deficient non-calorie macro for targeted addition if possible.
                        $primaryDeficientMacro = null;
                        if ($mostDeficientMacro != 'calories' && $targetMacros[$mostDeficientMacro] > $currentMacros[$mostDeficientMacro]) {
                            $primaryDeficientMacro = $mostDeficientMacro;
                        } else {
                            // If calories is the most deficient, or no other primary macro is deficient,
                            // find the overall most deficient primary macro.
                            $maxDeficiencyVal = 0;
                            foreach(['protein', 'carbs', 'fat'] as $m) {
                                if ($targetMacros[$m] > 0 && ($targetMacros[$m] - $currentMacros[$m]) > $maxDeficiencyVal) {
                                    $maxDeficiencyVal = ($targetMacros[$m] - $currentMacros[$m]);
                                    $primaryDeficientMacro = $m;
                                }
                            }
                        }

                        if ($primaryDeficientMacro) {
                            $addedIngredient = $this->addSupplementaryIngredient(
                                $primaryDeficientMacro,
                                $currentMacros[$primaryDeficientMacro],
                                $targetMacros[$primaryDeficientMacro],
                                $optimizedIngredients->pluck('model.name')->toArray()
                            );

                            if ($addedIngredient) {
                                $optimizedIngredients->push($addedIngredient);
                                $currentMacros = $this->calculateCurrentMealMacros($optimizedIngredients);
                                $this->info("   Added new ingredient: {$addedIngredient['model']->display_name}. New macros: " . json_encode($currentMacros));
                                $stuckCounter = 0; // Reset stuck counter if an ingredient was added
                                continue; // Continue the loop to re-evaluate with the new ingredient
                            }
                        }
                    }
                }
                break; // If no ingredient was added, or swaps not allowed, truly break
            }
        }

        // Final check and return
        if ($this->checkMacroTargets($currentMacros, $targetMacros, $tolerancePercentage)) {
            $this->info("Optimization successful after {$iteration} iterations!");
            // Remove ingredients that ended up with very low grams after optimization
            $finalIngredients = $optimizedIngredients->filter(function($item) use ($minIngredientGrams) {
                return $item['grams'] >= $minIngredientGrams;
            })->map(function ($item) {
                return [
                    'name' => $item['model']->name,
                    'display_name' => $item['model']->display_name,
                    'grams' => $item['grams'],
                    'is_fixed' => $item['is_fixed'],
                ];
            })->values()->toArray(); // values() to reset numeric keys

            return [
                'optimized_ingredients' => $finalIngredients,
                'final_macros' => $currentMacros,
            ];
        } else {
            $this->warning("Optimization failed to meet targets after {$iteration} iterations. Final macros: " . json_encode($currentMacros));
            return null; // Optimization failed
        }
    }


    /**
     * Helper to calculate macros for the current state of optimizedIngredients collection.
     */
    private function calculateCurrentMealMacros(Collection $optimizedIngredients): array
    {
        $currentCalories = 0;
        $currentProtein = 0;
        $currentFat = 0;
        $currentCarbs = 0;

        foreach ($optimizedIngredients as $item) {
            $grams = $item['grams'];
            $model = $item['model'];

            if ($grams > 0) {
                $currentCalories += ($model->calories_per_100g / 100) * $grams;
                $currentProtein += ($model->protein_per_100g / 100) * $grams;
                $currentFat += ($model->fat_per_100g / 100) * $grams;
                $currentCarbs += ($model->carbs_per_100g / 100) * $grams;
            }
        }

        return [
            'calories' => round($currentCalories),
            'protein' => round($currentProtein),
            'fat' => round($currentFat),
            'carbs' => round($currentCarbs),
        ];
    }

    /**
     * Checks if current macros are within tolerance of target macros.
     */
    private function checkMacroTargets(array $current, array $target, float $tolerance): bool
    {
        foreach (['calories', 'protein', 'fat', 'carbs'] as $macro) {
            if (!$this->isWithinTolerance($current[$macro] ?? 0, $target[$macro] ?? 0, $tolerance)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Helper to check if a single macro value is within tolerance.
     */
    private function isWithinTolerance(float $current, float $target, float $tolerance): bool
    {
        if ($target > 0) {
            $deviation = abs(($current - $target) / $target);
            return $deviation <= $tolerance;
        } else { // If target is 0, allow a very small absolute value, else it's off target.
            return $current <= 5; // e.g., if current is more than 5g when target is 0, it's out of tolerance.
        }
    }


    /**
     * Calculates deviations for each macro from target.
     * Positive if over, negative if under.
     */
    private function getMacroDeviations(array $current, array $target): array
    {
        return [
            'calories' => $current['calories'] - $target['calories'],
            'protein' => $current['protein'] - $target['protein'],
            'fat' => $current['fat'] - $target['fat'],
            'carbs' => $current['carbs'] - $target['carbs'],
        ];
    }

    /**
     * Attempts to add a supplementary ingredient to balance a specific deficient macro.
     * It tries to pick the most macro-dense option for the deficient macro.
     *
     * @param string $deficientMacro The macro that is deficient (e.g., 'protein', 'carbs', 'fat', 'calories').
     * @param float $currentAmount Current amount of the deficient macro.
     * @param float $targetAmount Target amount of the deficient macro.
     * @param array $excludeNames Names of ingredients already in the meal to avoid adding duplicates or unwanted items.
     * @return array|null The added ingredient data (model, grams, is_fixed) or null if failed.
     */
    private function addSupplementaryIngredient(string $deficientMacro, float $currentAmount, float $targetAmount, array $excludeNames = []): ?array
    {
        // Special handling for calories if it's the target macro for addition
        if ($deficientMacro === 'calories') {
            // If we're adding for calories, find an ingredient that's relatively balanced or high in needed macros
            // This is harder to target for a heuristic. For now, let's just pick one with good general calories.
            $ingredient = Ingredient::where('is_supplementary', true)
                                    ->whereNotIn('name', $excludeNames)
                                    ->orderByDesc('calories_per_100g')
                                    ->first();
        } else {
            $macroPer100gColumn = "{$deficientMacro}_per_100g";
            $ingredient = Ingredient::where('is_supplementary', true)
                                    ->where($macroPer100gColumn, '>', 0) // Must contribute to the target macro
                                    ->whereNotIn('name', $excludeNames)
                                    ->orderByDesc($macroPer100gColumn) // Order by efficiency for this macro
                                    ->first();
        }


        if ($ingredient) {
            $requiredAmount = $targetAmount - $currentAmount;
            if ($requiredAmount <= 0) {
                return null; // No deficiency for this macro
            }

            $macroPerGram = ($ingredient->{"{$deficientMacro}_per_100g"} ?? 0) / 100;
            // If trying to add for calories, and macroPerGram is 0, use total calories per gram.
            if ($deficientMacro === 'calories' && $macroPerGram <= 0) {
                $macroPerGram = ($ingredient->calories_per_100g ?? 0) / 100;
            }

            if ($macroPerGram <= 0) {
                return null; // Ingredient doesn't contribute to the deficient macro
            }

            // Calculate grams needed to cover the deficiency for this macro
            $gramsToAdd = $requiredAmount / $macroPerGram;

            // Apply a buffer to prevent endless small additions or overshooting just this macro.
            // E.g., add 80% of what's needed.
            $gramsToAdd *= 0.8;
            $gramsToAdd = max(10, round($gramsToAdd)); // Ensure at least 10g or calculated amount

            // Don't add excessively large amounts in one go
            $gramsToAdd = min($gramsToAdd, 200); // Cap additions to a reasonable size (e.g., max 200g per add)

            $this->info("Attempting to add {$gramsToAdd}g of {$ingredient->display_name} for {$deficientMacro} deficiency.");
            return [
                'model' => $ingredient,
                'grams' => $gramsToAdd,
                'original_grams' => $gramsToAdd, // This is its initial amount if newly added
                'is_fixed' => false, // Newly added ingredients are adjustable
            ];
        }
        return null;
    }


    // Helper methods for logging (already present)
    private function info(string $message): void
    {
        if (app()->runningInConsole()) {
            echo $message . "\n";
        } else {
            \Log::info($message);
        }
    }

    private function warning(string $message): void
    {
        if (app()->runningInConsole()) {
            echo "WARNING: " . $message . "\n";
        } else {
            \Log::warning($message);
        }
    }

    private function error(string $message): void
    {
        if (app()->runningInConsole()) {
            echo "ERROR: " . $message . "\n";
        } else {
            \Log::error($message);
        }
    }

}