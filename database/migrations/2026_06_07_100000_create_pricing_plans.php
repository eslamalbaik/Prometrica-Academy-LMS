<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            // Bilingual content
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            // Pricing
            $table->decimal('price', 10, 2)->default(0); // 0 = free
            $table->string('period')->nullable();        // e.g. "سنوياً"
            $table->string('period_en')->nullable();     // e.g. "yearly"
            // Presentation
            $table->string('badge')->nullable();         // e.g. "الأكثر شيوعاً"
            $table->string('badge_en')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('cta_label')->nullable();
            $table->string('cta_label_en')->nullable();
            $table->string('cta_url')->nullable();
            // Grouped feature matrix (bilingual) — see PricingPlan::DEFAULT_FEATURES
            $table->json('features')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_plans');
    }
};
