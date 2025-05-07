<?php
namespace App\Services;

class MacroCalculator
{
    public static function calculate(array $user): array
    {
        $factors = [
            'sedentary'   => 1.2,
            'light'       => 1.375,
            'moderate'    => 1.55,
            'active'      => 1.725,
            'very_active' => 1.9,
        ];

        // Mifflin-St Jeor BMR
        $s = $user['gender']==='male' ? 5 : -161;
        $bmr = 10*$user['weight'] + 6.25*$user['height'] - 5*$user['age'] + $s;

        $level  = $user['activity_level'] ?? 'sedentary';
        $factor = $factors[$level] ?? $factors['sedentary'];
        
        $tdee = round($bmr * $factor);
        // adjust for goal
        if($user['goal']==='Lose Weight')      $tdee -= 500;
        if($user['goal']==='Gain Weight')      $tdee += 300;
        // macros split: protein 1.8g per kg, fat 25% cals, rest carbs
        $protein_g = round(1.8 * $user['weight']);
        $protein_cals = $protein_g * 4;
        $fat_cals     = round(0.25 * $tdee);
        $fat_g        = round($fat_cals / 9);
        $carb_cals    = $tdee - ($protein_cals + $fat_cals);
        $carbs_g      = round($carb_cals / 4);
        return [
            'daily_calories' => round($tdee),
            'daily_protein'  => $protein_g,
            'daily_carbs'    => $carbs_g,
            'daily_fat'      => $fat_g,
        ];
    }
}
