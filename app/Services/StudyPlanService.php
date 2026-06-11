<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StudyPlan;
use App\Models\StudyPlanTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudyPlanService
{
    /**
     * Generate a study plan for a new enrollment.
     * Distributes all lessons and quizzes across the subscription period.
     * Idempotent: if a plan already exists for this enrollment, returns it.
     */
    public function generateForEnrollment(Enrollment $enrollment): ?StudyPlan
    {
        // Idempotency check
        $existing = StudyPlan::where('user_id', $enrollment->user_id)
            ->where('enrollment_id', $enrollment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Load course with ordered curriculum
        $course = Course::with([
            'modules'          => fn ($q) => $q->orderBy('order'),
            'modules.lessons'  => fn ($q) => $q->orderBy('order'),
            'modules.quizzes'  => fn ($q) => $q->orderBy('order'),
        ])->find($enrollment->course_id);

        if (! $course) {
            return null;
        }

        // Build ordered flat list of curriculum items
        $items = [];
        foreach ($course->modules as $module) {
            $combined = collect();
            foreach ($module->lessons as $lesson) {
                $combined->push(['type' => 'lesson', 'id' => $lesson->id, 'title' => $lesson->title, 'order' => $lesson->order]);
            }
            foreach ($module->quizzes as $quiz) {
                $combined->push(['type' => 'quiz', 'id' => $quiz->id, 'title' => $quiz->title, 'order' => $quiz->order]);
            }
            foreach ($combined->sortBy('order') as $item) {
                $items[] = $item;
            }
        }

        if (empty($items)) {
            return null;
        }

        $startDate = $enrollment->enrolled_at
            ? Carbon::parse($enrollment->enrolled_at)
            : now();

        // Default to 90 days if no expiry (lifetime access)
        $endDate = $enrollment->expires_at
            ? Carbon::parse($enrollment->expires_at)
            : $startDate->copy()->addDays(90);

        $durationDays = max(1, (int) $startDate->diffInDays($endDate));
        $itemCount    = count($items);
        $daysPerItem  = $durationDays / $itemCount;

        return DB::transaction(function () use ($enrollment, $course, $items, $startDate, $endDate, $daysPerItem) {
            $plan = StudyPlan::create([
                'user_id'       => $enrollment->user_id,
                'enrollment_id' => $enrollment->id,
                'course_id'     => $course->id,
                'start_date'    => $startDate->toDateString(),
                'end_date'      => $endDate->toDateString(),
                'status'        => 'active',
            ]);

            foreach ($items as $index => $item) {
                $scheduledDate = $startDate->copy()
                    ->addDays((int) floor($index * $daysPerItem))
                    ->toDateString();

                StudyPlanTask::create([
                    'study_plan_id'  => $plan->id,
                    'lesson_id'      => $item['type'] === 'lesson' ? $item['id'] : null,
                    'quiz_id'        => $item['type'] === 'quiz'   ? $item['id'] : null,
                    'title'          => $item['title'],
                    'type'           => $item['type'],
                    'scheduled_date' => $scheduledDate,
                    'order'          => $index,
                ]);
            }

            return $plan;
        });
    }

    /**
     * Automatically mark a lesson task as completed when the student
     * completes the lesson (called from StudentController::completeLesson).
     */
    public function markLessonCompleted(int $userId, int $lessonId): void
    {
        StudyPlanTask::whereHas('studyPlan', fn ($q) => $q
            ->where('user_id', $userId)
            ->where('status', 'active')
        )
            ->where('lesson_id', $lessonId)
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);
    }

    /**
     * Automatically mark a quiz task as completed when the student
     * passes the quiz.
     */
    public function markQuizCompleted(int $userId, int $quizId): void
    {
        StudyPlanTask::whereHas('studyPlan', fn ($q) => $q
            ->where('user_id', $userId)
            ->where('status', 'active')
        )
            ->where('quiz_id', $quizId)
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);
    }

    /**
     * Delete old plan and regenerate when enrollment changes (package upgrade, expiry extension).
     */
    public function recalculate(Enrollment $enrollment): ?StudyPlan
    {
        StudyPlan::where('user_id', $enrollment->user_id)
            ->where('enrollment_id', $enrollment->id)
            ->delete();

        return $this->generateForEnrollment($enrollment);
    }
}
