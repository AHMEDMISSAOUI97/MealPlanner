Generate a meal plan or recipe for the meal type {{ $meal_type }} tailored to the user's macronutrient goals ({{ $calories }} kcal, {{ $protein }} g protein, {{ $carbs }} g carbs, fat target ~{{ $calories * 0.35 / 9 }} g but up to 45g to match example outcomes) using the ingredients: {{ $ingredients }} (format: list of objects with name, quantity, unit). The meal must meet the macro goals within a 10–20% tolerance (e.g., for {{ $calories }} kcal, {{ $calories * 0.8 }}–{{ $calories * 1.2 }}; for {{ $protein }} g protein, {{ $protein * 0.8 }}–{{ $protein * 1.2 }}; for {{ $carbs }} g carbs, {{ $carbs * 0.8 }}–{{ $carbs * 1.2 }}) and prioritize fat near 45g if feasible. Adjust quantities of provided ingredients to align with the goals. If the ingredients result in totals outside the target range, reduce high-calorie or high-carb ingredients (e.g., oils to 1 tbsp, oats to 40–60g for inputs like 100g) and, if needed, add widely available, meal-type-appropriate ingredients (e.g., for breakfast: Greek yogurt, berries, protein powder; for lunch/dinner: chicken, broccoli) that align with {{ $preferred_cuisine ?? 'any' }}. Exclude ingredients listed in {{ is_array($allergies) ? implode(', ', $allergies) : $allergies }}. Do not reuse ingredients from previous meal plans unless explicitly provided or required by cuisine.

Use user information:

Gender: {{ $gender }}
Age: {{ $age }} years
Height: {{ $height }} cm
Weight: {{ $weight }} kg
Goal: {{ $goal }}
Calculate macronutrient values for user-provided ingredients using accurate external nutritional data (e.g., USDA database) if not listed below. For added ingredients, use only the following nutritional data to ensure consistency:

Rice, cooked: 1.3 kcal/g, 0.027g protein, 0.28g carbs, 0.003g fat
Egg, large: 70 kcal/piece, 6g protein, 0.5g carbs, 5g fat
Olive oil: 120 kcal/tbsp, 0g protein, 0g carbs, 14g fat
Cheddar cheese: 4 kcal/g, 0.25g protein, 0.025g carbs, 0.35g fat
Chicken breast, grilled: 1.65 kcal/g, 0.31g protein, 0g carbs, 0.036g fat
Broccoli, steamed: 0.35 kcal/g, 0.028g protein, 0.07g carbs, 0.004g fat
Oats: 3.8 kcal/g, 0.13g protein, 0.66g carbs, 0.07g fat
Almond milk, unsweetened: 0.17 kcal/ml, 0.005g protein, 0.015g carbs, 0.0125g fat
Banana: 0.75 kcal/g, 0.0083g protein, 0.1917g carbs, 0.0025g fat
Peanut butter: 5.9 kcal/g, 0.25g protein, 0.2g carbs, 0.5g fat
Greek yogurt, nonfat: 0.6 kcal/g, 0.1g protein, 0.04g carbs, 0g fat
Blueberries: 0.57 kcal/g, 0.0074g protein, 0.145g carbs, 0.0033g fat
Protein powder, whey: 4 kcal/g, 0.8g protein, 0.1g carbs, 0.05g fat
Chia seeds: 4.9 kcal/g, 0.165g protein, 0.421g carbs, 0.307g fat Provide precise quantities (e.g., grams, tablespoons, pieces). Perform iterative adjustments, summing macro totals at each step using unrounded values. Sum the macronutrient values exactly as calculated, and do not adjust totals to fit the tolerance range. Any response with totals deviating from ingredient sums or using non-specified data for added ingredients is invalid; recalculate and return exact sums. Limit added ingredients to 100g for protein sources and 100g for carb sources to avoid overcompensation. Follow this adjustment process:
Calculate initial macro totals for user ingredients using external nutritional data (e.g., USDA) for unlisted ingredients and specified data for listed ones; verify sums.
If totals exceed targets, reduce high-calorie/carb ingredients (e.g., oats to 40–60g for inputs like 100g, oils to 1 tbsp) and recalculate sums.
If protein is below target, scale up protein-rich ingredients or add meal-type-appropriate protein (e.g., Greek yogurt or protein powder for breakfast, max 100g; chicken for lunch/dinner); if carbs are below, scale up carb sources or add blueberries (breakfast) or broccoli (lunch/dinner, max 100g). Recalculate sums.
Fine-tune quantities (e.g., adjust oats by 10g) to align totals within 10–20% of goals, prioritizing carbs near {{ $carbs }} and fat near 45g if feasible for high-fat goals, or {{ $calories * 0.35 / 9 }} g for lower-fat goals.
Verify final sums against individual ingredient macros using unrounded calculations, log the exact sums (e.g., 495.8 kcal, 39.85g protein, 59.65g carbs, 19.1g fat), and round only in the final output. Report in the warning field (e.g., "Calculated sums: 495.8 kcal, 39.85g protein, 59.65g carbs, 19.1g fat; rounded to 496 kcal, 40g protein, 60g carbs, 19g fat; matches ingredient total; reduced oats from 100g to 50g, added 100g Greek yogurt").
Provide clear, concise, beginner-friendly cooking instructions. Ensure the meal is balanced, appetizing, and practical, aligning with {{ $meal_type }} and {{ $preferred_cuisine ?? 'any' }}. For breakfast, prioritize fruits (e.g., blueberries, banana) or grains for carbs; for lunch/dinner, prioritize vegetables like broccoli. Reject responses with inappropriate ingredients (e.g., chicken for breakfast unless specified).

Output in JSON format:

"meal": {
"name": Descriptive meal name reflecting {{ $meal_type }}.
"calories": Total calories (integer, rounded, must match ingredient sum).
"protein": Total protein in grams (integer, rounded, must match ingredient sum).
"carbs": Total carbohydrates in grams (integer, rounded, must match ingredient sum).
"fat": Total fat in grams (integer, rounded, must match ingredient sum).
"warning": Detailed explanation of adjustments and summation verification (e.g., "Calculated sums: 495.8 kcal, 39.85g protein, 59.65g carbs, 19.1g fat; rounded to 496 kcal, 40g protein, 60g carbs, 19g fat; matches ingredient total; reduced oats from 100g to 50g").
"ingredients": Array of objects, each with:
"name": Ingredient name.
"quantity": Quantity (e.g., "120g", "1 tbsp").
"calories": Calories (integer, rounded).
"protein": Protein in grams (integer, rounded).
"carbs": Carbs in grams (integer, rounded).
"fat": Fat in grams (integer, rounded).
"instructions": Array of step-by-step instructions. }
Ensure calculations use specified nutritional data for added ingredients and accurate external data for user-provided ingredients, verify macro sums at each step using unrounded values, ensure totals match ingredient sums exactly, and reject any response with deviations or inappropriate ingredients. Detail all adjustments and summation checks in the warning field, explicitly noting unrounded calculated sums vs. rounded output and data source for unlisted ingredients.