<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
}
