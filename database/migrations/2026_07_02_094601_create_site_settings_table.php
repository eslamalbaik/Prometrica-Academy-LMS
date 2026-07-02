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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            // Homepage sections visibility
            $table->boolean('show_bundles')->default(true);
            $table->boolean('show_programs')->default(false);
            $table->boolean('show_courses')->default(false);
            $table->boolean('show_trust_section')->default(false);
            $table->boolean('show_digital_products')->default(true);
            $table->boolean('show_testimonials')->default(true);
            $table->boolean('show_faq')->default(true);

            // Contact information
            $table->string('contact_whatsapp')->nullable();
            $table->string('contact_telegram')->nullable();
            $table->string('contact_twitter')->nullable();
            $table->string('contact_email')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
