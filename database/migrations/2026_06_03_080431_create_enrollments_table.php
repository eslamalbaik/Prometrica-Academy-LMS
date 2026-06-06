<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            
            // التقدم من 0 إلى 100، مفهرس للاستعلام عن الكورسات المكتملة
            $table->unsignedTinyInteger('progress')->default(0)->index(); 
            
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();
            
            // التأكد من عدم تكرار تسجيل الطالب في نفس الكورس
            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
