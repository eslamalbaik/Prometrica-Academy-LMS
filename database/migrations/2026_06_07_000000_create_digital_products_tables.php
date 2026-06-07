<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_free')->default(false);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('digital_product_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_product_id')
                ->constrained('digital_products')
                ->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path'); // relative path on the PRIVATE disk
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
        });

        Schema::create('product_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('digital_product_id')
                ->constrained('digital_products')
                ->cascadeOnDelete();
            // No payments table yet (future shell) — keep nullable, no FK constraint.
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('status')->default('completed'); // completed | pending | refunded
            $table->timestamps();

            $table->index(['user_id', 'digital_product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_purchases');
        Schema::dropIfExists('digital_product_files');
        Schema::dropIfExists('digital_products');
    }
};
