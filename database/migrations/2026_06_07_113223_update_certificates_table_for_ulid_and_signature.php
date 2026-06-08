<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'uuid')) {
                // Drop index first (critical for SQLite in-memory test compatibility)
                try {
                    if (DB::getDriverName() === 'sqlite') {
                        $table->dropUnique('certificates_uuid_unique');
                    } else {
                        $table->dropUnique(['uuid']);
                    }
                } catch (\Exception $e) {
                    // Ignore index drop errors if index doesn't exist
                }
                
                $table->dropColumn('uuid');
            }
        });

        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'ulid')) {
                $table->string('ulid', 26)->unique()->after('course_id');
            }
            if (!Schema::hasColumn('certificates', 'signature')) {
                $table->string('signature', 255)->after('ulid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'ulid')) {
                try {
                    if (DB::getDriverName() === 'sqlite') {
                        $table->dropUnique('certificates_ulid_unique');
                    } else {
                        $table->dropUnique(['ulid']);
                    }
                } catch (\Exception $e) {
                    // Ignore index drop errors
                }
                $table->dropColumn('ulid');
            }
            if (Schema::hasColumn('certificates', 'signature')) {
                $table->dropColumn('signature');
            }
        });

        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'uuid')) {
                $table->uuid('uuid')->unique()->after('course_id');
            }
        });
    }
};
