<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentManagementController extends Controller
{
    public function getEnrollmentsWithBundles()
    {
        $enrollments = Enrollment::with('user', 'bundle')
            ->where('bundle_id', '!=', null)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($enrollment) => [
                'id' => $enrollment->id,
                'student_id' => $enrollment->user_id,
                'student_name' => $enrollment->user?->name ?? 'Unknown',
                'student_email' => $enrollment->user?->email ?? 'Unknown',
                'bundle_id' => $enrollment->bundle_id,
                'bundle_name' => $enrollment->bundle?->name ?? 'Unknown Bundle',
                'bundle_max_devices' => $enrollment->bundle?->max_devices ?? 1,
                'enrolled_at' => $enrollment->enrolled_at?->format('Y-m-d') ?? 'N/A',
                'expires_at' => $enrollment->expires_at?->format('Y-m-d') ?? 'N/A',
                'max_devices' => $enrollment->max_devices ?? ($enrollment->bundle?->max_devices ?? 1),
                'is_active' => $enrollment->is_active ?? true,
            ]);

        return response()->json(['data' => $enrollments], 200);
    }

    public function updateMaxDevices(Request $request, Enrollment $enrollment)
    {
        $this->authorize('update', $enrollment);

        $validated = $request->validate([
            'max_devices' => 'required|integer|min:1|max:5',
        ]);

        $enrollment->update($validated);

        return response()->json([
            'message' => 'Maximum devices updated successfully',
            'data' => $enrollment,
        ]);
    }
}
