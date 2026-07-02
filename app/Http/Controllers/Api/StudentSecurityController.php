<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Bundle;
use Illuminate\Http\Request;

class StudentSecurityController extends Controller
{
    public function studentDevices()
    {
        $this->authorize('viewAny', User::class);

        $enrollments = Enrollment::with('user', 'course')
            ->whereNotNull('device_ip')
            ->orderByDesc('last_accessed_at')
            ->get()
            ->map(fn ($enrollment) => [
                'id' => $enrollment->id,
                'student_id' => $enrollment->user_id,
                'student_name' => $enrollment->user->name,
                'student_email' => $enrollment->user->email,
                'device_id' => $enrollment->device_id,
                'device_name' => $enrollment->device_id ? 'Device: ' . substr($enrollment->device_id, 0, 8) : 'Unknown',
                'device_ip' => $enrollment->device_ip,
                'last_accessed_at' => $enrollment->last_accessed_at?->format('Y-m-d H:i:s'),
                'max_devices' => $enrollment->max_devices,
            ]);

        return response()->json(['data' => $enrollments]);
    }

    public function removeDeviceFromStudent(Request $request, User $student)
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'device_id' => 'required|string',
        ]);

        Enrollment::where('user_id', $student->id)
            ->where('device_id', $validated['device_id'])
            ->update([
                'device_id' => null,
                'device_ip' => null,
                'last_accessed_at' => null,
            ]);

        return response()->json(['message' => 'Device removed successfully']);
    }

    public function updateMaxDevices(Request $request, User $student)
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'max_devices' => 'required|integer|min:1|max:5',
        ]);

        $student->enrollments()->update($validated);

        return response()->json(['message' => 'Max devices updated successfully']);
    }

    public function studentBundles()
    {
        $this->authorize('viewAny', User::class);

        $bundleEnrollments = Enrollment::where('bundle_id', '!=', null)
            ->with('user', 'bundle')
            ->get()
            ->map(fn ($enrollment) => [
                'id' => $enrollment->id,
                'student_id' => $enrollment->user_id,
                'student_name' => $enrollment->user->name,
                'student_email' => $enrollment->user->email,
                'bundle_id' => $enrollment->bundle_id,
                'bundle_name' => $enrollment->bundle->name ?? 'Unknown Bundle',
                'is_active' => true,
                'expires_at' => $enrollment->expires_at?->format('Y-m-d'),
            ]);

        return response()->json(['data' => $bundleEnrollments]);
    }

    public function toggleBundleAccess(Request $request, Enrollment $enrollment)
    {
        $this->authorize('update', $enrollment->user);

        $enrollment->is_active = !$enrollment->is_active;
        $enrollment->save();

        return response()->json([
            'message' => $enrollment->is_active ? 'Access granted' : 'Access revoked',
            'data' => $enrollment,
        ]);
    }

    public function assignBundleToStudent(Request $request)
    {
        $this->authorize('create', Enrollment::class);

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'bundle_id' => 'required|exists:bundles,id',
        ]);

        $student = User::findOrFail($validated['student_id']);
        $bundle = Bundle::findOrFail($validated['bundle_id']);

        $enrollment = Enrollment::firstOrCreate(
            [
                'user_id' => $student->id,
                'bundle_id' => $bundle->id,
            ],
            [
                'is_active' => true,
                'enrolled_at' => now(),
                'expires_at' => now()->addDays($bundle->access_days ?? 365),
            ]
        );

        return response()->json([
            'message' => 'Bundle assigned successfully',
            'data' => $enrollment,
        ]);
    }
}
