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
            $table->integer('age')->nullable();
            $table->integer('height')->nullable(); // cm
            $table->integer('weight')->nullable(); // kg
            $table->string('goal')->nullable(); // Lose weight, gain weight, healthy eating
            $table->string('preferred_cuisine')->nullable();
            $table->json('allergies')->nullable();
            $table->string('provider')->nullable(); // Social login provider
            $table->string('provider_id')->nullable(); // Social login ID
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['age', 'height', 'weight', 'goal', 'preferred_cuisine', 'allergies', 'provider', 'provider_id']);
        });
    }
};
