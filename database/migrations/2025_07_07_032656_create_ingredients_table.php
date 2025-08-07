<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Canonical name (e.g., "chicken breast")
            $table->string('display_name')->nullable(); // User-friendly name (e.g., "Chicken Breast, raw, boneless")
            $table->string('fdc_id')->nullable(); // USDA FoodData Central ID for API lookup
            $table->string('source_api')->nullable(); // e.g., 'USDA', 'Edamam', 'Manual'
            $table->decimal('calories_per_100g', 8, 2)->default(0);
            $table->decimal('protein_per_100g', 8, 2)->default(0);
            $table->decimal('fat_per_100g', 8, 2)->default(0);
            $table->decimal('carbs_per_100g', 8, 2)->default(0);
            $table->json('common_units')->nullable(); // JSON array: [{"unit": "cup", "grams": 200}, {"unit": "medium", "grams": 123}]
            $table->json('cuisine_affinities')->nullable(); // JSON array: ["Italian", "French", "Mexican"]
            $table->json('allergens')->nullable(); // JSON array: ["dairy", "gluten", "nuts"]
            $table->string('ingredient_type')->nullable(); // e.g., 'protein_source', 'carb_source', 'vegetable', 'fat', 'fruit', 'dairy', 'grain'
            $table->boolean('is_supplementary')->default(false); // Can be used for adding to balance macros (e.g., 'true' for chicken breast, 'false' for 'salt')
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};