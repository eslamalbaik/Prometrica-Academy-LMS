<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // When true: course is hidden from the individual courses listing
            // and can only be accessed through a bundle subscription.
            $table->boolean('bundle_only')->default(false)->after('include_in_subscription');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('bundle_only');
        });
    }
};
