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
        Schema::table('courses', function (Blueprint $table) {
            $table->text('short_description')->nullable();
            $table->string('category')->nullable();
            $table->string('difficulty')->nullable();
            $table->string('language')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('keywords')->nullable();
            $table->boolean('is_free')->default(false);
            $table->decimal('discount_price', 8, 2)->nullable();
            $table->boolean('include_in_subscription')->default(false);
            $table->string('status')->default('draft');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'short_description',
                'category',
                'difficulty',
                'language',
                'meta_title',
                'meta_description',
                'keywords',
                'is_free',
                'discount_price',
                'include_in_subscription',
                'status'
            ]);
        });
    }
};
