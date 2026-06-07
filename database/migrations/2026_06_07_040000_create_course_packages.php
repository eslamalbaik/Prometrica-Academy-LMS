<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('name'); // e.g. Basic, Silver, Gold
            $table->decimal('price', 10, 2)->default(0);
            // Feature gating flags. Cast to array on the model.
            $table->json('entitlements')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index('course_id');
        });

        // The student's purchase now points to the package they bought.
        // Nullable + nullOnDelete keeps legacy enrollments (full access) intact.
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('course_package_id')
                ->nullable()
                ->after('course_id')
                ->constrained('course_packages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_package_id');
        });
        Schema::dropIfExists('course_packages');
    }
};
