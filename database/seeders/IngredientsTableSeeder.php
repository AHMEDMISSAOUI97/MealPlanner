<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ingredient;

class IngredientsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing ingredients to prevent duplicates on re-seed
        Ingredient::truncate();

        $ingredients = [
            // --- Protein Sources ---
            [
                'name' => 'chicken breast', 'display_name' => 'Chicken Breast (raw)',
                'calories_per_100g' => 165, 'protein_per_100g' => 31, 'fat_per_100g' => 3.6, 'carbs_per_100g' => 0,
                'common_units' => [['unit' => 'fillet', 'grams' => 150]], 'cuisine_affinities' => ['American', 'Asian', 'Mediterranean'],
                'allergens' => [], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'beef sirloin', 'display_name' => 'Beef Sirloin (raw)',
                'calories_per_100g' => 200, 'protein_per_100g' => 26, 'fat_per_100g' => 10, 'carbs_per_100g' => 0,
                'common_units' => [], 'cuisine_affinities' => ['American', 'European'],
                'allergens' => [], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'salmon fillet', 'display_name' => 'Salmon Fillet (raw)',
                'calories_per_100g' => 208, 'protein_per_100g' => 20, 'fat_per_100g' => 13, 'carbs_per_100g' => 0,
                'common_units' => [], 'cuisine_affinities' => ['Mediterranean', 'Asian', 'Nordic'],
                'allergens' => ['fish'], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'eggs', 'display_name' => 'Large Egg (raw)',
                'calories_per_100g' => 155, 'protein_per_100g' => 12.6, 'fat_per_100g' => 10.6, 'carbs_per_100g' => 1.1,
                'common_units' => [['unit' => 'unit', 'grams' => 50]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['egg'], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'tuna canned in water', 'display_name' => 'Tuna (canned in water)',
                'calories_per_100g' => 116, 'protein_per_100g' => 25.5, 'fat_per_100g' => 1.3, 'carbs_per_100g' => 0,
                'common_units' => [['unit' => 'can (drained)', 'grams' => 130]], 'cuisine_affinities' => ['Mediterranean', 'American'],
                'allergens' => ['fish'], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'greek yogurt plain', 'display_name' => 'Greek Yogurt (plain, non-fat)',
                'calories_per_100g' => 59, 'protein_per_100g' => 10, 'fat_per_100g' => 0.4, 'carbs_per_100g' => 3.6,
                'common_units' => [['unit' => 'cup', 'grams' => 245]], 'cuisine_affinities' => ['Mediterranean', 'Global'],
                'allergens' => ['dairy'], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'cottage cheese', 'display_name' => 'Cottage Cheese (low fat)',
                'calories_per_100g' => 72, 'protein_per_100g' => 11.2, 'fat_per_100g' => 1, 'carbs_per_100g' => 3.4,
                'common_units' => [['unit' => 'cup', 'grams' => 226]], 'cuisine_affinities' => ['American', 'European'],
                'allergens' => ['dairy'], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'tofu firm', 'display_name' => 'Tofu (firm)',
                'calories_per_100g' => 76, 'protein_per_100g' => 8, 'fat_per_100g' => 4.8, 'carbs_per_100g' => 1.9,
                'common_units' => [], 'cuisine_affinities' => ['Asian'],
                'allergens' => ['soy'], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'whey protein powder', 'display_name' => 'Whey Protein Powder',
                'calories_per_100g' => 370, 'protein_per_100g' => 80, 'fat_per_100g' => 3, 'carbs_per_100g' => 5,
                'common_units' => [['unit' => 'scoop', 'grams' => 30]], 'cuisine_affinities' => ['Supplement'],
                'allergens' => ['dairy'], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'black beans canned', 'display_name' => 'Black Beans (canned, drained)',
                'calories_per_100g' => 132, 'protein_per_100g' => 8.9, 'fat_per_100g' => 0.5, 'carbs_per_100g' => 23.7,
                'common_units' => [['unit' => 'can', 'grams' => 240]], 'cuisine_affinities' => ['Mexican', 'South American'],
                'allergens' => [], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],

            // --- Carb Sources ---
            [
                'name' => 'brown rice cooked', 'display_name' => 'Brown Rice (cooked)',
                'calories_per_100g' => 123, 'protein_per_100g' => 2.7, 'fat_per_100g' => 1, 'carbs_per_100g' => 25.6,
                'common_units' => [['unit' => 'cup', 'grams' => 200]], 'cuisine_affinities' => ['Asian', 'Mediterranean'],
                'allergens' => [], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'oats dry', 'display_name' => 'Rolled Oats (dry)',
                'calories_per_100g' => 389, 'protein_per_100g' => 16.9, 'fat_per_100g' => 6.9, 'carbs_per_100g' => 66.3,
                'common_units' => [['unit' => 'cup', 'grams' => 80]], 'cuisine_affinities' => ['American', 'European'],
                'allergens' => ['gluten'], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'sweet potato', 'display_name' => 'Sweet Potato (raw)',
                'calories_per_100g' => 86, 'protein_per_100g' => 1.6, 'fat_per_100g' => 0.1, 'carbs_per_100g' => 20.1,
                'common_units' => [['unit' => 'medium', 'grams' => 150]], 'cuisine_affinities' => ['American', 'Asian'],
                'allergens' => [], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'quinoa cooked', 'display_name' => 'Quinoa (cooked)',
                'calories_per_100g' => 120, 'protein_per_100g' => 4.4, 'fat_per_100g' => 1.9, 'carbs_per_100g' => 21.3,
                'common_units' => [['unit' => 'cup', 'grams' => 185]], 'cuisine_affinities' => ['South American', 'Mediterranean'],
                'allergens' => [], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'whole wheat pasta cooked', 'display_name' => 'Whole Wheat Pasta (cooked)',
                'calories_per_100g' => 150, 'protein_per_100g' => 5.8, 'fat_per_100g' => 0.9, 'carbs_per_100g' => 30.6,
                'common_units' => [['unit' => 'cup', 'grams' => 140]], 'cuisine_affinities' => ['Italian', 'Mediterranean'],
                'allergens' => ['gluten'], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'bread whole wheat', 'display_name' => 'Whole Wheat Bread (slice)',
                'calories_per_100g' => 247, 'protein_per_100g' => 13, 'fat_per_100g' => 3.5, 'carbs_per_100g' => 41,
                'common_units' => [['unit' => 'slice', 'grams' => 30]], 'cuisine_affinities' => ['American', 'European'],
                'allergens' => ['gluten'], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'potato', 'display_name' => 'Potato (raw)',
                'calories_per_100g' => 77, 'protein_per_100g' => 2, 'fat_per_100g' => 0.1, 'carbs_per_100g' => 17,
                'common_units' => [['unit' => 'medium', 'grams' => 170]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'banana', 'display_name' => 'Banana (raw)',
                'calories_per_100g' => 89, 'protein_per_100g' => 1.1, 'fat_per_100g' => 0.3, 'carbs_per_100g' => 22.8,
                'common_units' => [['unit' => 'medium', 'grams' => 118]], 'cuisine_affinities' => ['Tropical', 'Global'],
                'allergens' => [], 'ingredient_type' => 'fruit', 'is_supplementary' => true,
            ],
            [
                'name' => 'apple', 'display_name' => 'Apple (raw)',
                'calories_per_100g' => 52, 'protein_per_100g' => 0.3, 'fat_per_100g' => 0.2, 'carbs_per_100g' => 13.8,
                'common_units' => [['unit' => 'medium', 'grams' => 182]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'fruit', 'is_supplementary' => true,
            ],
            [
                'name' => 'berries mixed', 'display_name' => 'Mixed Berries (frozen)',
                'calories_per_100g' => 57, 'protein_per_100g' => 0.7, 'fat_per_100g' => 0.5, 'carbs_per_100g' => 13.6,
                'common_units' => [['unit' => 'cup', 'grams' => 140]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'fruit', 'is_supplementary' => true,
            ],

            // --- Fat Sources ---
            [
                'name' => 'olive oil', 'display_name' => 'Olive Oil',
                'calories_per_100g' => 884, 'protein_per_100g' => 0, 'fat_per_100g' => 100, 'carbs_per_100g' => 0,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 13.5]], 'cuisine_affinities' => ['Mediterranean', 'Italian'],
                'allergens' => [], 'ingredient_type' => 'fat_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'avocado', 'display_name' => 'Avocado (raw)',
                'calories_per_100g' => 160, 'protein_per_100g' => 2, 'fat_per_100g' => 14.66, 'carbs_per_100g' => 8.53,
                'common_units' => [['unit' => 'medium', 'grams' => 150]], 'cuisine_affinities' => ['Mexican', 'Mediterranean'],
                'allergens' => [], 'ingredient_type' => 'fat_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'almonds', 'display_name' => 'Almonds (raw)',
                'calories_per_100g' => 579, 'protein_per_100g' => 21.2, 'fat_per_100g' => 49.9, 'carbs_per_100g' => 21.6,
                'common_units' => [['unit' => 'quarter cup', 'grams' => 30]], 'cuisine_affinities' => ['Mediterranean', 'Asian'],
                'allergens' => ['tree_nuts'], 'ingredient_type' => 'fat_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'cheddar cheese', 'display_name' => 'Cheddar Cheese',
                'calories_per_100g' => 404, 'protein_per_100g' => 24.9, 'fat_per_100g' => 33.1, 'carbs_per_100g' => 1.3,
                'common_units' => [], 'cuisine_affinities' => ['American', 'European'],
                'allergens' => ['dairy'], 'ingredient_type' => 'fat_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'peanut butter', 'display_name' => 'Peanut Butter (creamy)',
                'calories_per_100g' => 588, 'protein_per_100g' => 22.2, 'fat_per_100g' => 51, 'carbs_per_100g' => 22.3,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 16]], 'cuisine_affinities' => ['American', 'Asian'],
                'allergens' => ['peanut'], 'ingredient_type' => 'fat_source', 'is_supplementary' => true,
            ],

            // --- Vegetables ---
            [
                'name' => 'broccoli', 'display_name' => 'Broccoli (raw)',
                'calories_per_100g' => 34, 'protein_per_100g' => 2.8, 'fat_per_100g' => 0.4, 'carbs_per_100g' => 6.6,
                'common_units' => [['unit' => 'cup (chopped)', 'grams' => 91]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'spinach', 'display_name' => 'Spinach (raw)',
                'calories_per_100g' => 23, 'protein_per_100g' => 2.9, 'fat_per_100g' => 0.4, 'carbs_per_100g' => 3.6,
                'common_units' => [['unit' => 'cup', 'grams' => 30]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'bell pepper red', 'display_name' => 'Red Bell Pepper (raw)',
                'calories_per_100g' => 31, 'protein_per_100g' => 1, 'fat_per_100g' => 0.3, 'carbs_per_100g' => 6,
                'common_units' => [['unit' => 'medium', 'grams' => 164]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'onion', 'display_name' => 'Onion (raw)',
                'calories_per_100g' => 40, 'protein_per_100g' => 1.1, 'fat_per_100g' => 0.1, 'carbs_per_100g' => 9.3,
                'common_units' => [['unit' => 'medium', 'grams' => 110]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'tomato', 'display_name' => 'Tomato (raw)',
                'calories_per_100g' => 18, 'protein_per_100g' => 0.9, 'fat_per_100g' => 0.2, 'carbs_per_100g' => 3.9,
                'common_units' => [['unit' => 'medium', 'grams' => 123]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'carrots', 'display_name' => 'Carrots (raw)',
                'calories_per_100g' => 41, 'protein_per_100g' => 0.9, 'fat_per_100g' => 0.2, 'carbs_per_100g' => 9.6,
                'common_units' => [['unit' => 'medium', 'grams' => 61]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'cucumber', 'display_name' => 'Cucumber (raw)',
                'calories_per_100g' => 15, 'protein_per_100g' => 0.7, 'fat_per_100g' => 0.1, 'carbs_per_100g' => 3.6,
                'common_units' => [['unit' => 'medium', 'grams' => 200]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'zucchini', 'display_name' => 'Zucchini (raw)',
                'calories_per_100g' => 17, 'protein_per_100g' => 1.2, 'fat_per_100g' => 0.3, 'carbs_per_100g' => 3.1,
                'common_units' => [['unit' => 'medium', 'grams' => 200]], 'cuisine_affinities' => ['Mediterranean'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'garlic', 'display_name' => 'Garlic (raw)',
                'calories_per_100g' => 149, 'protein_per_100g' => 6.4, 'fat_per_100g' => 0.5, 'carbs_per_100g' => 33.1,
                'common_units' => [['unit' => 'clove', 'grams' => 3]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'spice_herb', 'is_supplementary' => false, // Not for macro balancing
            ],
            [
                'name' => 'lemon', 'display_name' => 'Lemon (raw)',
                'calories_per_100g' => 29, 'protein_per_100g' => 1.1, 'fat_per_100g' => 0.3, 'carbs_per_100g' => 9.3,
                'common_units' => [['unit' => 'medium', 'grams' => 58]], 'cuisine_affinities' => ['Mediterranean', 'Global'],
                'allergens' => [], 'ingredient_type' => 'fruit', 'is_supplementary' => false,
            ],

            // --- Dairy / Alternatives ---
            [
                'name' => 'milk skim', 'display_name' => 'Milk (skim)',
                'calories_per_100g' => 34, 'protein_per_100g' => 3.4, 'fat_per_100g' => 0.1, 'carbs_per_100g' => 4.9,
                'common_units' => [['unit' => 'cup', 'grams' => 245]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['dairy'], 'ingredient_type' => 'dairy', 'is_supplementary' => true,
            ],
            [
                'name' => 'almond milk unsweetened', 'display_name' => 'Almond Milk (unsweetened)',
                'calories_per_100g' => 15, 'protein_per_100g' => 0.5, 'fat_per_100g' => 1.1, 'carbs_per_100g' => 0.6,
                'common_units' => [['unit' => 'cup', 'grams' => 240]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['tree_nuts'], 'ingredient_type' => 'dairy_alternative', 'is_supplementary' => true,
            ],

            // --- Other common ingredients ---
            [
                'name' => 'canned chickpeas', 'display_name' => 'Canned Chickpeas (drained)',
                'calories_per_100g' => 164, 'protein_per_100g' => 8.9, 'fat_per_100g' => 2.6, 'carbs_per_100g' => 27.4,
                'common_units' => [['unit' => 'can', 'grams' => 240]], 'cuisine_affinities' => ['Mediterranean', 'Middle Eastern'],
                'allergens' => [], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'lentils cooked', 'display_name' => 'Lentils (cooked)',
                'calories_per_100g' => 116, 'protein_per_100g' => 9, 'fat_per_100g' => 0.4, 'carbs_per_100g' => 20.1,
                'common_units' => [['unit' => 'cup', 'grams' => 198]], 'cuisine_affinities' => ['Indian', 'Mediterranean'],
                'allergens' => [], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'canned diced tomatoes', 'display_name' => 'Canned Diced Tomatoes',
                'calories_per_100g' => 20, 'protein_per_100g' => 0.9, 'fat_per_100g' => 0.2, 'carbs_per_100g' => 3.9,
                'common_units' => [['unit' => 'can', 'grams' => 400]], 'cuisine_affinities' => ['Italian', 'Mexican'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => false,
            ],
            [
                'name' => 'spinach cooked', 'display_name' => 'Spinach (cooked)',
                'calories_per_100g' => 23, 'protein_per_100g' => 2.9, 'fat_per_100g' => 0.4, 'carbs_per_100g' => 3.6,
                'common_units' => [['unit' => 'cup', 'grams' => 180]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'white rice cooked', 'display_name' => 'White Rice (cooked)',
                'calories_per_100g' => 130, 'protein_per_100g' => 2.7, 'fat_per_100g' => 0.3, 'carbs_per_100g' => 28.2,
                'common_units' => [['unit' => 'cup', 'grams' => 200]], 'cuisine_affinities' => ['Asian', 'Global'],
                'allergens' => [], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'whole wheat flour', 'display_name' => 'Whole Wheat Flour',
                'calories_per_100g' => 340, 'protein_per_100g' => 13.7, 'fat_per_100g' => 2.5, 'carbs_per_100g' => 72.9,
                'common_units' => [['unit' => 'cup', 'grams' => 120]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['gluten'], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'milk 2 percent', 'display_name' => 'Milk (2% fat)',
                'calories_per_100g' => 50, 'protein_per_100g' => 3.3, 'fat_per_100g' => 2, 'carbs_per_100g' => 4.9,
                'common_units' => [['unit' => 'cup', 'grams' => 245]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['dairy'], 'ingredient_type' => 'dairy', 'is_supplementary' => true,
            ],
            [
                'name' => 'butter unsalted', 'display_name' => 'Butter (unsalted)',
                'calories_per_100g' => 717, 'protein_per_100g' => 0.8, 'fat_per_100g' => 81.1, 'carbs_per_100g' => 0.1,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 14.2]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['dairy'], 'ingredient_type' => 'fat_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'salt', 'display_name' => 'Salt (table)',
                'calories_per_100g' => 0, 'protein_per_100g' => 0, 'fat_per_100g' => 0, 'carbs_per_100g' => 0,
                'common_units' => [['unit' => 'teaspoon', 'grams' => 5]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'seasoning', 'is_supplementary' => false,
            ],
            [
                'name' => 'black pepper', 'display_name' => 'Black Pepper (ground)',
                'calories_per_100g' => 251, 'protein_per_100g' => 10.4, 'fat_per_100g' => 3.3, 'carbs_per_100g' => 64.8,
                'common_units' => [['unit' => 'teaspoon', 'grams' => 2.3]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'seasoning', 'is_supplementary' => false,
            ],
            [
                'name' => 'cinnamon', 'display_name' => 'Cinnamon (ground)',
                'calories_per_100g' => 247, 'protein_per_100g' => 3.9, 'fat_per_100g' => 1.2, 'carbs_per_100g' => 80.6,
                'common_units' => [['unit' => 'teaspoon', 'grams' => 2.6]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'spice_herb', 'is_supplementary' => false,
            ],
            [
                'name' => 'oregano dry', 'display_name' => 'Oregano (dry)',
                'calories_per_100g' => 265, 'protein_per_100g' => 9, 'fat_per_100g' => 4.3, 'carbs_per_100g' => 68.9,
                'common_units' => [['unit' => 'teaspoon', 'grams' => 1.4]], 'cuisine_affinities' => ['Mediterranean', 'Italian'],
                'allergens' => [], 'ingredient_type' => 'spice_herb', 'is_supplementary' => false,
            ],
            [
                'name' => 'garlic powder', 'display_name' => 'Garlic Powder',
                'calories_per_100g' => 331, 'protein_per_100g' => 16.6, 'fat_per_100g' => 0.7, 'carbs_per_100g' => 72.7,
                'common_units' => [['unit' => 'teaspoon', 'grams' => 3.1]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'spice_herb', 'is_supplementary' => false,
            ],
            [
                'name' => 'onion powder', 'display_name' => 'Onion Powder',
                'calories_per_100g' => 348, 'protein_per_100g' => 10.3, 'fat_per_100g' => 1.1, 'carbs_per_100g' => 80.9,
                'common_units' => [['unit' => 'teaspoon', 'grams' => 3]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'spice_herb', 'is_supplementary' => false,
            ],
            [
                'name' => 'vegetable broth', 'display_name' => 'Vegetable Broth',
                'calories_per_100g' => 5, 'protein_per_100g' => 0.2, 'fat_per_100g' => 0.1, 'carbs_per_100g' => 0.8,
                'common_units' => [['unit' => 'cup', 'grams' => 240]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'liquid', 'is_supplementary' => false,
            ],
            [
                'name' => 'chicken broth', 'display_name' => 'Chicken Broth',
                'calories_per_100g' => 7, 'protein_per_100g' => 0.5, 'fat_per_100g' => 0.2, 'carbs_per_100g' => 0.6,
                'common_units' => [['unit' => 'cup', 'grams' => 240]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'liquid', 'is_supplementary' => false,
            ],
            [
                'name' => 'water', 'display_name' => 'Water',
                'calories_per_100g' => 0, 'protein_per_100g' => 0, 'fat_per_100g' => 0, 'carbs_per_100g' => 0,
                'common_units' => [['unit' => 'cup', 'grams' => 236]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'liquid', 'is_supplementary' => false,
            ],
            [
                'name' => 'canned crushed tomatoes', 'display_name' => 'Canned Crushed Tomatoes',
                'calories_per_100g' => 20, 'protein_per_100g' => 0.9, 'fat_per_100g' => 0.2, 'carbs_per_100g' => 3.9,
                'common_units' => [['unit' => 'can', 'grams' => 400]], 'cuisine_affinities' => ['Italian', 'Mediterranean'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => false,
            ],
            [
                'name' => 'broccoli cooked', 'display_name' => 'Broccoli (cooked)',
                'calories_per_100g' => 35, 'protein_per_100g' => 2.4, 'fat_per_100g' => 0.4, 'carbs_per_100g' => 7.2,
                'common_units' => [['unit' => 'cup (chopped)', 'grams' => 156]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'cauliflower', 'display_name' => 'Cauliflower (raw)',
                'calories_per_100g' => 25, 'protein_per_100g' => 1.9, 'fat_per_100g' => 0.3, 'carbs_per_100g' => 4.9,
                'common_units' => [['unit' => 'cup (chopped)', 'grams' => 107]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'green beans', 'display_name' => 'Green Beans (raw)',
                'calories_per_100g' => 31, 'protein_per_100g' => 1.8, 'fat_per_100g' => 0.2, 'carbs_per_100g' => 7,
                'common_units' => [['unit' => 'cup', 'grams' => 110]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'sweet corn canned', 'display_name' => 'Sweet Corn (canned, drained)',
                'calories_per_100g' => 86, 'protein_per_100g' => 3.2, 'fat_per_100g' => 1.2, 'carbs_per_100g' => 18.7,
                'common_units' => [['unit' => 'can', 'grams' => 200]], 'cuisine_affinities' => ['American', 'Mexican'],
                'allergens' => [], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'pork tenderloin', 'display_name' => 'Pork Tenderloin (raw)',
                'calories_per_100g' => 143, 'protein_per_100g' => 26, 'fat_per_100g' => 3.5, 'carbs_per_100g' => 0,
                'common_units' => [], 'cuisine_affinities' => ['American', 'European'],
                'allergens' => [], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'shrimp raw', 'display_name' => 'Shrimp (raw)',
                'calories_per_100g' => 85, 'protein_per_100g' => 20.3, 'fat_per_100g' => 0.3, 'carbs_per_100g' => 0.2,
                'common_units' => [], 'cuisine_affinities' => ['Asian', 'Mediterranean'],
                'allergens' => ['shellfish'], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'white fish cod', 'display_name' => 'Cod (raw)',
                'calories_per_100g' => 82, 'protein_per_100g' => 17.8, 'fat_per_100g' => 0.7, 'carbs_per_100g' => 0,
                'common_units' => [], 'cuisine_affinities' => ['European', 'Mediterranean'],
                'allergens' => ['fish'], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'almond butter', 'display_name' => 'Almond Butter',
                'calories_per_100g' => 614, 'protein_per_100g' => 21, 'fat_per_100g' => 55.8, 'carbs_per_100g' => 19.4,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 16]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['tree_nuts'], 'ingredient_type' => 'fat_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'walnuts', 'display_name' => 'Walnuts (raw)',
                'calories_per_100g' => 654, 'protein_per_100g' => 15.2, 'fat_per_100g' => 65.2, 'carbs_per_100g' => 13.7,
                'common_units' => [['unit' => 'quarter cup', 'grams' => 30]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['tree_nuts'], 'ingredient_type' => 'fat_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'coconut oil', 'display_name' => 'Coconut Oil',
                'calories_per_100g' => 892, 'protein_per_100g' => 0, 'fat_per_100g' => 100, 'carbs_per_100g' => 0,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 13.6]], 'cuisine_affinities' => ['Asian', 'Tropical'],
                'allergens' => [], 'ingredient_type' => 'fat_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'chia seeds', 'display_name' => 'Chia Seeds',
                'calories_per_100g' => 486, 'protein_per_100g' => 16.5, 'fat_per_100g' => 30.7, 'carbs_per_100g' => 42.1,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 12]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'fat_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'flax seeds', 'display_name' => 'Flax Seeds',
                'calories_per_100g' => 534, 'protein_per_100g' => 18.3, 'fat_per_100g' => 42.2, 'carbs_per_100g' => 28.9,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 10]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'fat_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'whole grain bread', 'display_name' => 'Whole Grain Bread',
                'calories_per_100g' => 266, 'protein_per_100g' => 12.3, 'fat_per_100g' => 3.6, 'carbs_per_100g' => 49.6,
                'common_units' => [['unit' => 'slice', 'grams' => 35]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['gluten'], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'oat milk unsweetened', 'display_name' => 'Oat Milk (unsweetened)',
                'calories_per_100g' => 45, 'protein_per_100g' => 1, 'fat_per_100g' => 1.5, 'carbs_per_100g' => 6.5,
                'common_units' => [['unit' => 'cup', 'grams' => 240]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['gluten_cross_contamination'], 'ingredient_type' => 'dairy_alternative', 'is_supplementary' => true,
            ],
            [
                'name' => 'rice milk unsweetened', 'display_name' => 'Rice Milk (unsweetened)',
                'calories_per_100g' => 47, 'protein_per_100g' => 0.3, 'fat_per_100g' => 1.2, 'carbs_per_100g' => 9.2,
                'common_units' => [['unit' => 'cup', 'grams' => 240]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'dairy_alternative', 'is_supplementary' => true,
            ],
            [
                'name' => 'red onion', 'display_name' => 'Red Onion (raw)',
                'calories_per_100g' => 42, 'protein_per_100g' => 1.4, 'fat_per_100g' => 0.2, 'carbs_per_100g' => 9.8,
                'common_units' => [['unit' => 'medium', 'grams' => 110]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'garlic cloves', 'display_name' => 'Garlic Cloves (raw)',
                'calories_per_100g' => 149, 'protein_per_100g' => 6.4, 'fat_per_100g' => 0.5, 'carbs_per_100g' => 33.1,
                'common_units' => [['unit' => 'clove', 'grams' => 3]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'spice_herb', 'is_supplementary' => false,
            ],
            [
                'name' => 'ginger fresh', 'display_name' => 'Ginger (fresh)',
                'calories_per_100g' => 80, 'protein_per_100g' => 1.8, 'fat_per_100g' => 0.8, 'carbs_per_100g' => 17.8,
                'common_units' => [], 'cuisine_affinities' => ['Asian', 'Indian'],
                'allergens' => [], 'ingredient_type' => 'spice_herb', 'is_supplementary' => false,
            ],
            [
                'name' => 'cilantro fresh', 'display_name' => 'Cilantro (fresh)',
                'calories_per_100g' => 23, 'protein_per_100g' => 2.1, 'fat_per_100g' => 0.5, 'carbs_per_100g' => 3.7,
                'common_units' => [['unit' => 'bunch', 'grams' => 30]], 'cuisine_affinities' => ['Mexican', 'Asian'],
                'allergens' => [], 'ingredient_type' => 'spice_herb', 'is_supplementary' => false,
            ],
            [
                'name' => 'parsley fresh', 'display_name' => 'Parsley (fresh)',
                'calories_per_100g' => 36, 'protein_per_100g' => 3, 'fat_per_100g' => 0.8, 'carbs_per_100g' => 6.3,
                'common_units' => [['unit' => 'bunch', 'grams' => 30]], 'cuisine_affinities' => ['Mediterranean', 'European'],
                'allergens' => [], 'ingredient_type' => 'spice_herb', 'is_supplementary' => false,
            ],
            [
                'name' => 'spinach frozen', 'display_name' => 'Spinach (frozen)',
                'calories_per_100g' => 23, 'protein_per_100g' => 2.9, 'fat_per_100g' => 0.4, 'carbs_per_100g' => 3.6,
                'common_units' => [['unit' => 'cup', 'grams' => 190]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'peas frozen', 'display_name' => 'Peas (frozen)',
                'calories_per_100g' => 81, 'protein_per_100g' => 5.4, 'fat_per_100g' => 0.4, 'carbs_per_100g' => 14.4,
                'common_units' => [['unit' => 'cup', 'grams' => 145]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'mixed vegetables frozen', 'display_name' => 'Mixed Vegetables (frozen)',
                'calories_per_100g' => 60, 'protein_per_100g' => 2.5, 'fat_per_100g' => 0.3, 'carbs_per_100g' => 12,
                'common_units' => [['unit' => 'cup', 'grams' => 150]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => true,
            ],
            [
                'name' => 'sweet potato cooked', 'display_name' => 'Sweet Potato (cooked, baked)',
                'calories_per_100g' => 90, 'protein_per_100g' => 2, 'fat_per_100g' => 0.2, 'carbs_per_100g' => 20.7,
                'common_units' => [['unit' => 'medium', 'grams' => 114]], 'cuisine_affinities' => ['American', 'Asian'],
                'allergens' => [], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'lentil pasta cooked', 'display_name' => 'Red Lentil Pasta (cooked)',
                'calories_per_100g' => 130, 'protein_per_100g' => 8, 'fat_per_100g' => 0.5, 'carbs_per_100g' => 24,
                'common_units' => [['unit' => 'cup', 'grams' => 140]], 'cuisine_affinities' => ['Italian', 'Mediterranean'],
                'allergens' => [], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'chickpea pasta cooked', 'display_name' => 'Chickpea Pasta (cooked)',
                'calories_per_100g' => 135, 'protein_per_100g' => 8.5, 'fat_per_100g' => 1.5, 'carbs_per_100g' => 23,
                'common_units' => [['unit' => 'cup', 'grams' => 140]], 'cuisine_affinities' => ['Italian', 'Mediterranean'],
                'allergens' => [], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'corn tortilla', 'display_name' => 'Corn Tortilla',
                'calories_per_100g' => 218, 'protein_per_100g' => 5.2, 'fat_per_100g' => 2.4, 'carbs_per_100g' => 44,
                'common_units' => [['unit' => 'tortilla', 'grams' => 28]], 'cuisine_affinities' => ['Mexican', 'South American'],
                'allergens' => [], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'flour tortilla', 'display_name' => 'Flour Tortilla (medium)',
                'calories_per_100g' => 314, 'protein_per_100g' => 8.3, 'fat_per_100g' => 8.7, 'carbs_per_100g' => 52,
                'common_units' => [['unit' => 'tortilla', 'grams' => 45]], 'cuisine_affinities' => ['Mexican', 'American'],
                'allergens' => ['gluten'], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'canned kidney beans', 'display_name' => 'Kidney Beans (canned, drained)',
                'calories_per_100g' => 127, 'protein_per_100g' => 8.7, 'fat_per_100g' => 0.5, 'carbs_per_100g' => 22.8,
                'common_units' => [['unit' => 'can', 'grams' => 240]], 'cuisine_affinities' => ['Mexican', 'American'],
                'allergens' => [], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'canned pinto beans', 'display_name' => 'Pinto Beans (canned, drained)',
                'calories_per_100g' => 128, 'protein_per_100g' => 8.3, 'fat_per_100g' => 0.6, 'carbs_per_100g' => 23.1,
                'common_units' => [['unit' => 'can', 'grams' => 240]], 'cuisine_affinities' => ['Mexican', 'American'],
                'allergens' => [], 'ingredient_type' => 'protein_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'salsa', 'display_name' => 'Salsa (red, mild)',
                'calories_per_100g' => 36, 'protein_per_100g' => 1.5, 'fat_per_100g' => 0.2, 'carbs_per_100g' => 7.8,
                'common_units' => [['unit' => 'cup', 'grams' => 245]], 'cuisine_affinities' => ['Mexican', 'American'],
                'allergens' => [], 'ingredient_type' => 'sauce_condiment', 'is_supplementary' => false,
            ],
            [
                'name' => 'soy sauce', 'display_name' => 'Soy Sauce (low sodium)',
                'calories_per_100g' => 53, 'protein_per_100g' => 8.2, 'fat_per_100g' => 0.6, 'carbs_per_100g' => 4.9,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 15]], 'cuisine_affinities' => ['Asian'],
                'allergens' => ['soy', 'gluten'], 'ingredient_type' => 'sauce_condiment', 'is_supplementary' => false,
            ],
            [
                'name' => 'honey', 'display_name' => 'Honey',
                'calories_per_100g' => 304, 'protein_per_100g' => 0.3, 'fat_per_100g' => 0, 'carbs_per_100g' => 82.4,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 21]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'sweetener', 'is_supplementary' => false,
            ],
            [
                'name' => 'maple syrup', 'display_name' => 'Maple Syrup',
                'calories_per_100g' => 260, 'protein_per_100g' => 0, 'fat_per_100g' => 0, 'carbs_per_100g' => 67,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 20]], 'cuisine_affinities' => ['American'],
                'allergens' => [], 'ingredient_type' => 'sweetener', 'is_supplementary' => false,
            ],
            [
                'name' => 'vinegar balsamic', 'display_name' => 'Balsamic Vinegar',
                'calories_per_100g' => 19, 'protein_per_100g' => 0.5, 'fat_per_100g' => 0, 'carbs_per_100g' => 3.9,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 15]], 'cuisine_affinities' => ['Italian', 'Mediterranean'],
                'allergens' => [], 'ingredient_type' => 'sauce_condiment', 'is_supplementary' => false,
            ],
            [
                'name' => 'canned tomatoes whole peeled', 'display_name' => 'Canned Whole Peeled Tomatoes',
                'calories_per_100g' => 20, 'protein_per_100g' => 0.9, 'fat_per_100g' => 0.2, 'carbs_per_100g' => 3.9,
                'common_units' => [['unit' => 'can', 'grams' => 400]], 'cuisine_affinities' => ['Italian', 'Mediterranean'],
                'allergens' => [], 'ingredient_type' => 'vegetable', 'is_supplementary' => false,
            ],
            [
                'name' => 'oat flour', 'display_name' => 'Oat Flour',
                'calories_per_100g' => 389, 'protein_per_100g' => 16.9, 'fat_per_100g' => 6.9, 'carbs_per_100g' => 66.3,
                'common_units' => [['unit' => 'cup', 'grams' => 100]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['gluten_cross_contamination'], 'ingredient_type' => 'carb_source', 'is_supplementary' => true,
            ],
            [
                'name' => 'cocoa powder unsweetened', 'display_name' => 'Cocoa Powder (unsweetened)',
                'calories_per_100g' => 228, 'protein_per_100g' => 19.6, 'fat_per_100g' => 13.7, 'carbs_per_100g' => 57.9,
                'common_units' => [['unit' => 'tablespoon', 'grams' => 5]], 'cuisine_affinities' => ['Global'],
                'allergens' => [], 'ingredient_type' => 'other', 'is_supplementary' => false,
            ],
            [
                'name' => 'yogurt plain full fat', 'display_name' => 'Plain Yogurt (full fat)',
                'calories_per_100g' => 61, 'protein_per_100g' => 3.5, 'fat_per_100g' => 3.3, 'carbs_per_100g' => 4.7,
                'common_units' => [['unit' => 'cup', 'grams' => 245]], 'cuisine_affinities' => ['Global'],
                'allergens' => ['dairy'], 'ingredient_type' => 'dairy', 'is_supplementary' => true,
            ],
        ];

        foreach ($ingredients as $ingredientData) {
            Ingredient::create($ingredientData);
        }
    }
}