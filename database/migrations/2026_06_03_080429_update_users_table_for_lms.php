<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // إضافة الصلاحيات والفهرسة للبحث السريع
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'student', 'instructor'])
                      ->default('student')
                      ->index()
                      ->after('password');
            }
            
            // Soft Deletes (إذا لم تكن موجودة)
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->dropSoftDeletes();
        });
    }
};
