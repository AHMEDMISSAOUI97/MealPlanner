<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Meal;
use App\Services\MacroOptimizer;
use Illuminate\Support\Facades\Storage;

class AIController extends Controller
{
    protected MacroOptimizer $macroOptimizer;

    public function __construct(MacroOptimizer $macroOptimizer)
    {
        $this->macroOptimizer = $macroOptimizer;
    }

    public function optimizeMeal(Request $request)
    {
        // --- 1. Fetch Ingredient Data from your Database ---
        // It's crucial that this data matches the structure expected by MacroOptimizer
        // (i.e., contains 'name', 'calories_per_100g', 'protein_per_100g', etc.)
        $ingredientDatabase = Ingredient::all()->toArray();

        // Basic check to ensure you have ingredients in your DB
        if (empty($ingredientDatabase)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No ingredients found in the database. Please seed your ingredients table.',
            ], 400);
        }

        // --- 2. Define Example Input Data ---

        // Example: Fixed ingredients you *must* include (e.g., from user's current meal log)
        // Key is the 'name' from your ingredients table, value is grams
        $currentIngredients = [
             'Chicken Breast' => 50, // Example: user wants to include 50g of chicken breast
             'White Rice' => 100,    // Example: user wants to include 100g of white rice
        ];
        // If you don't want any fixed ingredients, leave this array empty: []

        // Example: Target macro ranges for the meal (e.g., from user's daily goals)
        $targetMacros = [
            'calories' => ['min' => 500, 'max' => 700],
            'protein'  => ['min' => 40,  'max' => 60],
            'fat'      => ['min' => 15,  'max' => 25],
            'carbs'    => ['min' => 50,  'max' => 80],
        ];
        // Ensure the macro keys ('calories', 'protein', 'fat', 'carbs') match the MACROS set in your nutrition.mod file.


        // --- 3. Call the Optimization Service ---
        $result = $this->macroOptimizer->optimizeIngredientsForMacros(
            $currentIngredients,
            $targetMacros,
            $ingredientDatabase
        );

        // --- 4. Handle and Display the Results ---
        if (isset($result['error'])) {
            Log::error('Meal Optimization Failed: ' . $result['error']);
            return response()->json([
                'status' => 'error',
                'message' => $result['error'],
            ], 500); // 500 Internal Server Error for optimization failure
        }

        if ($result['status'] === 'infeasible') {
            return response()->json([
                'status' => 'infeasible',
                'message' => $result['error'], // Contains the infeasible message
                'details' => $result, // Full result for debugging
            ], 422); // 422 Unprocessable Entity for infeasible
        }

        // If optimal solution found
        $optimizedIngredients = $result['optimized_ingredients'];
        $deviations = $result['deviations'];
        $totalObjectiveValue = $result['total_objective_value'];

        // You might want to calculate total macros from the optimized ingredients here for user display
        $calculatedMacros = [];
        foreach ($optimizedIngredients as $name => $grams) {
            // Find the original ingredient from the database to get its per_100g values
            $originalIngredient = collect($ingredientDatabase)->firstWhere('name', $name);

            if ($originalIngredient) {
                foreach (['calories', 'protein', 'fat', 'carbs'] as $macro) {
                    $macroPerG = ($originalIngredient[$macro . '_per_100g'] ?? 0) / 100;
                    $calculatedMacros[$macro] = ($calculatedMacros[$macro] ?? 0) + ($grams * $macroPerG);
                }
            }
        }
        $calculatedMacros = array_map(fn($val) => round($val, 2), $calculatedMacros);


        return response()->json([
            'status' => 'success',
            'message' => 'Meal optimization completed.',
            'optimized_ingredients' => $optimizedIngredients,
            'calculated_macros' => $calculatedMacros, // Total macros from the optimized plan
            'target_macros' => $targetMacros,
            'deviations' => $deviations,
            'total_objective_value' => round($totalObjectiveValue, 2),
        ]);
    }

    public function getMealSuggestion(Request $request)
    {
         // Validate request input
         $request->validate([
            'gender' => 'required|string|in:male,female',
            'age' => 'required|integer|min:1',
            'height' => 'required|integer|min:50',
            'weight' => 'required|integer|min:20',
            'goal' => 'required|string|in:Weight Loss,Muscle Gain,Maintenance',
            'calories' => 'required|integer|min:100',
            'protein' => 'required|integer|min:0',
            'carbs' => 'required|integer|min:0',
            'ingredients' => 'required|array|min:1'
        ]);

        // Retrieve user inputs
        $gender = ucfirst($request->gender);
        $age = $request->age;
        $height = $request->height;
        $weight = $request->weight;
        $goal = $request->goal;
        $calories = $request->calories;
        $protein = $request->protein;
        $carbs = $request->carbs;
        $ingredients = implode(", ", $request->ingredients);

        $apiKey = env('GEMINI_API_KEY');
        $endpoint = env('GEMINI_API_URL') . '?key=' . $apiKey;

        // **🔹 Optimized AI Prompt with Ingredients**
        $prompt = "I need a meal plan suggestion based on these precise requirements:\n\n" .
        "### **🔹 User Information**\n" .
        "- **Gender:** $gender\n" .
        "- **Age:** $age years\n" .
        "- **Height:** $height cm\n" .
        "- **Weight:** $weight kg\n" .
        "- **Goal:** $goal\n\n" .
        "### **🔹 Macronutrient Goals**\n" .
        "- **Target Calories:** $calories kcal\n" .
        "- **Target Protein:** $protein g\n" .
        "- **Target Carbohydrates:** $carbs g\n" .
        "- **Fat should be adjusted dynamically to balance macros.**\n\n" .
        "### **🔹 Allowed Ingredients**\n" .
        "**Prioritize these ingredients:** $ingredients\n\n" .
        "### **🔹 Smart Ingredient Selection Rules**\n" .
        "- **Use ONLY ingredients that help reach the calorie and macronutrient targets.**\n" .
        "- **Remove ingredients that do not significantly contribute to macros.**\n" .
        "- **If necessary, ADD new ingredients to improve balance.**\n" .
        "- **Portion sizes should be adjusted to stay within ±5% of the target macros.**\n\n" .
        "### **🔹 Cooking Instructions**\n" .
        "- Provide clear, structured cooking steps.\n" .
        "- Include preparation time, cooking time, and method (e.g., baking, grilling, steaming).\n" .
        "- Make it beginner-friendly with easy-to-follow steps.\n\n" .
        "### **🔹 IMPORTANT RULES**\n" .
        "1️⃣ **STRICTLY return only a valid JSON object. No markdown, no explanations, no formatting.**\n" .
        "2️⃣ **Ensure all meals include detailed macronutrient breakdowns per ingredient.**\n" .
        "3️⃣ **The final response format must exactly match the example JSON below:**\n\n" .
        "```json\n" .
        "{\n" .
        "  \"meal\": {\n" .
        "    \"name\": \"Grilled Chicken & Avocado Bowl\",\n" .
        "    \"calories\": 500,\n" .
        "    \"protein\": 50,\n" .
        "    \"carbs\": 20,\n" .
        "    \"fat\": 15,\n" .
        "    \"ingredients\": [\n" .
        "      {\"name\": \"Chicken Breast\", \"quantity\": \"150g\", \"calories\": 165, \"protein\": 31, \"carbs\": 0, \"fat\": 3.6},\n" .
        "      {\"name\": \"Avocado\", \"quantity\": \"50g\", \"calories\": 80, \"protein\": 1, \"carbs\": 4, \"fat\": 7},\n" .
        "      {\"name\": \"Quinoa\", \"quantity\": \"50g\", \"calories\": 185, \"protein\": 6, \"carbs\": 30, \"fat\": 3},\n" .
        "      {\"name\": \"Olive Oil\", \"quantity\": \"1 tbsp\", \"calories\": 120, \"protein\": 0, \"carbs\": 0, \"fat\": 14}\n" .
        "    ],\n" .
        "    \"instructions\": [\n" .
        "      \"Preheat grill to medium heat.\",\n" .
        "      \"Season chicken breast with salt and pepper.\",\n" .
        "      \"Grill chicken for 7 minutes per side until fully cooked.\",\n" .
        "      \"Cook quinoa as per package instructions.\",\n" .
        "      \"Slice avocado and mix with cooked quinoa.\",\n" .
        "      \"Drizzle olive oil over the bowl and serve.\"\n" .
        "    ],\n" .
        "    \"difficulty_level\": \"Beginner\"\n" .
        "  }\n" .
        "}\n" .
        "```\n" .
        "🔹 **Remember: ONLY return a valid JSON object!** Any response that does not match the above format is INVALID.";

        // **🔹 Send API Request to Gemini**
        $response = Http::post($endpoint, [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ]
        ]);

        // Decode API response
        $data = $response->json();

        // Log AI response for debugging
        Log::info("AI Response: " . json_encode($data));

        // **🔹 Validate AI Response**
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return response()->json([
                'success' => false,
                'error' => 'AI response is empty or incorrectly formatted.'
            ], 500);
        }

        // Extract raw AI response
        $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];

        // **🔹 Fix 1: Remove Markdown Formatting (` ```json ... ``` `)**
        $cleanedText = preg_replace('/```json\n|\n```/', '', $generatedText);
        
        $cleanedText = preg_replace('/(\d+)g/', '$1', $cleanedText);

        // **🔹 Fix 2: Decode JSON Properly**
        $parsedData = json_decode($cleanedText, true);

        if (!$parsedData) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to parse AI response into valid JSON.',
                'cleaned_ai_response' => $cleanedText
            ], 500);
        }
        // **🔹 Verify Nutritional Data using USDA API**
        $verifiedMeal = $this->verifyMealWithUSDA($parsedData['meal']['ingredients']);

        // **Return cleaned and structured AI response**
        return response()->json([
            'success' => true,
            'meal_plan' => [
                'meal' => array_merge($parsedData['meal'], ['verified_ingredients' => $verifiedMeal])
            ]
        ]);
    }

    /**
     * Query USDA API to verify nutritional data
     */
    private function verifyMealWithUSDA($ingredients)
    {
        $usdaApiKey = env('USDA_API_KEY');
        $usdaEndpoint = "https://api.nal.usda.gov/fdc/v1/foods/search";
        $verifiedData = [];
    
        foreach ($ingredients as $ingredient) {
            $query = $ingredient['name'];
    
            // 🔹 Log full request before sending
            Log::info("USDA API Request", [
                'url' => $usdaEndpoint,
                'params' => [
                    'query' => $query,
                    'api_key' => $usdaApiKey,
                    'pageSize' => 1
                ]
            ]);
    
            // 🔹 Send request with the API key
            $response = Http::get($usdaEndpoint, [
                'query' => $query,
                'api_key' => $usdaApiKey,
                'pageSize' => 1
            ]);
    
            $data = $response->json();
    
            // 🔹 Log full USDA API response
            Log::info("USDA Response for: {$query}", $data);
    
            if (!empty($data['foods'][0])) {
                $foodData = $data['foods'][0];
    
                // Extract verified values
                $verifiedCalories = $this->getNutrientValue($foodData, 1008);
                $verifiedProtein = $this->getNutrientValue($foodData, 1003);
                $verifiedCarbs = $this->getNutrientValue($foodData, 1005);
                $verifiedFat = $this->getNutrientValue($foodData, 1004);
    
                $verifiedData[] = [
                    'name' => $query,
                    'verified_calories' => $verifiedCalories,
                    'verified_protein' => $verifiedProtein,
                    'verified_carbs' => $verifiedCarbs,
                    'verified_fat' => $verifiedFat,
                    'source' => $foodData['description'] ?? 'USDA Database'
                ];
            } else {
                $verifiedData[] = [
                    'name' => $query,
                    'error' => 'Nutritional data not found'
                ];
            }
        }
    
        return $verifiedData;
    }

    /**
     * Extract a nutrient value from USDA API response
     */
    private function getNutrientValue($foodData, $nutrientId)
    {
        foreach ($foodData['foodNutrients'] as $nutrient) {
            if ($nutrient['nutrientId'] == $nutrientId) {
                return $nutrient['value'];
            }
        }
        return 0;
    }

    public function getMealFromOpenAI(Request $request)
    {
        $user = $request->user();

        // Fallback to user profile values if not present in request
        $gender = $request->input('gender', $user->gender);
        $age = $request->input('age', $user->age);
        $height = $request->input('height', $user->height);
        $weight = $request->input('weight', $user->weight);
        $goal = $request->input('goal', $user->goal);
        $preferred_cuisine = $request->input('preferred_cuisine', $user->preferred_cuisine);
        $allergies = $request->input('allergies', $user->allergies ?? []);

        // Validate the request
        $request->validate([
            'meal_type' => 'required|string|in:breakfast,lunch,dinner,snack,post_workout,pre_workout',
            'calories' => 'required|integer|min:100',
            'protein' => 'required|integer|min:0',
            'carbs' => 'required|integer|min:0',
            'ingredients' => 'required|array|min:1',
        ]);

        // Construct the meal data to pass to the GPT API
        $mealType = $request->meal_type;
        $ingredients = implode(", ", $request->ingredients);
        $calories = $request->calories;
        $protein = $request->protein;
        $carbs = $request->carbs;

        $openaiApiKey = config('services.openai.key');
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        // Prepare the prompt for OpenAI meal generation
        $prompt = view('prompts.reliable_meal_prompt', [
            'gender' => $gender,
            'age' => $age,
            'height' => $height,
            'weight' => $weight,
            'goal' => $goal,
            'meal_type' => $mealType,
            'calories' => $calories,
            'protein' => $protein,
            'carbs' => $carbs,
            'ingredients' => $ingredients,
            'allergies' => $allergies,
            'preferred_cuisine' => $preferred_cuisine
        ])->render();

        // Call OpenAI API to generate the meal
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $openaiApiKey,
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
            'model' => 'gpt-4o',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.7,
        ]);

        // Parse the response from OpenAI
        $data = $response->json();
        Log::info("OpenAI Response:", $data);

        $message = $data['choices'][0]['message']['content'] ?? null;
        if (!$message) return response()->json(['error' => 'Invalid AI response'], 500);

        $cleaned = preg_replace('/```json|```/', '', $message);
        $parsed = json_decode(trim($cleaned), true);
        if (!$parsed) return response()->json(['success' => false, 'error' => 'Failed to parse OpenAI response.', 'raw' => $message], 500);

        // USDA Macro validation log
        Log::info("USDA Data for Ingredients: ", $parsed['meal']['ingredients']);

        // Validate the macros from the ingredients
        $totals = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];
        foreach ($parsed['meal']['ingredients'] as $i) {
            // USDA lookup for each ingredient
            $nutrition = $this->lookupUsdaMacros($i['name'], $i['quantity']);
            $i['calories'] = $nutrition['calories'];
            $i['protein'] = $nutrition['protein'];
            $i['carbs'] = $nutrition['carbs'];
            $i['fat'] = $nutrition['fat'];

            // Add to totals
            $totals['calories'] += $nutrition['calories'];
            $totals['protein'] += $nutrition['protein'];
            $totals['carbs'] += $nutrition['carbs'];
            $totals['fat'] += $nutrition['fat'];
        }

        // Log the final macronutrient totals
        Log::info('Total Macros after USDA verification: ', $totals);

        // Check if macros are within acceptable range
        if (!$this->isWithinRange($totals['calories'], $calories) || !$this->isWithinRange($totals['protein'], $protein) || !$this->isWithinRange($totals['carbs'], $carbs)) {
            // If not within range, ask GPT to regenerate the meal
            return response()->json([
                'error' => 'Unable to generate a meal that satisfies the macro constraints with provided ingredients.'
            ], 400);
        }

        // Image Generation Logic
        $mealName = $parsed['meal']['name'];
        $ingredients = implode(", ", array_map(function ($ingredient) {
            return $ingredient['name'];
        }, $parsed['meal']['ingredients']));

        $imagePrompt = "Generate a realistic, high-resolution photograph of a freshly prepared meal titled '$mealName', served on a ceramic plate. The dish should be fully cooked and beautifully arranged with the following ingredients: $ingredients. The scene should resemble a professional food photo, styled for a recipe book or restaurant menu. Use natural lighting, slight shadows, and realistic textures — no illustrations, no cartoons, no sketches.";

        $imageResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $openaiApiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/images/generations', [
            'prompt' => $imagePrompt,
            'n' => 2,
            'size' => '1024x1024'
        ]);

        $imageData = $imageResponse->json();
        $localUrls = [];
        if (!empty($imageData['data'])) {
            foreach ($imageData['data'] as $img) {
                $url = $img['url'];
                $contents = file_get_contents($url);
                $path = 'meals/' . uniqid('meal_') . '.png';
                Storage::disk('public')->put($path, $contents);
                $localUrls[] = asset("storage/{$path}");
            }
        } else {
            return response()->json(['error'=>'Failed to generate images'], 500);
        }

        return response()->json([
            'success' => true,
            'meal_plan' => $parsed,
            'images_link' => $localUrls,
        ], 200);
    }

    // Helper function to check if value is within target range (±5%)
    private function isWithinRange($actual, $target, $percent = 5) {
        $min = $target * (1 - $percent / 100);
        $max = $target * (1 + $percent / 100);
        return $actual >= $min && $actual <= $max;
    }

    // Helper function to fetch USDA macros
    private function lookupUsdaMacros($ingredientName, $quantity) {
        $usdaApiKey = env('USDA_API_KEY');
        $response = Http::get("https://api.nal.usda.gov/fdc/v1/foods/search?query={$ingredientName}&api_key={$usdaApiKey}");

        if ($response->successful()) {
            $data = $response->json();
            // Log USDA response
            Log::info('USDA API Response for ' . $ingredientName, $data);
            
            // Return macros based on the first food item
            return [
                'calories' => $data['foods'][0]['foodNutrients'][0]['value'],
                'protein' => $data['foods'][0]['foodNutrients'][1]['value'],
                'carbs' => $data['foods'][0]['foodNutrients'][2]['value'],
                'fat' => $data['foods'][0]['foodNutrients'][3]['value']
            ];
        }

        // Return mock data if API fails
        return [
            'calories' => 200, 
            'protein' => 15, 
            'carbs' => 20, 
            'fat' => 10
        ];
    }
}
