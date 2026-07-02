<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\User;
use Illuminate\Http\Request;

class BundleManagementController extends Controller
{
    public function showBundle(Bundle $bundle)
    {
        $this->authorize('view', $bundle);

        return response()->json([
            'data' => $bundle,
        ]);
    }

    public function getBundleStudents(Bundle $bundle)
    {
        $this->authorize('view', $bundle);

        $students = $bundle->enrollments()
            ->with('user')
            ->get()
            ->map(fn ($enrollment) => [
                'id' => $enrollment->user_id,
                'name' => $enrollment->user->name,
                'email' => $enrollment->user->email,
                'enrolled_at' => $enrollment->enrolled_at?->format('Y-m-d'),
                'expires_at' => $enrollment->expires_at?->format('Y-m-d'),
                'is_active' => $enrollment->is_active,
            ]);

        return response()->json(['data' => $students]);
    }

    public function toggleStudentAccess(Request $request, Bundle $bundle, User $student)
    {
        $this->authorize('update', $bundle);

        $enrollment = $bundle->enrollments()
            ->where('user_id', $student->id)
            ->firstOrFail();

        $enrollment->update(['is_active' => !$enrollment->is_active]);

        return response()->json([
            'message' => 'Access ' . ($enrollment->is_active ? 'granted' : 'revoked'),
            'data' => $enrollment,
        ]);
    }

    public function addStudentsToBundle(Request $request, Bundle $bundle)
    {
        $this->authorize('update', $bundle);

        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
        ]);

        $added = [];
        foreach ($validated['student_ids'] as $studentId) {
            $enrollment = $bundle->enrollments()
                ->firstOrCreate(
                    ['user_id' => $studentId],
                    [
                        'is_active' => true,
                        'enrolled_at' => now(),
                        'expires_at' => now()->addDays($bundle->access_days ?? 365),
                    ]
                );
            $added[] = $enrollment;
        }

        return response()->json([
            'message' => count($added) . ' students added to bundle',
            'data' => $added,
        ]);
    }

    public function removeStudentFromBundle(Request $request, Bundle $bundle, User $student)
    {
        $this->authorize('update', $bundle);

        $bundle->enrollments()
            ->where('user_id', $student->id)
            ->delete();

        return response()->json(['message' => 'Student removed from bundle']);
    }
}
