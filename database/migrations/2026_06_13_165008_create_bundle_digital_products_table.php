<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundle_digital_products', function (Blueprint $table) {
            $table->id();
            // Which bundle this product belongs to
            $table->foreignId('bundle_id')->constrained('bundles')->cascadeOnDelete();
            // The standalone digital product included in the bundle
            $table->foreignId('digital_product_id')
                  ->constrained('digital_products')->cascadeOnDelete();
            // false = explicitly excluded when default_products_visibility = 'selected'
            $table->boolean('is_included')->default(true);
            $table->timestamps();

            $table->unique(['bundle_id', 'digital_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundle_digital_products');
    }
};
