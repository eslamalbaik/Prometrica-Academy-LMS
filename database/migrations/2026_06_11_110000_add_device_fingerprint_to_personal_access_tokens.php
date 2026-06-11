<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FTR-001: Single-Device Login — adds device tracking columns to Sanctum tokens
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('device_fingerprint')->nullable()->after('name');
            $table->string('device_label')->nullable()->after('device_fingerprint'); // e.g. "Chrome / Windows"
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn(['device_fingerprint', 'device_label']);
        });
    }
};
