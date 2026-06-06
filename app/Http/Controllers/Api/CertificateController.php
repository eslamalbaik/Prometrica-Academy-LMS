<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\CourseProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(private CourseProgressService $progressService) {}

    /**
     * GET /student/certificates
     * قائمة شهادات الطالب المسجّل الدخول
     */
    public function index(Request $request): JsonResponse
    {
        $certificates = Certificate::where('user_id', $request->user()->id)
            ->with('course:id,title,thumbnail,category')
            ->orderByDesc('issued_at')
            ->get();

        return response()->json($certificates);
    }

    /**
     * POST /student/courses/{id}/certificate
     * إصدار شهادة إذا اكتمل الكورس 100%
     */
    public function issue(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // تأكد أن الطالب مسجّل في الكورس
        if (! $user->enrolledCourses()->where('course_id', $id)->exists()) {
            return response()->json(['message' => 'You are not enrolled in this course.'], 403);
        }

        $progress = $this->progressService->calculate($user, $id);

        if ($progress['progress_percentage'] < 100) {
            return response()->json([
                'message' => 'Course not completed yet.',
                'progress_percentage' => $progress['progress_percentage'],
            ], 422);
        }

        // firstOrCreate يمنع الإصدار المزدوج بسبب unique constraint
        $certificate = Certificate::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $id]
        );

        $certificate->load('course:id,title,thumbnail,category');

        return response()->json([
            'message' => $certificate->wasRecentlyCreated ? 'Certificate issued.' : 'Certificate already issued.',
            'certificate' => $certificate,
        ], $certificate->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * GET /certificates/{uuid}/verify  (public)
     * التحقق من صحة الشهادة برابط عام
     */
    public function verify(string $uuid): JsonResponse
    {
        $certificate = Certificate::where('uuid', $uuid)
            ->with([
                'user:id,name',
                'course:id,title,thumbnail,category',
            ])
            ->first();

        if (! $certificate) {
            return response()->json(['valid' => false, 'message' => 'Certificate not found.'], 404);
        }

        return response()->json([
            'valid'       => true,
            'student'     => $certificate->user->name,
            'course'      => $certificate->course->title,
            'category'    => $certificate->course->category,
            'issued_at'   => $certificate->issued_at->toDateString(),
            'uuid'        => $certificate->uuid,
        ]);
    }

    /**
     * GET /dashboard/certificates  (admin)
     * قائمة كل الشهادات الصادرة
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $certificates = Certificate::with([
                'user:id,name,email',
                'course:id,title,category',
            ])
            ->orderByDesc('issued_at')
            ->paginate(20);

        return response()->json($certificates);
    }

    /**
     * GET /dashboard/certificates/stats  (admin)
     * إجمالي الشهادات لكل كورس
     */
    public function adminStats(): JsonResponse
    {
        $stats = Certificate::selectRaw('course_id, count(*) as issued_count')
            ->with('course:id,title,category,thumbnail')
            ->groupBy('course_id')
            ->orderByDesc('issued_count')
            ->get();

        return response()->json($stats);
    }
}
