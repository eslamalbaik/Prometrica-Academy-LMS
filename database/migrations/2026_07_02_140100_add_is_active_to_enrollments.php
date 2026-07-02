<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('max_devices');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
