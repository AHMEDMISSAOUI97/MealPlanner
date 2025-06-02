<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\MealAnalysisController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/analyze-meal', [MealAnalysisController::class, 'analyzeMeal']);

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill(); 

    return response()->json(['message' => 'Email verified! Please log in.']);
})->middleware('signed')->name('verification.verify');

Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/user/{id}', [AuthController::class, 'getUserById']);
    Route::get('/users', [AuthController::class, 'getAllUsers']); 
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/ai-meal/gemini', [AIController::class, 'getMealSuggestion']);
    Route::post('/ai-meal/openai', [AIController::class, 'getMealFromOpenAI']);
    //Route::post('/analyze-meal', [MealAnalysisController::class, 'analyzeMeal']);
    Route::get('/meals', [AuthController::class, 'getUserMeals']);
    Route::post('/favorites/{meal_id}', [AuthController::class, 'toggleFavorite']);
    Route::get('/favorites', [AuthController::class, 'getFavorites']);
});
