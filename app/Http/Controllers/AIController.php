<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Meal;
use Illuminate\Support\Facades\Storage;

class AIController extends Controller
{
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
        \Log::info("AI Response: " . json_encode($data));

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
            \Log::info("USDA API Request", [
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
            \Log::info("USDA Response for: {$query}", $data);
    
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

        // Explicitly required from the user input
        $request->validate([
            'meal_type' => 'required|string|in:breakfast,lunch,dinner,snack,post_workout,pre_workout',
            'calories' => 'required|integer|min:100',
            'protein' => 'required|integer|min:0',
            'carbs' => 'required|integer|min:0',
            'ingredients' => 'required|array|min:1',
        ]);

        $mealType = $request->meal_type;
        $ingredients = implode(", ", $request->ingredients);
        $calories = $request->calories;
        $protein = $request->protein;
        $carbs = $request->carbs;

        $openaiApiKey = config('services.openai.key');
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        
        $promptTemplate = <<<EOT
        I need a meal plan suggestion based on these precise requirements:

        ### 🔹 User Information
        - Gender: {{gender}}
        - Age: {{age}} years
        - Height: {{height}} cm
        - Weight: {{weight}} kg
        - Goal: {{goal}}
        - Meal Type: {{meal_type}} (The meal must be suitable for this type)

        ### 🔹 Macronutrient Goals
        - Target Calories: {{calories}} kcal
        - Target Protein: {{protein}} g
        - Target Carbohydrates: {{carbs}} g
        - Fat should be adjusted dynamically to balance macros.

        ### 🔹 Allowed Ingredients
        Prioritize these ingredients: {{ingredients}}

        ### 🔹 Smart Ingredient Selection Rules
        - The meal must strictly align with the meal type: {{meal_type}}. Adjust ingredients, cooking style, and portion sizes accordingly.
        - Use ONLY ingredients that help reach the calorie and macronutrient targets.
        - Remove ingredients that do not significantly contribute to macros.
        - If necessary, ADD new ingredients to improve balance.
        - Portion sizes should be adjusted to stay within ±5% of the target macros.

        ### 🔹 Macronutrient Integrity
        - Macronutrient values must reflect **real values from reliable nutritional databases like USDA**.
        - Each ingredient must indicate its **preparation state** (e.g., raw, cooked, grilled, steamed).
        - NEVER alter protein, fat, or carb values just to match the targets.
        - If nutritional goals aren’t met, adjust quantities or swap ingredients — but DO NOT fabricate macros.

        ### 🔹 Cooking Instructions
        - Provide clear, structured cooking steps.
        - Include preparation time, cooking time, and method (e.g., baking, grilling, steaming).
        - Make it beginner-friendly with easy-to-follow steps.

        ### 🔹 IMPORTANT RULES
        1️⃣ STRICTLY return only a valid JSON object. No markdown, no explanations, no formatting.  
        2️⃣ Ensure all meals include detailed macronutrient breakdowns **per ingredient**, with quantities and cooking states.  
        3️⃣ The final response format must **exactly match** the example JSON below:

        {"meal": {
        "name": "Grilled Meat & Potato Bowl",
        "calories": 500,
        "protein": 60,
        "carbs": 40,
        "fat": 30,
        "ingredients": [
            {"name": "Beef (grilled)", "quantity": "150g", "calories": 300, "protein": 50, "carbs": 0, "fat": 18},
            {"name": "Olive Oil", "quantity": "2 tbsp", "calories": 240, "protein": 0, "carbs": 0, "fat": 28},
            {"name": "Potatoes (roasted)", "quantity": "150g", "calories": 130, "protein": 3, "carbs": 30, "fat": 0.2},
            {"name": "Tomato (fresh)", "quantity": "1 medium", "calories": 25, "protein": 1, "carbs": 5, "fat": 0.2}
        ],
        "instructions": [
            "Preheat oven to 400°F (200°C).",
            "Season beef with salt and pepper. Grill or pan-fry for 5-7 minutes per side.",
            "Cut potatoes into wedges, drizzle with 1 tbsp olive oil, and roast for 25-30 minutes.",
            "Slice tomato and set aside.",
            "Drizzle remaining olive oil over meat and potatoes before serving.",
            "Serve all components together in a bowl."
        ]}}
        EOT;
        
                $prompt = str_replace(
                    ['{{gender}}', '{{age}}', '{{height}}', '{{weight}}', '{{goal}}', '{{meal_type}}', '{{calories}}', '{{protein}}', '{{carbs}}', '{{ingredients}}'],
                    [$gender, $age, $height, $weight, $goal, $mealType, $calories, $protein, $carbs, $ingredients],
                    $promptTemplate
                );
        
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $openaiApiKey,
                    'Content-Type' => 'application/json',
                ])->post($endpoint, [
                    'model' => 'gpt-4o',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.7,
                ]);
        
        $data = $response->json();

        Log::info("OpenAI Response:", $data);

        $message = $data['choices'][0]['message']['content'] ?? null;
        if (!$message) {
            return response()->json(['error' => 'Invalid AI response'], 500);
        }

        $cleaned = preg_replace('/```json|```/', '', $message);
        $parsed = json_decode(trim($cleaned), true);

        if (!$parsed) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to parse OpenAI response into valid JSON.',
                'raw' => $message
            ], 500);
        }

        // **Now generate the image based on the meal name and ingredients**
        $mealName = $parsed['meal']['name'];
        $ingredients = implode(", ", array_map(function ($ingredient) {
            return $ingredient['name'];
        }, $parsed['meal']['ingredients']));

        $imagePrompt = "Generate a realistic, high-resolution photograph of a freshly prepared meal titled '$mealName', served on a ceramic plate. The dish should be fully cooked and beautifully arranged with the following ingredients: $ingredients. The scene should resemble a professional food photo, styled for a recipe book or restaurant menu. Use natural lighting, slight shadows, and realistic textures — no illustrations, no cartoons, no sketches.";
        //Log::info("Image Generation Prompt: " . $imagePrompt);

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
                $url       = $img['url'];
                $contents  = file_get_contents($url);
                $path      = 'meals/' . uniqid('meal_') . '.png';
                Storage::disk('public')->put($path, $contents);
                $localUrls[] = asset("storage/{$path}");
            }
        } else {
            return response()->json(['error'=>'Failed to generate images'], 500);
        }

        // Save the meal
        $meal = new Meal();
        $meal->user_id = $user->id;
        $meal->name = $parsed['meal']['name'];
        $meal->meal_type = $mealType;
        $meal->ingredients = json_encode($parsed['meal']['ingredients']);
        $meal->instructions = json_encode($parsed['meal']['instructions']);
        $meal->calories = $parsed['meal']['calories'];
        $meal->protein = $parsed['meal']['protein'];
        $meal->carbs = $parsed['meal']['carbs'];
        $meal->fat = $parsed['meal']['fat'];
        $meal->images = $localUrls;
        $meal->save();

        return response()->json([
            'success' => true,
            'message' => 'Meal successfully generated and stored.',
            'meal_plan' => $parsed,
            'images_link' => $localUrls,
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
