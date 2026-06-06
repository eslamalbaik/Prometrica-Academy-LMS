<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('course_id')
                  ->constrained()
                  ->cascadeOnDelete(); // الحذف التلقائي عند حذف الكورس (hard delete)
            
            $table->string('title');
            $table->unsignedInteger('order')->default(0)->index(); // Index للترتيب المتكرر
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_modules');
    }
};
