<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CourseProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StudentController extends Controller
{
    public function myCourses(Request $request)
    {
        $user = $request->user();
        
        $courses = $user->enrolledCourses()->with([
                'modules' => function ($query) {
                    $query->orderBy('order');
                },
                'modules.lessons' => function ($query) {
                    $query->orderBy('order');
                },
                'modules.quizzes' => function ($query) {
                    $query->orderBy('order');
                },
            ])->get();

        // Compute some stats for the dashboard widgets
        $totalCourses = $courses->count();
        // Placeholder for avg score until quizzes are implemented
        $avgScore = $totalCourses > 0 ? rand(80, 95) : 0; 
        // Sum total duration of enrolled courses as placeholder for watch time
        $totalWatchTime = $courses->sum('total_duration') ?? 0;

        \Illuminate\Support\Facades\Log::info('Student Dashboard Fetch:', [
            'user_id' => $user->id,
            'courses' => $courses->toArray()
        ]);

        return response()->json([
            'stats' => [
                'total_courses' => $totalCourses,
                'avg_score' => $avgScore,
                'watch_time_minutes' => $totalWatchTime
            ],
            'courses' => $courses
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        $courses = $user->enrolledCourses()->with([
                'modules' => function ($query) {
                    $query->orderBy('order');
                },
                'modules.lessons' => function ($query) {
                    $query->orderBy('order');
                },
                'modules.quizzes' => function ($query) {
                    $query->orderBy('order');
                },
            ])->get();

        $totalCourses = $courses->count();
        $avgScore = $totalCourses > 0 ? rand(80, 95) : 0; 
        $totalWatchTime = $courses->sum('total_duration') ?? 0;

        return response()->json([
            'stats' => [
                'total_courses' => $totalCourses,
                'avg_score' => $avgScore,
                'watch_time_minutes' => $totalWatchTime
            ],
            'courses' => $courses
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['new_password'])
        ]);

        return response()->json([
            'message' => 'Password updated successfully'
        ]);
    }

    public function showCourse(Request $request, $id)
    {
        $user = $request->user();
        
        $isAdminOrInstructor = ($user->role === 'admin' || \App\Models\Course::where('id', $id)->where('instructor_id', $user->id)->exists());
        
        $relations = [
            'modules' => function ($query) {
                $query->orderBy('order');
            },
            'modules.lessons' => function ($query) {
                $query->orderBy('order');
            },
            'modules.quizzes' => function ($query) {
                $query->orderBy('order');
            },
            'modules.lessons.comments' => function ($query) {
                $query->with(['user:id,name', 'replies.user:id,name'])->whereNull('parent_id')->orderBy('created_at', 'desc');
            },
            'reviews' => function ($query) {
                $query->with('user:id,name')->where('is_approved', true)->orderBy('created_at', 'desc');
            }
        ];

        if ($isAdminOrInstructor) {
            $course = \App\Models\Course::with($relations)->findOrFail($id);
        } else {
            $course = $user->enrolledCourses()->with($relations)->findOrFail($id);

            // Enforce the access window — deny entry to expired subscriptions.
            $enrollment = \App\Models\Enrollment::where('user_id', $user->id)
                ->where('course_id', $id)
                ->first();
            if ($enrollment && ! $enrollment->isActive()) {
                return response()->json([
                    'message'    => 'Your access to this course has expired.',
                    'expired'    => true,
                    'expires_at' => $enrollment->expires_at,
                ], 403);
            }

            // ── Device-lock check ──────────────────────────────────────────
            if ($enrollment) {
                $incomingDeviceId = $request->header('X-Device-ID');
                if ($incomingDeviceId) {
                    if (empty($enrollment->device_id)) {
                        // First access → register this device
                        $enrollment->update(['device_id' => $incomingDeviceId]);
                    } elseif ($enrollment->device_id !== $incomingDeviceId) {
                        // Different device → deny
                        return response()->json([
                            'device_locked' => true,
                            'message'       => 'هذا الكورس مرتبط بجهاز آخر. تواصل مع الدعم لإلغاء القفل.',
                            'message_en'    => 'This course is locked to another device. Contact support to unlock.',
                        ], 423);
                    }
                }
            }
        }

        $progressService = app(CourseProgressService::class);
        $progress = $progressService->calculate($user, (int) $id);

        $completedLessonIds = $user->completedLessons()
            ->whereHas('module', function ($query) use ($id) {
                $query->where('course_id', $id);
            })
            ->pluck('lessons.id')
            ->map(fn ($lessonId) => (int) $lessonId)
            ->values()
            ->all();

        // Tiered entitlements: tell the frontend exactly which features are
        // unlocked, plus the available packages for the upsell UI.
        $entitlements = app(\App\Services\EntitlementService::class)
            ->resolve($user, (int) $id);
        $packages = \App\Models\CoursePackage::where('course_id', $id)
            ->orderBy('sort')
            ->get(['id', 'name', 'price', 'entitlements']);

        return response()->json([
            'course' => $course,
            'completed_lesson_ids' => $completedLessonIds,
            'passed_quiz_ids' => $progress['passed_quiz_ids'],
            'progress_percentage' => $progress['progress_percentage'],
            'total_items' => $progress['total_items'],
            'completed_items' => $progress['completed_items'],
            'entitlements' => $entitlements,
            'packages' => $packages,
        ]);
    }

    public function completeLesson(Request $request, $id)
    {
        $user = $request->user();
        $lesson = \App\Models\Lesson::with('module.course')->findOrFail($id);
        
        // Mark as completed
        $user->completedLessons()->syncWithoutDetaching([
            $id => ['is_completed' => true, 'completed_at' => now()]
        ]);

        $courseId = $lesson->module->course_id;

        $progressService = app(CourseProgressService::class);
        $progressPercentage = $progressService->syncEnrollment($user, $courseId);

        // Find next lesson
        $allLessons = \App\Models\Lesson::select('lessons.*')
            ->join('course_modules', 'lessons.course_module_id', '=', 'course_modules.id')
            ->where('course_modules.course_id', $courseId)
            ->orderBy('course_modules.order')
            ->orderBy('lessons.order')
            ->get();
            
        $currentIndex = $allLessons->search(function ($item) use ($id) {
            return $item->id == $id;
        });

        $nextLessonId = null;
        if ($currentIndex !== false && isset($allLessons[$currentIndex + 1])) {
            $nextLessonId = $allLessons[$currentIndex + 1]->id;
        }

        $isCourseCompleted = $progressPercentage == 100;

        return response()->json([
            'success' => true,
            'progress_percentage' => $progressPercentage,
            'next_lesson_id' => $nextLessonId,
            'is_course_completed' => $isCourseCompleted,
        ]);
    }

    public function submitReview(Request $request, $id)
    {
        $user = $request->user();
        
        // Ensure user is enrolled
        if (!$user->enrolledCourses()->where('course_id', $id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $review = \App\Models\CourseReview::create([
            'course_id' => $id,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'review' => $validated['comment'],
            'is_approved' => true, // Auto-approve for now
        ]);

        return response()->json(['message' => 'Review submitted successfully', 'review' => $review]);
    }

    public function submitComment(Request $request, $id)
    {
        $user = $request->user();
        $lesson = \App\Models\Lesson::findOrFail($id);

        $validated = $request->validate([
            'body' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:lesson_comments,id',
        ]);

        $comment = \App\Models\LessonComment::create([
            'user_id' => $user->id,
            'lesson_id' => $id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
        ]);

        // Load user relationship
        $comment->load('user:id,name');

        return response()->json([
            'message' => 'Comment submitted successfully.',
            'comment' => $comment,
        ], 201);
    }
}
