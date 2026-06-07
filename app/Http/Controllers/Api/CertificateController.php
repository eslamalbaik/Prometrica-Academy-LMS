<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseProgressService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(private CourseProgressService $progressService) {}

    /** GET /student/certificates */
    public function index(Request $request): JsonResponse
    {
        $certificates = Certificate::where('user_id', $request->user()->id)
            ->with('course:id,title,thumbnail,category')
            ->orderByDesc('issued_at')
            ->get();

        return response()->json($certificates);
    }

    /** POST /student/courses/{id}/certificate */
    public function issue(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

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

        $certificate = Certificate::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $id]
        );

        $certificate->load('course:id,title,thumbnail,category');

        return response()->json([
            'message'     => $certificate->wasRecentlyCreated ? 'Certificate issued.' : 'Certificate already issued.',
            'certificate' => $certificate,
        ], $certificate->wasRecentlyCreated ? 201 : 200);
    }

    /** GET /certificates/{uuid}/download  (public — returns PDF) */
    public function download(string $uuid)
    {
        $certificate = Certificate::where('uuid', $uuid)
            ->with(['user:id,name', 'course:id,title,category'])
            ->firstOrFail();

        $landingUrl = rtrim(env('LANDING_URL', 'http://localhost:8080'), '/');

        $pdf = Pdf::loadView('certificates.certificate', [
            'student'    => $certificate->user->name,
            'course'     => $certificate->course->title,
            'category'   => $certificate->course->category ?? '',
            'issued_at'  => $certificate->issued_at->format('F j, Y'),
            'uuid'       => strtoupper($certificate->uuid),
            'verify_url' => $landingUrl . '/verify/' . $certificate->uuid,
        ])->setPaper([0, 0, 841.89, 595.28], 'landscape'); // A4 landscape

        $filename = 'certificate-' . strtolower(str_replace(' ', '-', $certificate->course->title)) . '.pdf';

        return $pdf->download($filename);
    }

    /** GET /certificates/{uuid}/verify  (public — JSON) */
    public function verify(string $uuid): JsonResponse
    {
        $certificate = Certificate::where('uuid', $uuid)
            ->with(['user:id,name', 'course:id,title,thumbnail,category'])
            ->first();

        if (! $certificate) {
            return response()->json(['valid' => false, 'message' => 'Certificate not found.'], 404);
        }

        return response()->json([
            'valid'      => true,
            'student'    => $certificate->user->name,
            'course'     => $certificate->course->title,
            'category'   => $certificate->course->category,
            'issued_at'  => $certificate->issued_at->toDateString(),
            'uuid'       => $certificate->uuid,
        ]);
    }

    /** POST /dashboard/certificates/issue  (admin — manually issue) */
    public function adminIssue(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'   => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        $certificate = Certificate::firstOrCreate([
            'user_id'   => $request->input('user_id'),
            'course_id' => $request->input('course_id'),
        ]);

        $certificate->load(['user:id,name,email', 'course:id,title,category']);

        return response()->json([
            'message'     => $certificate->wasRecentlyCreated ? 'Certificate issued.' : 'Certificate already exists.',
            'certificate' => $certificate,
        ], $certificate->wasRecentlyCreated ? 201 : 200);
    }

    /** GET /dashboard/certificates  (admin) */
    public function adminIndex(Request $request): JsonResponse
    {
        $certificates = Certificate::with(['user:id,name,email', 'course:id,title,category'])
            ->orderByDesc('issued_at')
            ->paginate(20);

        return response()->json($certificates);
    }

    /** GET /dashboard/certificates/stats  (admin) */
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
