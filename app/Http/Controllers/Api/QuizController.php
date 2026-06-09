<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Option;
use App\Models\QuizAttempt;
use App\Services\CourseProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    // ==========================================
    // ADMIN DASHBOARD ROUTES
    // ==========================================
    public function index(Request $request)
    {
        $query = Quiz::with('questions.options', 'module.course');
        if ($request->has('module_id')) {
            $query->where('course_module_id', $request->module_id);
        }
        return response()->json($query->orderBy('order')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_module_id' => 'required|exists:course_modules,id',
            'title' => 'required|string|max:255',
            'passing_score' => 'required|integer|min:0|max:100',
            'order' => 'nullable|integer|min:0',
            'questions' => 'nullable|array',
            'questions.*.question_text' => 'required_with:questions|string',
            'questions.*.options' => 'required_with:questions|array|min:2',
            'questions.*.options.*.option_text' => 'required_with:questions|string',
            'questions.*.options.*.is_correct' => 'required_with:questions|boolean',
        ]);

        DB::beginTransaction();
        try {
            $moduleId = $validated['course_module_id'];
            if (! array_key_exists('order', $validated) || $validated['order'] === null) {
                $maxLesson = Lesson::where('course_module_id', $moduleId)->max('order');
                $maxQuiz = Quiz::where('course_module_id', $moduleId)->max('order');
                $validated['order'] = max($maxLesson ?? -1, $maxQuiz ?? -1) + 1;
            }

            $quiz = Quiz::create([
                'course_module_id' => $moduleId,
                'title' => $validated['title'],
                'passing_score' => $validated['passing_score'],
                'order' => $validated['order'],
            ]);

            foreach ($validated['questions'] as $qIndex => $qData) {
                $question = $quiz->questions()->create([
                    'question_text' => $qData['question_text'],
                    'order' => $qIndex,
                ]);

                foreach ($qData['options'] as $oData) {
                    $question->options()->create([
                        'option_text' => $oData['option_text'],
                        'is_correct' => $oData['is_correct'],
                    ]);
                }
            }
            DB::commit();
            return response()->json(['message' => 'Quiz created successfully', 'quiz' => $quiz->load('questions.options')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create quiz', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $quiz = Quiz::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'passing_score' => 'required|integer|min:0|max:100',
            'order' => 'integer',
            'questions' => 'sometimes|array',
        ]);

        DB::beginTransaction();
        try {
            $quiz->update([
                'title' => $validated['title'],
                'passing_score' => $validated['passing_score'],
                'order' => $validated['order'] ?? $quiz->order,
            ]);

            if (isset($validated['questions'])) {
                // For simplicity in this demo, if questions are provided, we replace them
                // A more robust implementation would sync them based on IDs
                $quiz->questions()->delete();
                foreach ($validated['questions'] as $qIndex => $qData) {
                    $question = $quiz->questions()->create([
                        'question_text' => $qData['question_text'],
                        'order' => $qIndex,
                    ]);

                    foreach ($qData['options'] as $oData) {
                        $question->options()->create([
                            'option_text' => $oData['option_text'],
                            'is_correct' => $oData['is_correct'],
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'Quiz updated successfully', 'quiz' => $quiz->load('questions.options')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update quiz', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $quiz = Quiz::findOrFail($id);
        $quiz->delete();
        return response()->json(['message' => 'Quiz deleted successfully']);
    }

    // ==========================================
    // STUDENT ROUTES
    // ==========================================
    public function showStudentQuiz(Request $request, $id)
    {
        $quiz = Quiz::with(['questions' => function($q) {
            $q->orderBy('order');
        }, 'questions.options', 'module:id,course_id'])->findOrFail($id);

        // ─── Entitlement gate (never trust the frontend) ────────────────────────
        $courseId = $quiz->module?->course_id;
        if ($courseId) {
            $svc = app(\App\Services\EntitlementService::class);
            if (! $svc->allows($request->user(), (int) $courseId, 'has_quizzes')) {
                return response()->json([
                    'error'         => 'upgrade_required',
                    'feature'       => 'has_quizzes',
                    'required_tier' => $svc->requiredTier((int) $courseId, 'has_quizzes') ?? 'Premium',
                ], 403);
            }
        }

        // Security: Hide is_correct from frontend
        $quiz->questions->each(function ($question) {
            $question->options->makeHidden('is_correct');
        });

        // Get latest attempt if any
        $lastAttempt = QuizAttempt::where('user_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'quiz' => $quiz,
            'last_attempt' => $lastAttempt
        ]);
    }

    public function submitStudentQuiz(Request $request, $id)
    {
        $user = $request->user();
        $quiz = Quiz::with('questions.options', 'module.course')->findOrFail($id);

        // Entitlement gate — block grading for tiers without quiz access.
        $courseId = $quiz->module?->course_id;
        if ($courseId) {
            $svc = app(\App\Services\EntitlementService::class);
            if (! $svc->allows($user, (int) $courseId, 'has_quizzes')) {
                return response()->json([
                    'error'         => 'upgrade_required',
                    'feature'       => 'has_quizzes',
                    'required_tier' => $svc->requiredTier((int) $courseId, 'has_quizzes') ?? 'Premium',
                ], 403);
            }
        }

        $validated = $request->validate([
            'answers' => 'required|array', // format: [{question_id: 1, option_id: 2}]
        ]);

        $score = 0;
        $totalQuestions = $quiz->questions->count();
        $results = []; // Detailed results for each question

        // Map correct answers
        $correctOptions = [];
        foreach ($quiz->questions as $question) {
            foreach ($question->options as $option) {
                if ($option->is_correct) {
                    $correctOptions[$question->id] = $option->id;
                }
            }
        }

        $userAnswers = [];
        foreach ($validated['answers'] as $ans) {
            $qId = $ans['question_id'];
            $oId = $ans['option_id'];
            $userAnswers[$qId] = $oId;

            $isCorrect = isset($correctOptions[$qId]) && $correctOptions[$qId] == $oId;
            if ($isCorrect) {
                $score++;
            }

            $results[] = [
                'question_id' => $qId,
                'user_option_id' => $oId,
                'is_correct' => $isCorrect,
                // We only include correct_option_id if they passed (anti-cheating rule)
                'correct_option_id' => null, 
            ];
        }

        $percentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100) : 0;
        $passed = $percentage >= $quiz->passing_score;

        // Anti-Cheating: Populate correct options ONLY if they passed
        if ($passed) {
            foreach ($results as &$res) {
                $res['correct_option_id'] = $correctOptions[$res['question_id']] ?? null;
            }
        }

        // Save Attempt
        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => $percentage,
            'passed' => $passed,
            'answers' => $userAnswers
        ]);

        $courseId = $quiz->module->course_id;
        $progressService = app(CourseProgressService::class);

        $progressStats = $progressService->calculate($user, $courseId);

        if ($passed) {
            $progressService->syncEnrollment($user, $courseId);
        }

        return response()->json([
            'success' => true,
            'passed' => $passed,
            'score' => $percentage,
            'results' => $results,
            'progress_percentage' => $progressStats['progress_percentage'],
            'passed_quiz_ids' => $progressStats['passed_quiz_ids'],
            'completed_items' => $progressStats['completed_items'],
            'total_items' => $progressStats['total_items'],
            'is_course_completed' => $progressStats['progress_percentage'] === 100,
            'message' => $passed ? 'Congratulations, you passed!' : 'You did not pass. Try again.',
        ]);
    }

    public function getQuizSummary(Request $request, $id)
    {
        return \Illuminate\Support\Facades\Cache::flexible('summary:' . $id, [60, 300], function () use ($id) {
            $lock = \Illuminate\Support\Facades\Cache::lock('rebuild:' . $id, 10);
            
            if ($lock->get()) {
                try {
                    return QuizAttempt::query()
                        ->selectRaw('MAX(score) as best_score, user_id, quiz_id, COUNT(*) as attempts_count')
                        ->where('quiz_id', $id)
                        ->groupBy('user_id', 'quiz_id')
                        ->get();
                } finally {
                    $lock->release();
                }
            }

            // Return current cache data instantly without waiting if another worker is running aggregation
            return \Illuminate\Support\Facades\Cache::get('summary:' . $id) ?: collect();
        });
    }

    public function getQuizAttemptsHistory(Request $request)
    {
        $snapshotTime = $request->input('snapshot_time') ?: now()->toDateTimeString();
        
        $query = QuizAttempt::query()
            ->where('created_at', '<=', $snapshotTime);
            
        // If requesting attempts for a specific student and quiz (Attempts Log modal)
        if ($request->has('user_id') && $request->has('quiz_id')) {
            $history = $query->with(['user', 'quiz'])
                ->where('user_id', $request->input('user_id'))
                ->where('quiz_id', $request->input('quiz_id'))
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->get();
                
            return response()->json(['data' => $history]);
        }

        // Otherwise, list unique student + quiz results with the highest score
        $query->whereRaw('quiz_attempts.id = (
            SELECT qa2.id FROM quiz_attempts qa2
            WHERE qa2.user_id = quiz_attempts.user_id 
              AND qa2.quiz_id = quiz_attempts.quiz_id
              AND qa2.created_at <= ?
            ORDER BY qa2.score DESC, qa2.created_at DESC, qa2.id DESC
            LIMIT 1
        )', [$snapshotTime]);

        // Eager load relations
        $query->with(['user', 'quiz']);

        // Apply search query
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uQuery) use ($search) {
                    $uQuery->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('quiz', function ($qQuery) use ($search) {
                    $qQuery->where('title', 'like', "%{$search}%");
                });
            });
        }

        $history = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->cursorPaginate(15);
            
        return response()->json($history);
    }
}
