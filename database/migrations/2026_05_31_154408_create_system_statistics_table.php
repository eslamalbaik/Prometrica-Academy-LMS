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
        Schema::create('system_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('total_students')->default(0);
            $table->unsignedInteger('active_courses')->default(0);
            $table->unsignedInteger('total_enrollments')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_statistics');
    }
};
