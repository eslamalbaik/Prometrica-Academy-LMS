<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;

use App\Jobs\GenerateCertificateJob;

class CourseProgressService
{
    /**
     * @return array{
     *   progress_percentage: int,
     *   total_items: int,
     *   completed_items: int,
     *   total_lessons: int,
     *   total_quizzes: int,
     *   completed_lessons: int,
     *   passed_quiz_ids: list<int>
     * }
     */
    public function calculate(User $user, int $courseId): array
    {
        $totalLessons = Lesson::whereHas('module', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })->count();

        $totalQuizzes = Quiz::whereHas('module', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })->count();

        $completedLessons = $user->completedLessons()->whereHas('module', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })->count();

        $passedQuizIds = $user->quizAttempts()
            ->where('passed', true)
            ->whereHas('quiz.module', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            })
            ->select('quiz_id')
            ->distinct()
            ->pluck('quiz_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $passedQuizzes = count($passedQuizIds);
        $totalItems = $totalLessons + $totalQuizzes;
        $completedItems = $completedLessons + $passedQuizzes;
        $progressPercentage = $totalItems > 0
            ? (int) round(($completedItems / $totalItems) * 100)
            : 0;

        return [
            'progress_percentage' => $progressPercentage,
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'total_lessons' => $totalLessons,
            'total_quizzes' => $totalQuizzes,
            'completed_lessons' => $completedLessons,
            'passed_quiz_ids' => $passedQuizIds,
        ];
    }

    public function syncEnrollment(User $user, int $courseId): int
    {
        $stats = $this->calculate($user, $courseId);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if ($enrollment) {
            $enrollment->update(['progress' => $stats['progress_percentage']]);
        }

        // إصدار شهادة تلقائياً عند اكتمال الكورس 100%
        // ملاحظة: على اتصال طابور "sync" يُنفَّذ التوليد بشكل متزامن، وقد يفشل
        // (مثلاً تعذّر تشغيل Chrome). يجب ألا يكسر ذلك تسجيل تقدّم الطالب، لذا
        // نلتقط أي استثناء ونسجّله فقط — الشهادة ستُولَّد لاحقاً عند التحميل.
        if ($stats['progress_percentage'] === 100) {
            try {
                $tenantId = method_exists($user, 'tenantId') ? $user->tenantId() : 1;
                GenerateCertificateJob::dispatch($user->id, $courseId, $tenantId);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Auto certificate generation failed on course completion', [
                    'user_id'   => $user->id,
                    'course_id' => $courseId,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return $stats['progress_percentage'];
    }
}
