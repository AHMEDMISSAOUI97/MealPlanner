<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'ingredients', 'meal_type', 'instructions',
        'calories', 'protein', 'carbs', 'fat', 'images', 'user_id'
    ];

    protected $casts = [
        'ingredients' => 'array',
        'instructions' => 'array',
        'images' => 'array',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
