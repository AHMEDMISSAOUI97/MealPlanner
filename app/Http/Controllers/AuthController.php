<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Socialite\Facades\Socialite;
use App\Services\MacroCalculator;

class AuthController extends Controller
{
    // User Registration
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'gender' => 'nullable|in:male,female',
            'age' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',
            'weight' => 'nullable|integer|min:1',
            'goal' => 'nullable|string|in:Lose Weight,Gain Weight,Healthy Eating',
            'preferred_cuisine' => 'nullable|string',
            'allergies' => 'nullable|array',
            'activity_level' => 'nullable|in:sedentary,light,moderate,active,very_active',
        ]);
    
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'gender' => $validatedData['gender'] ?? null,
            'age' => $validatedData['age'] ?? null,
            'height' => $validatedData['height'] ?? null,
            'weight' => $validatedData['weight'] ?? null,
            'goal' => $validatedData['goal'] ?? null,
            'preferred_cuisine' => $validatedData['preferred_cuisine'] ?? null,
            'allergies' => $validatedData['allergies'] ?? [],
            'activity_level' => $validatedData['activity_level'] ?? 'sedentary',
        ]);

        $macros = MacroCalculator::calculate([
            'gender' => $user->gender,
            'age'    => $user->age,
            'height' => $user->height,
            'weight' => $user->weight,
            'goal'   => $user->goal,
            'activity_level' => $user->activity_level,
        ]);
        $user->update($macros);
    
        // Send verification email
        event(new Registered($user));
    
        return response()->json([
            'message' => 'User registered successfully. Please check your email for verification.',
            'daily_targets' => $macros,
        ], 201);
    }

    // User Login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
    
        $user = User::where('email', $credentials['email'])->first();
    
        if (!$user) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }
    
        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Your email is not verified. Please check your inbox.'], 403);
        }
    
        if (!auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message' => 'Login successful.',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // Get User Profile
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    // Return a specified user by ID
    public function getUserById($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    //Return all the Users
    public function getAllUsers()
    {
        return response()->json(User::all());
    }

    // Update User Profile
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'gender' => 'sometimes|in:male,female',
            'age' => 'sometimes|integer|min:1',
            'height' => 'sometimes|integer|min:1',
            'weight' => 'sometimes|integer|min:1',
            'goal' => 'sometimes|string|in:Lose Weight,Gain Weight,Healthy Eating',
            'preferred_cuisine' => 'sometimes|string',
            'allergies' => 'sometimes|array',
        ]);

        $logout = false;
        $triggerEmailVerification = false;
        $data = [];

        // Track email change
        if ($request->filled('email') && $request->email !== $user->email) {
            $data['email'] = $request->email;
            $data['email_verified_at'] = null;
            $triggerEmailVerification = true;
            $logout = true;
        }

        // Track password change
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
            $logout = true;
        }

        // Other fields
        $data['gender'] = $request->input('gender', $user->gender);
        $data['name'] = $request->input('name', $user->name);
        $data['age'] = $request->input('age', $user->age);
        $data['height'] = $request->input('height', $user->height);
        $data['weight'] = $request->input('weight', $user->weight);
        $data['goal'] = $request->input('goal', $user->goal);
        $data['preferred_cuisine'] = $request->input('preferred_cuisine', $user->preferred_cuisine);
        $data['allergies'] = $request->has('allergies') ? json_encode($request->allergies) : $user->allergies;

        $user->fill($data)->save();

        if ($triggerEmailVerification) {
            event(new Registered($user)); // Triggers email
        }

        if ($logout) {
            $user->tokens()->delete();
            return response()->json([
                'message' => 'Email or password updated. Please verify your email and log in again.'
            ]);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    // Logout User
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
