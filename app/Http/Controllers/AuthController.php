<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

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

            // FTR-001: Invalidate all previous sessions before issuing new token
            $user->tokens()->delete();

            $deviceLabel = substr($request->header('User-Agent', 'Unknown Device'), 0, 120);
            $token = $user->createToken('auth_token', ['*'], now()->addDays(30));
            $token->accessToken->update(['device_label' => $deviceLabel]);
            $plainToken = $token->plainTextToken;

            $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');
            $landingUrl = rtrim(env('LANDING_URL', 'http://localhost:8080'), '/');

            $redirectUrl = match($user->role) {
                'admin' => $frontendUrl . '/dashboards/lms',
                'student' => $landingUrl . '/student/dashboard',
                default => $landingUrl . '/',
            };

            return response()->json([
                'success' => true,
                'token' => $plainToken,
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

        // FTR-001: No prior tokens on fresh registration, but be defensive
        $user->tokens()->delete();
        $plainToken = $user->createToken('auth_token')->plainTextToken;

        $landingUrl = rtrim(env('LANDING_URL', 'http://localhost:8080'), '/');

        return response()->json([
            'success' => true,
            'token' => $plainToken,
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
     * Redirect to Google OAuth consent screen.
     * Google sends the user back to GOOGLE_REDIRECT_URI (frontend callback page).
     */
    public function redirectToGoogle(Request $request)
    {
        $landingUrl = rtrim(env('LANDING_URL', 'http://localhost:8080'), '/');

        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect($landingUrl . '/login?error=google_not_configured');
        }

        $returnTo = $request->query('return_to');

        // Store return_to in session so callback page can forward it
        session(['google_return_to' => $returnTo]);

        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * POST /api/auth/google/exchange
     * Frontend callback page posts the OAuth code here.
     * We exchange it for a Socialite user and return a Sanctum token.
     */
    public function exchangeGoogleCode(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $landingUrl = rtrim(env('LANDING_URL', 'http://localhost:8080'), '/');

        try {
            $driver     = Socialite::driver('google')->stateless();
            $tokenData  = $driver->getAccessTokenResponse($request->input('code'));
            $googleUser = $driver->userFromToken($tokenData['access_token']);
        } catch (\Exception $e) {
            Log::warning('Google OAuth exchange failed: ' . $e->getMessage());
            return response()->json(['message' => 'Google authentication failed. Please try again.'], 401);
        }

        if (empty($googleUser->getEmail())) {
            return response()->json(['message' => 'Could not retrieve email from Google account.'], 422);
        }

        $email = strtolower(trim($googleUser->getEmail()));

        // Find existing user or create a new student account
        $user = \App\Models\User::withTrashed()->where('email', $email)->first();

        if ($user && $user->trashed()) {
            return response()->json(['message' => 'This account has been deactivated.'], 403);
        }

        if (! $user) {
            $user = \App\Models\User::create([
                'name'     => $googleUser->getName() ?: $email,
                'email'    => $email,
                'password' => bcrypt(\Illuminate\Support\Str::random(32)), // random unusable password
                'role'     => 'student',
            ]);
            Log::info("New Google student registered: {$email}");
        }

        // FTR-001: Invalidate all previous sessions on Google OAuth login too
        $user->tokens()->delete();
        $plainToken = $user->createToken('google_oauth')->plainTextToken;

        $redirectUrl = $user->role === 'admin'
            ? rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/dashboards/lms'
            : $landingUrl . '/student/dashboard';

        return response()->json([
            'success'      => true,
            'token'        => $plainToken,
            'redirect_url' => $redirectUrl,
            'user'         => [
                'id'       => $user->id,
                'name'     => $user->name,
                'fullName' => $user->name,
                'role'     => $user->role ?? 'student',
                'email'    => $user->email,
            ],
        ]);
    }
}
