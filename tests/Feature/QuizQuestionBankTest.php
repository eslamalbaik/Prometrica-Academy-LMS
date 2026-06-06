<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Module;
use App\Models\Course;
use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuizQuestionBankTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for API request simulation
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    /**
     * Test standalone question creation and validation.
     */
    public function test_can_create_standalone_question_with_options(): void
    {
        Sanctum::actingAs($this->adminUser);

        $payload = [
            'question_text' => 'What is the correct dosage of Paracetamol for adults?',
            'options' => [
                ['option_text' => '500mg - 1g', 'is_correct' => true],
                ['option_text' => '5g', 'is_correct' => false],
                ['option_text' => '10g', 'is_correct' => false],
            ]
        ];

        $response = $this->postJson('/api/v1/tenant/questions', $payload, [
            'X-Tenant-ID' => 'tenant-a'
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('question_text', $payload['question_text']);

        $this->assertDatabaseHas('questions', [
            'question_text' => $payload['question_text'],
            'tenant_id' => 'tenant-a',
        ]);

        $this->assertDatabaseHas('options', [
            'option_text' => '500mg - 1g',
            'is_correct' => true,
        ]);
    }

    /**
     * Test question options validation rules.
     */
    public function test_question_creation_requires_at_least_two_options_and_exactly_one_correct(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Scenario 1: Only 1 option
        $payloadSingleOption = [
            'question_text' => 'Invalid Question?',
            'options' => [
                ['option_text' => 'Only Option', 'is_correct' => true]
            ]
        ];

        $response = $this->postJson('/api/v1/tenant/questions', $payloadSingleOption);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options']);

        // Scenario 2: Multiple options but no correct one
        $payloadNoCorrect = [
            'question_text' => 'Invalid Question?',
            'options' => [
                ['option_text' => 'Option 1', 'is_correct' => false],
                ['option_text' => 'Option 2', 'is_correct' => false]
            ]
        ];

        $response = $this->postJson('/api/v1/tenant/questions', $payloadNoCorrect);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options']);

        // Scenario 3: Multiple options with multiple correct ones
        $payloadMultipleCorrect = [
            'question_text' => 'Invalid Question?',
            'options' => [
                ['option_text' => 'Option 1', 'is_correct' => true],
                ['option_text' => 'Option 2', 'is_correct' => true]
            ]
        ];

        $response = $this->postJson('/api/v1/tenant/questions', $payloadMultipleCorrect);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options']);
    }

    /**
     * Test tenant isolation for questions.
     */
    public function test_tenant_isolation_on_questions_bank(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create a question for tenant-a
        $this->postJson('/api/v1/tenant/questions', [
            'question_text' => 'Question in Tenant A',
            'options' => [
                ['option_text' => 'A1', 'is_correct' => true],
                ['option_text' => 'A2', 'is_correct' => false],
            ]
        ], ['X-Tenant-ID' => 'tenant-a'])->assertStatus(201);

        // Create a question for tenant-b
        $this->postJson('/api/v1/tenant/questions', [
            'question_text' => 'Question in Tenant B',
            'options' => [
                ['option_text' => 'B1', 'is_correct' => true],
                ['option_text' => 'B2', 'is_correct' => false],
            ]
        ], ['X-Tenant-ID' => 'tenant-b'])->assertStatus(201);

        // Fetch questions as tenant-a
        $responseA = $this->getJson('/api/v1/tenant/questions', ['X-Tenant-ID' => 'tenant-a']);
        $responseA->assertStatus(200);
        $this->assertCount(1, $responseA->json('data'));
        $this->assertEquals('Question in Tenant A', $responseA->json('data.0.question_text'));

        // Fetch questions as tenant-b
        $responseB = $this->getJson('/api/v1/tenant/questions', ['X-Tenant-ID' => 'tenant-b']);
        $responseB->assertStatus(200);
        $this->assertCount(1, $responseB->json('data'));
        $this->assertEquals('Question in Tenant B', $responseB->json('data.0.question_text'));
    }

    /**
     * Test updating a standalone question.
     */
    public function test_can_update_standalone_question_and_recreate_options(): void
    {
        Sanctum::actingAs($this->adminUser);

        $question = Question::create([
            'question_text' => 'Old Question Text',
            'tenant_id' => 'tenant-a',
        ]);
        $question->options()->create(['option_text' => 'Old Option 1', 'is_correct' => true]);
        $question->options()->create(['option_text' => 'Old Option 2', 'is_correct' => false]);

        $payload = [
            'question_text' => 'Updated Question Text',
            'options' => [
                ['option_text' => 'New Option 1', 'is_correct' => false],
                ['option_text' => 'New Option 2', 'is_correct' => true],
                ['option_text' => 'New Option 3', 'is_correct' => false],
            ]
        ];

        $response = $this->putJson("/api/v1/tenant/questions/{$question->id}", $payload, [
            'X-Tenant-ID' => 'tenant-a'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('question_text', 'Updated Question Text');

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_text' => 'Updated Question Text',
        ]);

        // Verify old options were removed and new ones created
        $this->assertEquals(3, $question->options()->count());
        $this->assertDatabaseMissing('options', ['option_text' => 'Old Option 1']);
        $this->assertDatabaseHas('options', ['option_text' => 'New Option 2', 'is_correct' => true]);
    }

    /**
     * Test deleting a standalone question.
     */
    public function test_can_delete_standalone_question(): void
    {
        Sanctum::actingAs($this->adminUser);

        $question = Question::create([
            'question_text' => 'To be deleted',
            'tenant_id' => 'tenant-a',
        ]);
        $option = $question->options()->create(['option_text' => 'Opt', 'is_correct' => true]);

        $response = $this->deleteJson("/api/v1/tenant/questions/{$question->id}", [], [
            'X-Tenant-ID' => 'tenant-a'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        // Options are cascade deleted if using foreign keys, or removed in db
        $this->assertDatabaseMissing('options', ['id' => $option->id]);
    }

    /**
     * Test syncing standalone questions to a quiz.
     */
    public function test_can_sync_questions_to_quiz_with_order(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create course and module first
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'instructor_id' => $this->adminUser->id
        ]);
        $module = Module::create([
            'title' => 'Test Module',
            'course_id' => $course->id,
            'order' => 1
        ]);

        // Create quiz
        $quiz = Quiz::create([
            'title' => 'Pharmacy Quiz',
            'course_module_id' => $module->id,
            'passing_score' => 80,
            'tenant_id' => 'tenant-a'
        ]);

        // Create 2 standalone questions in Tenant A
        $q1 = Question::create(['question_text' => 'Question 1', 'tenant_id' => 'tenant-a']);
        $q1->options()->create(['option_text' => 'Ans 1', 'is_correct' => true]);
        $q1->options()->create(['option_text' => 'Ans 2', 'is_correct' => false]);

        $q2 = Question::create(['question_text' => 'Question 2', 'tenant_id' => 'tenant-a']);
        $q2->options()->create(['option_text' => 'Ans A', 'is_correct' => true]);
        $q2->options()->create(['option_text' => 'Ans B', 'is_correct' => false]);

        // Sync questions to the quiz
        $response = $this->postJson("/api/v1/tenant/quizzes/{$quiz->id}/sync-questions", [
            'question_ids' => [$q2->id, $q1->id] // Sync in custom order
        ], ['X-Tenant-ID' => 'tenant-a']);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify pivot table entries and ordering
        $this->assertDatabaseHas('quiz_questions', [
            'quiz_id' => $quiz->id,
            'question_id' => $q2->id,
            'order' => 0
        ]);

        $this->assertDatabaseHas('quiz_questions', [
            'quiz_id' => $quiz->id,
            'question_id' => $q1->id,
            'order' => 1
        ]);

        // Verify Eloquent relationship orders correctly
        $quizQuestions = $quiz->fresh()->questions;
        $this->assertCount(2, $quizQuestions);
        $this->assertEquals($q2->id, $quizQuestions[0]->id);
        $this->assertEquals($q1->id, $quizQuestions[1]->id);
    }
}
