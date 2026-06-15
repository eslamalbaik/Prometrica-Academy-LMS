<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->enum('type', ['fixed', 'flexible', 'subscription'])
                  ->default('fixed')->after('access_days');

            $table->unsignedSmallInteger('max_courses')
                  ->nullable()->after('type');

            $table->boolean('auto_renewal')
                  ->default(false)->after('max_courses');

            $table->enum('default_quiz_visibility', ['all', 'none', 'selected'])
                  ->default('all')->after('auto_renewal');

            $table->boolean('default_certificate_enabled')
                  ->default(true)->after('default_quiz_visibility');

            $table->enum('default_products_visibility', ['all', 'none', 'selected'])
                  ->default('all')->after('default_certificate_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'max_courses', 'auto_renewal',
                'default_quiz_visibility', 'default_certificate_enabled',
                'default_products_visibility',
            ]);
        });
    }
};
