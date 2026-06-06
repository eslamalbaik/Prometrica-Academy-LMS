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
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index(['user_id', 'quiz_id', 'created_at'], 'attempts_user_quiz_date_idx');
            $table->index(['quiz_id', 'user_id', 'score'], 'attempts_quiz_user_score_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('attempts_user_quiz_date_idx');
            $table->dropIndex('attempts_quiz_user_score_idx');
        });
    }
};
