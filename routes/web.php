<?php

use App\Http\Controllers\AIController;
use App\Models\Ingredient;
use App\Services\NutritionService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/optimize-meal', [AIController::class, 'optimizeMeal']);

Route::get('/test-macros', function (NutritionService $nutritionService) {
    // Test Case 1: Ingredient already in your seeded DB (e.g., 'chicken breast')
    // Should use local data.
    $ingredients1 = [
        ['name' => 'chicken breast', 'grams' => 150],
        ['name' => 'brown rice cooked', 'grams' => 200],
    ];
    $macros1 = $nutritionService->calculateTotalMacros($ingredients1);
    echo "<h2>Test Case 1 (Local Ingredients):</h2>";
    echo "<pre>" . json_encode($macros1, JSON_PRETTY_PRINT) . "</pre>";
    echo "<hr>";

    // Test Case 2: Ingredient NOT in your seeded DB (e.g., 'quail eggs')
    // Should trigger USDA API call and store in DB.
    // Choose an ingredient you are reasonably sure is NOT in your 94 seeded items.
    // Also include a known ingredient to ensure mixed inputs work.
    $ingredients2 = [
        ['name' => 'quail eggs', 'grams' => 60], // ~4-5 quail eggs
        ['name' => 'avocado', 'grams' => 100],
    ];
    $macros2 = $nutritionService->calculateTotalMacros($ingredients2);
    echo "<h2>Test Case 2 (New Ingredient from USDA):</h2>";
    echo "<pre>" . json_encode($macros2, JSON_PRETTY_PRINT) . "</pre>";
    echo "<hr>";

    // Test Case 3: Ingredient NOT found by USDA (e.g., a gibberish name)
    // Should log an error and skip the ingredient.
    $ingredients3 = [
        ['name' => 'nonexistentfood123', 'grams' => 100],
        ['name' => 'broccoli', 'grams' => 150],
    ];
    $macros3 = $nutritionService->calculateTotalMacros($ingredients3);
    echo "<h2>Test Case 3 (Non-existent Ingredient):</h2>";
    echo "<pre>" . json_encode($macros3, JSON_PRETTY_PRINT) . "</pre>";
    echo "<hr>";


    // Optional: Verify 'quail eggs' is now in your DB for Test Case 2
    $quailEggs = Ingredient::where('name', 'quail eggs')->first();
    if ($quailEggs) {
        echo "<h2>Verification: 'quail eggs' in DB after Test Case 2</h2>";
        echo "<pre>" . json_encode($quailEggs->toArray(), JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<h2>Verification: 'quail eggs' NOT found in DB.</h2>";
    }

    return "Macro calculation tests complete. Check your Laravel logs for more details (storage/logs/laravel.log).";
});

// --- Comprehensive Test for optimizeIngredientsForMacros ---
Route::get('/test-optimization', function (NutritionService $nutritionService) {
    echo "<h1>Macro Optimization Tests</h1>";
    echo "<p>Check <code>storage/logs/laravel.log</code> for detailed iteration-by-iteration output.</p><hr>";

    // --- Test Case 1: Basic Adjustment (Undershooting Target) ---
    echo "<h2>Test Case 1: Undershooting Targets (Increase Quantities)</h2>";
    $initialIngredients1 = [
        ['name' => 'chicken breast', 'grams' => 50],
        ['name' => 'white rice cooked', 'grams' => 100],
    ];
    $targetMacros1 = [
        'calories' => 500,
        'protein' => 40,
        'fat' => 10,
        'carbs' => 60,
    ];
    $options1 = [
        'allow_swaps' => false, // Don't allow new ingredients for this test
        'max_adjust_percentage' => 1.0, // Allow up to 100% increase/decrease from original
        'min_ingredient_grams' => 10, // Don't reduce below 10g
    ];

    echo "Initial: <pre>" . json_encode($initialIngredients1, JSON_PRETTY_PRINT) . "</pre>";
    echo "Target: <pre>" . json_encode($targetMacros1, JSON_PRETTY_PRINT) . "</pre>";

    $result1 = $nutritionService->optimizeIngredientsForMacros(
        $initialIngredients1,
        $targetMacros1,
        0.10, // 10% tolerance
        $options1
    );

    if ($result1) {
        echo "<h3>Result 1 (Success):</h3>";
        echo "Optimized Ingredients: <pre>" . json_encode($result1['optimized_ingredients'], JSON_PRETTY_PRINT) . "</pre>";
        echo "Final Macros: <pre>" . json_encode($result1['final_macros'], JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<h3>Result 1 (Failed):</h3>";
        echo "<p>Could not optimize to target macros.</p>";
    }
    echo "<hr>";

    // --- Test Case 2: Basic Adjustment (Overshooting Target) ---
    echo "<h2>Test Case 2: Overshooting Targets (Decrease Quantities)</h2>";
    $initialIngredients2 = [
        ['name' => 'chicken breast', 'grams' => 300],
        ['name' => 'brown rice cooked', 'grams' => 400],
    ];
    $targetMacros2 = [
        'calories' => 600,
        'protein' => 50,
        'fat' => 15,
        'carbs' => 70,
    ];
    $options2 = [
        'allow_swaps' => false,
        'max_adjust_percentage' => 0.8, // Allow up to 80% adjustment
        'min_ingredient_grams' => 20, // Don't reduce below 20g
    ];

    echo "Initial: <pre>" . json_encode($initialIngredients2, JSON_PRETTY_PRINT) . "</pre>";
    echo "Target: <pre>" . json_encode($targetMacros2, JSON_PRETTY_PRINT) . "</pre>";

    $result2 = $nutritionService->optimizeIngredientsForMacros(
        $initialIngredients2,
        $targetMacros2,
        0.10, // 10% tolerance
        $options2
    );

    if ($result2) {
        echo "<h3>Result 2 (Success):</h3>";
        echo "Optimized Ingredients: <pre>" . json_encode($result2['optimized_ingredients'], JSON_PRETTY_PRINT) . "</pre>";
        echo "Final Macros: <pre>" . json_encode($result2['final_macros'], JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<h3>Result 2 (Failed):</h3>";
        echo "<p>Could not optimize to target macros.</p>";
    }
    echo "<hr>";

    // --- Test Case 3: Mixed Adjustment & Fixed Ingredients ---
    echo "<h2>Test Case 3: Mixed Adjustment & Fixed Ingredients</h2>";
    $initialIngredients3 = [
        ['name' => 'salmon', 'grams' => 150], // Good fat/protein
        ['name' => 'potato baked', 'grams' => 200], // Good carbs
        ['name' => 'spinach', 'grams' => 100], // Fixed, mostly fiber/vitamins
    ];
    $targetMacros3 = [
        'calories' => 700,
        'protein' => 60,
        'fat' => 25,
        'carbs' => 80,
    ];
    $options3 = [
        'fixed_ingredients_names' => ['spinach'], // Keep spinach quantity fixed
        'allow_swaps' => false,
        'max_adjust_percentage' => 0.75,
        'min_ingredient_grams' => 10,
    ];

    echo "Initial: <pre>" . json_encode($initialIngredients3, JSON_PRETTY_PRINT) . "</pre>";
    echo "Target: <pre>" . json_encode($targetMacros3, JSON_PRETTY_PRINT) . "</pre>";

    $result3 = $nutritionService->optimizeIngredientsForMacros(
        $initialIngredients3,
        $targetMacros3,
        0.10, // 10% tolerance
        $options3
    );

    if ($result3) {
        echo "<h3>Result 3 (Success):</h3>";
        echo "Optimized Ingredients: <pre>" . json_encode($result3['optimized_ingredients'], JSON_PRETTY_PRINT) . "</pre>";
        echo "Final Macros: <pre>" . json_encode($result3['final_macros'], JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<h3>Result 3 (Failed):</h3>";
        echo "<p>Could not optimize to target macros.</p>";
    }
    echo "<hr>";

    // --- Test Case 4: Allowing Swaps (Protein Deficiency) ---
    echo "<h2>Test Case 4: Allowing Swaps (Protein Deficiency)</h2>";
    echo "<p><strong>Requires an ingredient like 'whey protein powder' with `is_supplementary=1` and `ingredient_type='protein_source'` in your DB.</strong></p>";
    $initialIngredients4 = [
        ['name' => 'apple', 'grams' => 200],
        ['name' => 'white rice cooked', 'grams' => 250],
    ];
    $targetMacros4 = [
        'calories' => 600,
        'protein' => 50, // High protein target for a low-protein initial meal
        'fat' => 15,
        'carbs' => 90,
    ];
    $options4 = [
        'allow_swaps' => true, // Crucial for this test
        'max_adjust_percentage' => 0.5,
        'min_ingredient_grams' => 10,
    ];

    echo "Initial: <pre>" . json_encode($initialIngredients4, JSON_PRETTY_PRINT) . "</pre>";
    echo "Target: <pre>" . json_encode($targetMacros4, JSON_PRETTY_PRINT) . "</pre>";

    $result4 = $nutritionService->optimizeIngredientsForMacros(
        $initialIngredients4,
        $targetMacros4,
        0.15, // Higher tolerance for challenging swap scenarios
        $options4
    );

    if ($result4) {
        echo "<h3>Result 4 (Success):</h3>";
        echo "Optimized Ingredients: <pre>" . json_encode($result4['optimized_ingredients'], JSON_PRETTY_PRINT) . "</pre>";
        echo "Final Macros: <pre>" . json_encode($result4['final_macros'], JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<h3>Result 4 (Failed):</h3>";
        echo "<p>Could not optimize to target macros.</p>";
    }
    echo "<hr>";

    // --- Test Case 5: Allowing Swaps (Carb Deficiency) ---
    echo "<h2>Test Case 5: Allowing Swaps (Carb Deficiency)</h2>";
    echo "<p><strong>Requires an ingredient like 'maltodextrin powder' or similar with `is_supplementary=1` and `ingredient_type='carb_source'` in your DB.</strong></p>";
    $initialIngredients5 = [
        ['name' => 'chicken breast', 'grams' => 200],
        ['name' => 'broccoli', 'grams' => 150],
        ['name' => 'olive oil', 'grams' => 20],
    ];
    $targetMacros5 = [
        'calories' => 700,
        'protein' => 60,
        'fat' => 30,
        'carbs' => 100, // High carb target
    ];
    $options5 = [
        'allow_swaps' => true,
        'max_adjust_percentage' => 0.5,
        'min_ingredient_grams' => 10,
    ];

    echo "Initial: <pre>" . json_encode($initialIngredients5, JSON_PRETTY_PRINT) . "</pre>";
    echo "Target: <pre>" . json_encode($targetMacros5, JSON_PRETTY_PRINT) . "</pre>";

    $result5 = $nutritionService->optimizeIngredientsForMacros(
        $initialIngredients5,
        $targetMacros5,
        0.15, // Higher tolerance
        $options5
    );

    if ($result5) {
        echo "<h3>Result 5 (Success):</h3>";
        echo "Optimized Ingredients: <pre>" . json_encode($result5['optimized_ingredients'], JSON_PRETTY_PRINT) . "</pre>";
        echo "Final Macros: <pre>" . json_encode($result5['final_macros'], JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<h3>Result 5 (Failed):</h3>";
        echo "<p>Could not optimize to target macros.</p>";
    }
    echo "<hr>";

    // --- Test Case 6: Target 0 for a Macro (Fat) ---
    echo "<h2>Test Case 6: Target 0 for a Macro (Fat)</h2>";
    $initialIngredients6 = [
        ['name' => 'chicken breast', 'grams' => 150],
        ['name' => 'avocado', 'grams' => 100], // High fat
        ['name' => 'spinach', 'grams' => 100],
    ];
    $targetMacros6 = [
        'calories' => 400,
        'protein' => 45,
        'fat' => 0, // Aim for very low fat
        'carbs' => 20,
    ];
    $options6 = [
        'allow_swaps' => false,
        'max_adjust_percentage' => 0.8,
        'min_ingredient_grams' => 5, // Allow reducing to 5g
    ];

    echo "Initial: <pre>" . json_encode($initialIngredients6, JSON_PRETTY_PRINT) . "</pre>";
    echo "Target: <pre>" . json_encode($targetMacros6, JSON_PRETTY_PRINT) . "</pre>";

    $result6 = $nutritionService->optimizeIngredientsForMacros(
        $initialIngredients6,
        $targetMacros6,
        0.20, // Higher tolerance for challenging low/zero targets
        $options6
    );

    if ($result6) {
        echo "<h3>Result 6 (Success):</h3>";
        echo "Optimized Ingredients: <pre>" . json_encode($result6['optimized_ingredients'], JSON_PRETTY_PRINT) . "</pre>";
        echo "Final Macros: <pre>" . json_encode($result6['final_macros'], JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<h3>Result 6 (Failed):</h3>";
        echo "<p>Could not optimize to target macros.</p>";
    }
    echo "<hr>";


    return "All optimization tests complete. Please analyze the output and `storage/logs/laravel.log`.";

});