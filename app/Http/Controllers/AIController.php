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
        $prompt = "I need a meal plan suggestion based on these precise requirements:\n\n".
            "### **🔹 User Information**\n".
            "- **Gender:** $gender\n".
            "- **Age:** $age years\n".
            "- **Height:** $height cm\n".
            "- **Weight:** $weight kg\n".
            "- **Goal:** $goal\n\n".
            "### **🔹 Macronutrient Goals**\n".
            "- **Target Calories:** $calories kcal\n".
            "- **Target Protein:** $protein g\n".
            "- **Target Carbohydrates:** $carbs g\n".
            "- **Fat should be adjusted dynamically to balance macros.**\n\n".
            "### **🔹 Allowed Ingredients**\n".
            "**You must prioritize these ingredients:** $ingredients\n\n".
            "### **🔹 Smart Ingredient Selection Rules**\n".
            "- **Use ONLY ingredients that help reach the calorie and macronutrient targets.**\n".
            "- **Remove ingredients that do not significantly contribute to macros.**\n".
            "- **If necessary, ADD new ingredients to improve balance.**\n".
            "- **Portion sizes should be adjusted to stay within ±5% of the target macros.**\n\n".
            "### **🔹 Cooking Instructions**\n".
            "- Provide clear, structured cooking steps.\n".
            "- Include preparation time, cooking time, and method (e.g., baking, grilling, steaming).\n".
            "- Make it beginner-friendly with easy-to-follow steps.\n";

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

        // **🔹 Return cleaned and structured AI response**
        return response()->json([
            'success' => true,
            'meal_plan' => $parsedData
        ]);
    }
}
