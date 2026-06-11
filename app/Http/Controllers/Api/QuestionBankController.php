<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use App\Rules\QuestionValidationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'options'       => ['required', 'array', new QuestionValidationRule()],
        ]);

        DB::beginTransaction();
        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('question_images', 'public');
            }

            $question = Question::create([
                'question_text' => $request->input('question_text'),
                'image_path'    => $imagePath,
                'order'         => 0,
            ]);

            foreach ($request->input('options') as $optionData) {
                $question->options()->create([
                    'option_text' => $optionData['option_text'],
                    'is_correct'  => filter_var($optionData['is_correct'], FILTER_VALIDATE_BOOLEAN),
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
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_image'  => 'sometimes|boolean',
            'options'       => ['required', 'array', new QuestionValidationRule()],
        ]);

        DB::beginTransaction();
        try {
            $updates = ['question_text' => $request->input('question_text')];

            if ($request->hasFile('image')) {
                // Delete old image
                if ($question->image_path) {
                    Storage::disk('public')->delete($question->image_path);
                }
                $updates['image_path'] = $request->file('image')->store('question_images', 'public');
            } elseif ($request->boolean('remove_image') && $question->image_path) {
                Storage::disk('public')->delete($question->image_path);
                $updates['image_path'] = null;
            }

            $question->update($updates);

            // Recreate options to keep it clean and robust
            $question->options()->delete();

            foreach ($request->input('options') as $optionData) {
                $question->options()->create([
                    'option_text' => $optionData['option_text'],
                    'is_correct'  => filter_var($optionData['is_correct'], FILTER_VALIDATE_BOOLEAN),
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

        // Delete associated image from storage
        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }

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
