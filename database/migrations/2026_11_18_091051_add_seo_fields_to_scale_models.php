<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Search result title and description per language {ru, uz, en}; empty means "built from the page". */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scale_models', function (Blueprint $table) {
            $table->json('seo_title')->nullable()->after('intro');
            $table->json('seo_description')->nullable()->after('seo_title');
        });
    }

    public function down(): void
    {
        Schema::table('scale_models', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description']);
        });
    }
};
