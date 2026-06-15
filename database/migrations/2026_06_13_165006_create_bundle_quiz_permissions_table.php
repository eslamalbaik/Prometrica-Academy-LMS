<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundle_quiz_permissions', function (Blueprint $table) {
            $table->id();
            // Which bundle
            $table->foreignId('bundle_id')->constrained('bundles')->cascadeOnDelete();
            // Which bundle-course row (scopes quiz to a specific course within the bundle)
            $table->unsignedBigInteger('bundle_course_id');
            $table->foreign('bundle_course_id')
                  ->references('id')->on('bundle_course')
                  ->cascadeOnDelete();
            // Which quiz
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            // One permission row per quiz per bundle
            $table->unique(['bundle_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundle_quiz_permissions');
    }
};
