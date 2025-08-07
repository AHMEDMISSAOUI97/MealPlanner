<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class MacroOptimizer
{
    protected string $glpkModelPath;
    protected string $tempDataPath;
    protected string $tempOutputPath;
    protected string $glpsolCommand;

    // Added new properties for macro mapping
    protected array $macroMapping = [
        1 => 'calories',
        2 => 'protein',
        3 => 'fat',
        4 => 'carbs',
    ];
    protected array $reverseMacroMapping; // For quick lookup from name to integer

    public function __construct()
    {
        // Define paths for GLPK model, temporary data, and output files.
        $this->glpkModelPath = base_path('app/Services/glpk/nutrition.mod');
        $this->tempDataPath = storage_path('app/glpk_data.dat');
        $this->tempOutputPath = storage_path('app/glpk_output.txt');
        $this->glpsolCommand = 'glpsol'; // Ensure glpsol is in your system's PATH or provide full path

        // Initialize reverse mapping for faster lookups
        $this->reverseMacroMapping = array_flip($this->macroMapping);
    }

    /**
     * Optimizes ingredient amounts to meet macro targets using GLPK.
     *
     * @param array $currentIngredients   Associative array of original_ingredient_name => fixed_grams (e.g., ['Chicken Breast' => 50])
     * If an ingredient is not in this array, its min_grams will be 0 and max_grams will be a large number.
     * @param array $targetMacros         Associative array of macro => ['min' => val, 'max' => val]
     * (e.g., ['calories' => ['min' => 500, 'max' => 700]])
     * @param array $ingredientDatabase   Array of ingredient data directly from your database query.
     * Example:
     * [
     * ['id' => 1, 'name' => 'Chicken Breast', 'calories_per_100g' => 165, ...],
     * ['id' => 2, 'name' => 'White Rice', 'calories_per_100g' => 130, ...],
     * // ... more ingredients as associative arrays/objects from DB
     * ]
     * @return array Returns an array with optimized ingredient amounts, or an error message.
     */
    public function optimizeIngredientsForMacros(
        array $currentIngredients, // This is the fixed ingredients array
        array $targetMacros,       // This is the target macros array
        array $ingredientDatabase  // This is the full ingredient database from your DB
    ): array {
        // Step 1.1: Pre-process ingredientDatabase for easier GLPK interaction
        // This map will convert 'name' to a GLPK-compatible 'slug' and convert _per_100g to _per_g
        $glpkIngredientsMap = []; // Maps slug to all processed data, including original name
        $ingredientNamesForGlpk = []; // To hold the slugs for the GLPK set INGREDIENTS

        // Loop through the FULL $ingredientDatabase, not $currentIngredients
        foreach ($ingredientDatabase as $ingredient) {
            // Use a slugified version of the name for GLPK
            $slug = str_replace([' ', '-', '.'], '_', strtolower($ingredient['name']));
            $ingredientNamesForGlpk[] = "\"$slug\""; // GLPK string literals need quotes

            $glpkIngredientsMap[$slug] = [
                'original_name' => $ingredient['name'], // Keep original name for display later
                'calories_per_g' => ($ingredient['calories_per_100g'] ?? 0) / 100,
                'protein_per_g' => ($ingredient['protein_per_100g'] ?? 0) / 100,
                'fat_per_g' => ($ingredient['fat_per_100g'] ?? 0) / 100,
                'carbs_per_g' => ($ingredient['carbs_per_100g'] ?? 0) / 100,
                // Assuming 'cost_per_100g' might exist or default to 0 if not
                'cost_per_g' => (isset($ingredient['cost_per_100g']) ? ($ingredient['cost_per_100g'] / 100) : 0),
            ];
        }

        // Step 1.2: Generate GLPK data file (.dat)
        $dataGenerationResult = $this->generateGlpkDataFile(
            $currentIngredients, // Pass original fixed ingredients
            $targetMacros,
            $glpkIngredientsMap,
            $ingredientNamesForGlpk
        );

        if (isset($dataGenerationResult['error'])) {
            Log::error('Meal Optimization Failed: ' . $dataGenerationResult['error']);
            return ['error' => 'Failed to prepare optimization data.'];
        }

        // Step 2: Execute GLPK solver
        $command = [
            $this->glpsolCommand,
            '--model', $this->glpkModelPath,
            '--data', $this->tempDataPath,
            '--output', $this->tempOutputPath,
        ];

        Log::info('GLPK Command: ' . implode(' ', $command));

        $process = new Process($command);
        $process->run();

        // Check if GLPK process was successful
        if (!$process->isSuccessful()) {
            $errorMessage = $process->getErrorOutput() ?: $process->getOutput();
            Log::error('GLPK Process Failed: ' . $process->getCommandLine() . "\n" .
                        "Exit Code: " . $process->getExitCode() . "(" . $process->getExitCodeText() . ")\n\n" .
                        "Working directory: " . $process->getWorkingDirectory() . "\n\n" .
                        "Output:\n================\n" . $process->getOutput() . "\n\n" .
                        "Error Output:\n================\n " . $errorMessage);

            return ['error' => 'GLPK optimization failed: ' . $errorMessage];
        }

        // Step 4: Parse GLPK output
        try {
            $glpkOutput = File::get($this->tempOutputPath);
            $parsedOutput = $this->parseGlpkOutput($glpkOutput, $glpkIngredientsMap);
        } catch (\Exception $e) {
            Log::error('Failed to parse GLPK output: ' . $e->getMessage());
            return ['error' => 'Failed to process optimization results.'];
        } finally {
            // Clean up temporary files
            File::delete($this->tempDataPath);
            File::delete($this->tempOutputPath);
        }

        return $parsedOutput;
    }

    protected function generateGlpkDataFile(
        array $currentIngredients, // This is the fixed ingredients array
        array $targetMacros,
        array $glpkIngredientsMap, // Pre-processed map of slug => processed_ingredient_data
        array $ingredientNamesForGlpk // List of slugs for the GLPK 'INGREDIENTS' set
    ): array {
        $dataContent = [];

        // Define INGREDIENTS set using the prepared slugs
        $dataContent[] = "set INGREDIENTS := " . implode(" ", $ingredientNamesForGlpk) . ";";

        // Parameters for min_grams and max_grams
        $minGramsData = [];
        $maxGramsData = [];
        $costPerGData = [];

        foreach ($glpkIngredientsMap as $slug => $details) {
            // Map $currentIngredients (keyed by original name) to the $slug
            $fixedAmount = 0; // Default to 0, or if not found
            foreach ($currentIngredients as $originalName => $amount) {
                // IMPORTANT: Ensure this slugification logic matches exactly
                // what was used to create $slug for $glpkIngredientsMap
                if (str_replace([' ', '-', '.'], '_', strtolower($originalName)) === $slug) {
                    $fixedAmount = $amount;
                    break;
                }
            }

            // min_grams: If fixedAmount is set and > 0, fix it. Otherwise, 0.
            $minGramsData[] = "$slug " . ($fixedAmount > 0 ? $fixedAmount : 0);

            // max_grams: If fixedAmount is set and > 0, fix it. Otherwise, a very large number (unlimited).
            $maxGramsData[] = "$slug " . ($fixedAmount > 0 ? $fixedAmount : 9999999);

            // Cost per gram (already handled in pre-processing in optimizeIngredientsForMacros)
            $costPerGData[] = "$slug " . ($details['cost_per_g'] ?? 0);
        }

        $dataContent[] = "param min_grams := " . implode(" ", $minGramsData) . ";";
        $dataContent[] = "param max_grams := " . implode(" ", $maxGramsData) . ";";
        $dataContent[] = "param cost_per_g := " . implode(" ", $costPerGData) . ";";

        // Macro content per gram (matrix format) - NOW USING INTEGER HEADERS
        $macroHeaders = implode(" ", array_keys($this->macroMapping)); // e.g., "1 2 3 4"
        $dataContent[] = "param macro_content : " . $macroHeaders . " :=";
        foreach ($glpkIngredientsMap as $slug => $details) {
            $dataContent[] = sprintf(
                "%s %.6f %.6f %.6f %.6f",
                $slug,
                $details['calories_per_g'], // Value for Macro 1 (calories)
                $details['protein_per_g'],  // Value for Macro 2 (protein)
                $details['fat_per_g'],      // Value for Macro 3 (fat)
                $details['carbs_per_g']     // Value for Macro 4 (carbs)
            );
        }
        $dataContent[] = ";"; // End of macro_content table

        // Target macros - NOW USING INTEGER INDICES
        $dataContent[] = "param target_min := ";
        foreach ($targetMacros as $macroName => $limits) {
            $macroId = $this->reverseMacroMapping[$macroName] ?? null;
            if ($macroId !== null) {
                $dataContent[] = "$macroId " . ($limits['min'] ?? 0);
            } else {
                Log::warning("Unknown macro name: " . $macroName . " encountered in targetMacros.");
            }
        }
        $dataContent[] = ";";

        $dataContent[] = "param target_max := ";
        foreach ($targetMacros as $macroName => $limits) {
            $macroId = $this->reverseMacroMapping[$macroName] ?? null;
            if ($macroId !== null) {
                $dataContent[] = "$macroId " . ($limits['max'] ?? 9999999);
            } else {
                 Log::warning("Unknown macro name: " . $macroName . " encountered in targetMacros.");
            }
        }
        $dataContent[] = ";";

        // Add explicit 'end;' and a final newline for GLPK's data file parser
        $dataContent[] = "end;";
        $dataContent[] = "";

        try {
            File::put($this->tempDataPath, implode("\n", $dataContent));
        } catch (\Exception $e) {
            Log::error('Failed to write GLPK data file: ' . $e->getMessage());
            return ['error' => 'Failed to prepare optimization data.'];
        }

        return ['success' => true];
    }

    protected function parseGlpkOutput(string $output, array $glpkIngredientsMap): array
    {
        $optimizedIngredients = [];
        $deviationLow = [];
        $deviationHigh = [];
        $objectiveValue = null;
        $status = 'unknown';

        // Check for common GLPK statuses
        if (str_contains($output, 'Problem:    optimal')) {
            $status = 'optimal';
        } elseif (str_contains($output, 'No solution')) {
            $status = 'no solution'; // This is often a sub-status of infeasible or undefined
        } elseif (str_contains($output, 'problem has no feasible solution')) {
            $status = 'infeasible';
        } elseif (str_contains($output, 'problem has unbounded solution')) {
            $status = 'unbounded';
        }

        // Extract ingredient amounts (x)
        if (preg_match_all('/^x\[([a-zA-Z0-9_]+)\]\s*=\s*([0-9\.]+)\s*$/m', $output, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $slug = $match[1];
                $amount = (float) $match[2];
                if (isset($glpkIngredientsMap[$slug])) { // Ensure the slug exists in our map
                    if ($amount > 0.001) { // Only include if amount is significant
                        $optimizedIngredients[$glpkIngredientsMap[$slug]['name']] = round($amount, 2);
                    }
                }
            }
        }

        // Extract deviation_low - NOW MAPPING INTEGER TO STRING
        if (preg_match_all('/^deviation_low\[([0-9]+)\]\s*=\s*([0-9\.]+)\s*$/m', $output, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $macroId = (int) $match[1];
                $deviation = (float) $match[2];
                if ($deviation > 0.001) { // Only include if deviation is significant
                    $macroName = $this->macroMapping[$macroId] ?? 'unknown_macro_' . $macroId;
                    $deviationLow[$macroName] = round($deviation, 2);
                }
            }
        }

        // Extract deviation_high - NOW MAPPING INTEGER TO STRING
        if (preg_match_all('/^deviation_high\[([0-9]+)\]\s*=\s*([0-9\.]+)\s*$/m', $output, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $macroId = (int) $match[1];
                $deviation = (float) $match[2];
                if ($deviation > 0.001) { // Only include if deviation is significant
                    $macroName = $this->macroMapping[$macroId] ?? 'unknown_macro_' . $macroId;
                    $deviationHigh[$macroName] = round($deviation, 2);
                }
            }
        }

        // Extract total_deviation (objective value)
        if (preg_match('/^total_deviation\s*=\s*([0-9\.-]+)\s*$/m', $output, $matches)) {
            $objectiveValue = (float) $matches[1];
        }

        // Check solution status and return appropriate response
        if ($status === 'optimal') {
            return [
                'status' => 'optimal',
                'optimized_ingredients' => $optimizedIngredients,
                'deviations' => [
                    'low' => $deviationLow,
                    'high' => $deviationHigh,
                ],
                'total_objective_value' => $objectiveValue,
            ];
        } elseif ($status === 'infeasible') {
            return [
                'status' => 'infeasible',
                'error' => 'No feasible solution found that satisfies all constraints. Try relaxing your targets or adding more ingredients.',
            ];
        } else {
            // Catch other non-optimal statuses like 'no solution', 'unbounded', or 'unknown'
            return [
                'status' => $status,
                'error' => 'GLPK solver could not find an optimal solution or encountered an issue. Status: ' . $status . '. Full output: ' . $output, // Provide full output for deeper debug
                'optimized_ingredients' => $optimizedIngredients, // Still return partial results if available
                'deviations' => [
                    'low' => $deviationLow,
                    'high' => $deviationHigh,
                ],
                'total_objective_value' => $objectiveValue,
            ];
        }
    }
}