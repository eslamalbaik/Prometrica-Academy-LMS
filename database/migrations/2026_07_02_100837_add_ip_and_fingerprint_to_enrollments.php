<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'device_ip')) {
                $table->string('device_ip')->nullable()->after('device_id');
            }
            if (!Schema::hasColumn('enrollments', 'last_accessed_at')) {
                $table->timestamp('last_accessed_at')->nullable()->after('device_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'device_ip')) {
                $table->dropColumn('device_ip');
            }
            if (Schema::hasColumn('enrollments', 'last_accessed_at')) {
                $table->dropColumn('last_accessed_at');
            }
        });
    }
};
