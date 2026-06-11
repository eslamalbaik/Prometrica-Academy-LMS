<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FTR-003: MCQ Image Integration — adds image support to questions and options
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('question_text');
        });

        Schema::table('options', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('option_text');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
