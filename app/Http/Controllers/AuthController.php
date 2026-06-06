<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials['email'] = strtolower(trim($credentials['email']));

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            if ($user->role === 'admin') {
                Log::info("Admin login: {$user->email} at " . now());
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');
            $landingUrl = rtrim(env('LANDING_URL', 'http://localhost:8080'), '/');

            $redirectUrl = match($user->role) {
                'admin' => $frontendUrl . '/dashboards/lms',
                'student' => $landingUrl . '/student/dashboard',
                default => $landingUrl . '/',
            };

            return response()->json([
                'success' => true,
                'token' => $token,
                'redirect_url' => $redirectUrl,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'fullName' => $user->name,
                    'role' => $user->role ?? 'student',
                    'email' => $user->email,
                ],
            ]);
        }

        return response()->json([
            'message' => 'invalid_credentials',
            'errors' => [
                'email' => ['invalid_credentials'],
            ],
        ], 401);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $validated['email'] = strtolower(trim($validated['email']));

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => 'student',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $landingUrl = rtrim(env('LANDING_URL', 'http://localhost:8080'), '/');

        return response()->json([
            'success' => true,
            'token' => $token,
            'redirect_url' => $landingUrl . '/student/dashboard',
            'message' => 'Account created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'fullName' => $user->name,
                'role' => $user->role,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            if ($user->role === 'admin') {
                Log::info("Admin logout: {$user->email} at " . now());
            }
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
        }

        return response()->json(['success' => true, 'message' => 'Logged out successfully']);
    }

    /**
     * Google OAuth — wire Laravel Socialite when credentials are ready.
     * @see https://laravel.com/docs/socialite
     */
    public function redirectToGoogle(Request $request)
    {
        $landingUrl = rtrim(env('LANDING_URL', 'http://localhost:8080'), '/');
        $returnTo = $request->query('return_to', $landingUrl . '/student/dashboard');

        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect($landingUrl . '/login?error=google_not_configured&to=' . urlencode($returnTo));
        }

        return redirect($landingUrl . '/login?error=google_pending&to=' . urlencode($returnTo));
    }

    public function handleGoogleCallback(Request $request)
    {
        $landingUrl = rtrim(env('LANDING_URL', 'http://localhost:8080'), '/');

        if (! config('services.google.client_id')) {
            return redirect($landingUrl . '/login?error=google_not_configured');
        }

        return redirect($landingUrl . '/login?error=google_callback_pending');
    }
}
