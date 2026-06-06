<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add tenant_id to questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->string('tenant_id')->default('default')->index()->after('id');
        });

        // 2. Create quiz_questions pivot table
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0)->index();
            $table->timestamps();

            // Unique index to prevent duplicate relations
            $table->unique(['quiz_id', 'question_id']);
        });

        // 3. Migrate existing quiz-question relationships
        $questions = DB::table('questions')->whereNotNull('quiz_id')->get();
        foreach ($questions as $question) {
            DB::table('quiz_questions')->insert([
                'quiz_id' => $question->quiz_id,
                'question_id' => $question->id,
                'order' => $question->order ?? 0,
                'created_at' => $question->created_at ?? now(),
                'updated_at' => $question->updated_at ?? now(),
            ]);
        }

        // 4. Drop quiz_id column from questions
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']);
            $table->dropColumn('quiz_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add quiz_id to questions
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('quiz_id')->nullable()->constrained('quizzes')->cascadeOnDelete()->after('tenant_id');
        });

        // Restore relationship data
        $relations = DB::table('quiz_questions')->get();
        foreach ($relations as $rel) {
            DB::table('questions')->where('id', $rel->question_id)->update([
                'quiz_id' => $rel->quiz_id,
                'order' => $rel->order,
            ]);
        }

        // Make quiz_id not nullable if needed (since it was constrained)
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedBigInteger('quiz_id')->nullable(false)->change();
        });

        // Drop pivot table and tenant_id column
        Schema::dropIfExists('quiz_questions');

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
