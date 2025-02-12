<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // User Registration
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'age' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',
            'weight' => 'nullable|integer|min:1',
            'goal' => 'nullable|string|in:Lose Weight,Gain Weight,Healthy Eating',
            'preferred_cuisine' => 'nullable|string',
            'allergies' => 'nullable|array',
        ]);
    
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'age' => $validatedData['age'],
            'height' => $validatedData['height'],
            'weight' => $validatedData['weight'],
            'goal' => $validatedData['goal'],
            'preferred_cuisine' => $validatedData['preferred_cuisine'],
            'allergies' => $validatedData['allergies'], 
        ]);
    
        // Send verification email
        event(new Registered($user));
    
        return response()->json([
            'message' => 'User registered successfully. Please check your email for verification.',
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
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'age' => 'sometimes|integer|min:1',
            'height' => 'sometimes|integer|min:1',
            'weight' => 'sometimes|integer|min:1',
            'goal' => 'sometimes|string|in:Lose Weight,Gain Weight,Healthy Eating',
            'preferred_cuisine' => 'sometimes|string',
            'allergies' => 'sometimes|array',
        ]);

        $user = $request->user();
        $user->update([
            'name' => $request->name ?? $user->name,
            'age' => $request->age ?? $user->age,
            'height' => $request->height ?? $user->height,
            'weight' => $request->weight ?? $user->weight,
            'goal' => $request->goal ?? $user->goal,
            'preferred_cuisine' => $request->preferred_cuisine ?? $user->preferred_cuisine,
            'allergies' => $request->has('allergies') ? json_encode($request->allergies) : $user->allergies,
        ]);

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
    }

    // Logout User
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
