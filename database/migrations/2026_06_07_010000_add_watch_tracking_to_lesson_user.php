<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_user', function (Blueprint $table) {
            // Server-accumulated watched seconds (anti-cheat). Incremented by a
            // fixed step per heartbeat — NOT by the client's current_time — so a
            // user cannot mark a lesson complete by seeking to the end.
            $table->unsignedInteger('watched_seconds')->default(0)->after('is_completed');
            // Last known playhead position (for resume), client-reported.
            $table->unsignedInteger('last_position')->default(0)->after('watched_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_user', function (Blueprint $table) {
            $table->dropColumn(['watched_seconds', 'last_position']);
        });
    }
};
