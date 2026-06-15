<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bundle_course', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort')->default(0)->after('course_id');

            // 'inherit' = use the bundle-level default
            $table->enum('quiz_visibility', ['inherit', 'all', 'none', 'selected'])
                  ->default('inherit')->after('sort');

            $table->enum('certificate_enabled', ['inherit', 'yes', 'no'])
                  ->default('inherit')->after('quiz_visibility');

            $table->enum('products_visibility', ['inherit', 'all', 'none', 'selected'])
                  ->default('inherit')->after('certificate_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('bundle_course', function (Blueprint $table) {
            $table->dropColumn(['sort', 'quiz_visibility', 'certificate_enabled', 'products_visibility']);
        });
    }
};
