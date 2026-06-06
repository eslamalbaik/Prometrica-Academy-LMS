<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            
            // ربط الكورس بالمدرب (strict constraints)
            $table->foreignId('instructor_id')
                  ->constrained('users')
                  ->restrictOnDelete(); // نمنع حذف المدرب إذا كان لديه كورسات
            
            $table->string('title');
            $table->string('slug')->unique(); // Unique index automatically created
            $table->text('description')->nullable();
            
            // Optimization: Decimal للأسعار
            $table->decimal('price', 8, 2)->default(0); 
            $table->string('thumbnail')->nullable();
            
            $table->boolean('is_published')->default(false)->index(); // Index للفلترة السريعة
            
            $table->timestamps();
            $table->softDeletes(); // للحماية من الحذف بالخطأ
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
