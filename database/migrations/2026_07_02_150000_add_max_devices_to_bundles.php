<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            if (!Schema::hasColumn('bundles', 'max_devices')) {
                $table->integer('max_devices')->default(1)->after('max_courses');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            if (Schema::hasColumn('bundles', 'max_devices')) {
                $table->dropColumn('max_devices');
            }
        });
    }
};
