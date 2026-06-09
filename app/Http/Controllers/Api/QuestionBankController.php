<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use App\Rules\QuestionValidationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionBankController extends Controller
{
    /**
     * Display a listing of standalone questions (Question Bank).
     */
    public function index(Request $request)
    {
        $query = Question::query()->with('options');

        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where('question_text', 'like', "%{$search}%");
        }

        // Return cursor paginated questions to support infinite scrolling smoothly
        $questions = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->cursorPaginate(15);

        return response()->json($questions);
    }

    /**
     * Store a newly created question in the Question Bank.
     */
    public function store(Request $request)
    {
        $request->validate([
            'question_text' => 'required|string',
            'options' => ['required', 'array', new QuestionValidationRule()],
        ]);

        DB::beginTransaction();
        try {
            $question = Question::create([
                'question_text' => $request->input('question_text'),
                'order' => 0 // Standalone question bank order is default 0
            ]);

            foreach ($request->input('options') as $optionData) {
                $question->options()->create([
                    'option_text' => $optionData['option_text'],
                    'is_correct' => filter_var($optionData['is_correct'], FILTER_VALIDATE_BOOLEAN),
                ]);
            }

            DB::commit();
            return response()->json($question->load('options'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create question', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified question in the Question Bank.
     */
    public function update(Request $request, $id)
    {
        $question = Question::findOrFail($id);

        $request->validate([
            'question_text' => 'required|string',
            'options' => ['required', 'array', new QuestionValidationRule()],
        ]);

        DB::beginTransaction();
        try {
            $question->update([
                'question_text' => $request->input('question_text'),
            ]);

            // Recreate options to keep it clean and robust
            $question->options()->delete();

            foreach ($request->input('options') as $optionData) {
                $question->options()->create([
                    'option_text' => $optionData['option_text'],
                    'is_correct' => filter_var($optionData['is_correct'], FILTER_VALIDATE_BOOLEAN),
                ]);
            }

            DB::commit();
            return response()->json($question->load('options'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update question', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified question from the Question Bank.
     */
    public function destroy($id)
    {
        $question = Question::findOrFail($id);
        $question->delete();

        return response()->json(['message' => 'Question deleted successfully']);
    }

    /**
     * Sync questions associated with a specific quiz.
     */
    public function syncQuestions(Request $request, $id)
    {
        $quiz = Quiz::findOrFail($id);

        $request->validate([
            'question_ids'   => 'nullable|array',
            'question_ids.*' => 'integer|exists:questions,id',
        ]);

        DB::beginTransaction();
        try {
            $syncData = [];
            foreach ($request->input('question_ids', []) as $index => $qId) {
                $syncData[$qId] = ['order' => $index];
            }

            $quiz->questions()->sync($syncData);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Quiz questions synchronized successfully.',
                'questions' => $quiz->questions()->with('options')->get()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to synchronize questions', 'error' => $e->getMessage()], 500);
        }
    }
}
