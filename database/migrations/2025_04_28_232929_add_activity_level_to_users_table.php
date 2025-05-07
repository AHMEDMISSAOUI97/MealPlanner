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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('activity_level', [
                'sedentary',      // little or no exercise
                'light',          // light exercise/sports 1–3 days/week
                'moderate',       // moderate 3–5 days/week
                'active',         // hard exercise 6–7 days/week
                'very_active'     // very hard physical job & exercise
            ])->default('sedentary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activity_level');
        });
    }
};
