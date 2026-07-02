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
        Schema::create('digital_product_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_product_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_folder_id')->nullable()->constrained('digital_product_folders')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Add folder_id to digital_product_files
        Schema::table('digital_product_files', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->constrained('digital_product_folders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('digital_product_files', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['folder_id']);
            $table->dropColumn('folder_id');
        });
        Schema::dropIfExists('digital_product_folders');
    }
};
