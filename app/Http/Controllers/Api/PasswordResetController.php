<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\ResetPasswordMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Send a password reset link to the student's email.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $email = strtolower(trim($request->input('email')));
        $user = User::where('email', $email)->first();

        // Generate a secure random reset token
        $token = Str::random(60);

        // Store or update the reset token in the database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        // Construct the frontend URL pointing to Vuexy reset-password route
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');
        $resetUrl = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($email);

        // Queue the reset password email to keep responses fast and non-blocking
        Mail::to($email)->queue(new ResetPasswordMail($resetUrl));

        return response()->json([
            'success' => true,
            'message' => 'A password reset link has been sent to your email.'
        ]);
    }

    /**
     * Reset the user's password using the token.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $email = strtolower(trim($request->input('email')));
        $token = $request->input('token');

        // Check if token exists for the email
        $resetRecord = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$resetRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired password reset request.'
            ], 400);
        }

        // Verify token expiration (60 minutes TTL)
        if (now()->subMinutes(60)->gt($resetRecord->created_at)) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'The password reset link has expired.'
            ], 400);
        }

        // Check token validity
        if (!Hash::check($token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password reset token.'
            ], 400);
        }

        // Update the user's password
        $user = User::where('email', $email)->first();
        $user->update([
            'password' => Hash::make($request->input('password'))
        ]);

        // Invalidate and delete the used token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Your password has been successfully reset.'
        ]);
    }
}
