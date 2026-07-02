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
        Schema::table('users', function (Blueprint $table) {
            $table->string('university')->nullable()->after('phone');
            $table->string('specialty')->nullable()->after('university');
            $table->year('graduation_year')->nullable()->after('specialty');
            $table->string('target_exam')->nullable()->after('graduation_year');
            $table->string('city')->nullable()->after('target_exam');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['university', 'specialty', 'graduation_year', 'target_exam', 'city']);
        });
    }
};
