<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'fdc_id',
        'source_api',
        'calories_per_100g',
        'protein_per_100g',
        'fat_per_100g',
        'carbs_per_100g',
        'common_units',
        'cuisine_affinities',
        'allergens',
        'ingredient_type',
        'is_supplementary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'common_units' => 'array',
        'cuisine_affinities' => 'array',
        'allergens' => 'array',
        'is_supplementary' => 'boolean',
    ];
}