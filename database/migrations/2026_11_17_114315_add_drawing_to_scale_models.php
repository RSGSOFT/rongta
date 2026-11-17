<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A model's own line drawing (made with AI from its photos, or uploaded as SVG), shown as its
 * main picture. "drawing" holds the cleaned SVG content (no <svg> wrapper), "drawing_views"
 * the viewBox of the whole drawing and two detail crops: {viewBox, crops: [{viewBox, key}]}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scale_models', function (Blueprint $table) {
            $table->longText('drawing')->nullable()->after('gallery');
            $table->json('drawing_views')->nullable()->after('drawing');
        });
    }

    public function down(): void
    {
        Schema::table('scale_models', function (Blueprint $table) {
            $table->dropColumn(['drawing', 'drawing_views']);
        });
    }
};
