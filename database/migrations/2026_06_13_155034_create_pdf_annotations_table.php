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
        Schema::create('pdf_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('digital_product_file_id')->constrained('digital_product_files')->cascadeOnDelete();
            $table->unsignedSmallInteger('page');
            $table->enum('type', ['highlight', 'note']);
            $table->text('selected_text')->nullable();
            $table->text('note')->nullable();
            $table->string('color', 20)->default('yellow');
            // Bounding box stored as percentages relative to page dimensions
            $table->float('x')->nullable();
            $table->float('y')->nullable();
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'digital_product_file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_annotations');
    }
};
