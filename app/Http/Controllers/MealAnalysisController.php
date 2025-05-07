<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MealAnalysisController extends Controller
{
    public function analyzeMeal(Request $request)
    {
        $request->validate([
            'meal_name'   => 'required|string',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.name'     => 'required|string',
            'ingredients.*.quantity' => 'required|string', // e.g. "150g", "0.5kg"
        ]);

        $usdaKey      = config('services.usda.key');
        $usdaEndpoint = 'https://api.nal.usda.gov/fdc/v1/foods/search';

        $breakdown = [];
        $totals = ['calories'=>0,'protein'=>0,'carbs'=>0,'fat'=>0];

        foreach ($request->ingredients as $item) {
            $name = $item['name'];
            $qty  = $item['quantity'];
            $grams = $this->parseGrams($qty);

            // fetch USDA per-100g
            $resp = Http::get($usdaEndpoint, [
                'query'    => $name,
                'pageSize' => 1,
                'api_key'  => $usdaKey,
            ])->json();

            $food = $resp['foods'][0] ?? null;
            Log::info("USDA Response:", ['food : ',$food]);
            if (! $food) {
                return response()->json([
                    'success' => false,
                    'error'   => "No USDA data for {$name}"
                ], 422);
            }

            // pull nutrients
            $per100 = [
                'calories' => $this->nutrient($food, 1008),
                'protein'  => $this->nutrient($food, 1003),
                'carbs'    => $this->nutrient($food, 1005),
                'fat'      => $this->nutrient($food, 1004),
            ];

            // scale by quantity
            $factor = $grams / 100;
            $ingData = [
                'name'       => $name,
                'quantity'   => $qty,
                'calories'   => round($per100['calories'] * $factor),
                'protein'    => round($per100['protein']  * $factor),
                'carbs'      => round($per100['carbs']    * $factor),
                'fat'        => round($per100['fat']      * $factor),
            ];

            // accumulate
            foreach (['calories','protein','carbs','fat'] as $k) {
                $totals[$k] += $ingData[$k];
            }

            $breakdown[] = $ingData;
        }

        return response()->json([
            'success'     => true,
            'meal_name'   => $request->meal_name,
            'ingredients' => $breakdown,
            'totals'      => $totals,
        ]);
    }

    private function parseGrams(string $qty): float
    {
        // supports g, kg
        if (preg_match('/([\d\.]+)\s*kg/i',$qty,$m)) {
            return floatval($m[1]) * 1000;
        }
        if (preg_match('/([\d\.]+)\s*g/i',$qty,$m)) {
            return floatval($m[1]);
        }
        // fallback: treat plain number as grams
        return floatval($qty);
    }

    private function nutrient(array $food, int $nutrientId): float
    {
        foreach ($food['foodNutrients'] as $n) {
            if ($n['nutrientId'] === $nutrientId) {
                return $n['value'];
            }
        }
        return 0;
    }
}
