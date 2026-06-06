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
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('tenant_id')->default('default')->index();
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->string('tenant_id')->default('default')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
