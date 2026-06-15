<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pdf_annotations', function (Blueprint $table) {
            // Full ScaledPosition from react-pdf-highlighter (supports multi-rect selections)
            $table->json('position')->nullable()->after('color');
            // Remove old flat columns that can't represent multi-line selections
            $table->dropColumn(['x', 'y', 'width', 'height']);
        });
    }

    public function down(): void
    {
        Schema::table('pdf_annotations', function (Blueprint $table) {
            $table->dropColumn('position');
            $table->float('x')->nullable();
            $table->float('y')->nullable();
            $table->float('width')->nullable();
            $table->float('height')->nullable();
        });
    }
};
