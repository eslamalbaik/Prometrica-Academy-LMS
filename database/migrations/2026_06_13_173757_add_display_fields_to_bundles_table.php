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
        Schema::table('bundles', function (Blueprint $table) {
            $table->string('badge')->nullable()->after('image');
            $table->string('badge_en')->nullable()->after('badge');
            $table->boolean('is_featured')->default(false)->after('badge_en');
            $table->string('color')->nullable()->after('is_featured');
            $table->string('cta_label')->nullable()->after('color');
            $table->string('cta_label_en')->nullable()->after('cta_label');
            $table->string('period')->nullable()->after('cta_label_en');
            $table->string('period_en')->nullable()->after('period');
            $table->json('features')->nullable()->after('period_en');
        });
    }

    public function down(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->dropColumn([
                'badge', 'badge_en', 'is_featured', 'color',
                'cta_label', 'cta_label_en', 'period', 'period_en', 'features',
            ]);
        });
    }
};
