<?php

use App\Models\UiString;
use App\Support\UiStrings;
use Database\Seeders\SiteStringSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photo descriptions (alt texts) of a model: {stored path: {ru, uz, en}}. A photo without one
 * gets an automatic alt from the model name and type. Also adds "ill.drawing", the alt of a
 * model's own line drawing (an existing row is never overwritten).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scale_models', function (Blueprint $table) {
            $table->json('image_alts')->nullable()->after('gallery');
        });

        UiString::firstOrCreate(['key' => 'ill.drawing'], SiteStringSeeder::STRINGS['ill.drawing']);
        UiStrings::flush();
    }

    public function down(): void
    {
        Schema::table('scale_models', function (Blueprint $table) {
            $table->dropColumn('image_alts');
        });

        UiString::where('key', 'ill.drawing')->delete();
        UiStrings::flush();
    }
};
