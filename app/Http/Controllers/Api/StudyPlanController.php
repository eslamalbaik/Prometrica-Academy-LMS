<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyPlan;
use App\Models\StudyPlanTask;
use App\Services\StudyPlanService;
use Illuminate\Http\Request;

class StudyPlanController extends Controller
{
    public function __construct(private StudyPlanService $service) {}

    // ─── Student routes ───────────────────────────────────────────────────────

    /**
     * GET /api/v1/student/study-plans
     * Returns all active study plans for the authenticated student.
     */
    public function index(Request $request)
    {
        $plans = StudyPlan::with([
            'course:id,title,thumbnail',
            'tasks',
        ])
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->get()
            ->map(fn ($plan) => $this->formatPlan($plan));

        return response()->json($plans);
    }

    /**
     * POST /api/v1/study-plans/unsubscribe
     * Opt the student out of weekly digest emails.
     */
    public function unsubscribeEmails(Request $request)
    {
        $request->user()->update(['study_emails_enabled' => false]);
        return response()->json(['message' => 'Unsubscribed from weekly progress emails.']);
    }

    /**
     * POST /api/v1/study-plans/subscribe
     * Opt the student back in to weekly digest emails.
     */
    public function subscribeEmails(Request $request)
    {
        $request->user()->update(['study_emails_enabled' => true]);
        return response()->json(['message' => 'Subscribed to weekly progress emails.']);
    }

    // ─── Admin routes ─────────────────────────────────────────────────────────

    /**
     * GET /api/dashboard/students/{id}/study-plans
     * Returns all study plans for a specific student (admin view).
     */
    public function adminIndex(Request $request, int $userId)
    {
        $plans = StudyPlan::with(['course:id,title', 'tasks'])
            ->where('user_id', $userId)
            ->get()
            ->map(fn ($plan) => $this->formatPlan($plan));

        return response()->json($plans);
    }

    /**
     * PUT /api/dashboard/study-plan-tasks/{task}
     * Admin can edit scheduled date or mark a task complete/incomplete.
     */
    public function updateTask(Request $request, StudyPlanTask $task)
    {
        $validated = $request->validate([
            'scheduled_date' => 'sometimes|date',
            'completed_at'   => 'sometimes|nullable|date',
            'title'          => 'sometimes|string|max:255',
        ]);

        $task->update($validated);
        return response()->json($task);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function formatPlan(StudyPlan $plan): array
    {
        return [
            'id'              => $plan->id,
            'course'          => $plan->course ? [
                'id'        => $plan->course->id,
                'title'     => $plan->course->title,
                'thumbnail' => $plan->course->thumbnail,
            ] : null,
            'start_date'      => $plan->start_date?->toDateString(),
            'end_date'        => $plan->end_date?->toDateString(),
            'status'          => $plan->status,
            'progress'        => $plan->progressPercentage(),
            'completed_tasks' => $plan->completedTasksCount(),
            'total_tasks'     => $plan->totalTasksCount(),
            'tasks'           => $plan->tasks->map(fn ($task) => [
                'id'             => $task->id,
                'title'          => $task->title,
                'type'           => $task->type,
                'scheduled_date' => $task->scheduled_date?->toDateString(),
                'completed_at'   => $task->completed_at?->toISOString(),
                'lesson_id'      => $task->lesson_id,
                'quiz_id'        => $task->quiz_id,
            ])->values(),
        ];
    }
}
