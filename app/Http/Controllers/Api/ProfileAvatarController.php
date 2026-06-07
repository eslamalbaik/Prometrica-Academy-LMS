<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Avatar upload — shared by students (React) and admins (Vue). Any authenticated
 * user may change their own picture. Images are stored on the PUBLIC disk under
 * avatars/ and the relative path is saved on users.avatar.
 */
class ProfileAvatarController extends Controller
{
    /** POST /api/v1/profile/avatar  (multipart: avatar) */
    public function update(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $user = $request->user();

        // Remove the previous avatar (ignore non-existent).
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Avatar updated successfully.',
            'avatar'  => $path,
            'user'    => $user->fresh(),
        ]);
    }

    /** DELETE /api/v1/profile/avatar */
    public function destroy(Request $request)
    {
        $user = $request->user();
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return response()->json(['message' => 'Avatar removed.', 'user' => $user->fresh()]);
    }
}
