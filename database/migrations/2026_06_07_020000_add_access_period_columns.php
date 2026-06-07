<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-configured access duration in days. NULL = lifetime access.
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedInteger('access_days')->nullable()->after('price');
        });
        Schema::table('digital_products', function (Blueprint $table) {
            $table->unsignedInteger('access_days')->nullable()->after('price');
        });

        // Per-student computed expiry. NULL = never expires (lifetime).
        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('enrolled_at');
        });
        Schema::table('product_purchases', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('courses', fn (Blueprint $t) => $t->dropColumn('access_days'));
        Schema::table('digital_products', fn (Blueprint $t) => $t->dropColumn('access_days'));
        Schema::table('enrollments', fn (Blueprint $t) => $t->dropColumn('expires_at'));
        Schema::table('product_purchases', fn (Blueprint $t) => $t->dropColumn('expires_at'));
    }
};
