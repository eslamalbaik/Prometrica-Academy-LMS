<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_reviews', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            
            // التقييم من 1 إلى 5
            $table->unsignedTinyInteger('rating');
            $table->text('review')->nullable();
            
            $table->timestamps();
            
            // التأكد من أن الطالب يقيم الكورس مرة واحدة فقط
            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_reviews');
    }
};
