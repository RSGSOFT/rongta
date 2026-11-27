<?php

use App\Models\UiString;
use App\Support\UiStrings;
use Database\Seeders\HeroItemSeeder;
use Database\Seeders\SiteStringSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sample products for the home page scale display and label, with the ten starter items and
 * the display's text alternative. "unit_price" is the owner-approved exception to the
 * "no prices" rule (2026-11-23): an illustration of a scale readout, shown without currency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_items', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('brand', 40)->nullable();
            $table->unsignedInteger('weight_g');       // 40 .. 30000
            $table->unsignedInteger('unit_price');     // per kilogram, 10 .. 5000000
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        (new HeroItemSeeder)->run();

        UiString::firstOrCreate(['key' => 'hero.readout.item'], SiteStringSeeder::STRINGS['hero.readout.item']);
        UiStrings::flush();
    }

    public function down(): void
    {
        UiString::where('key', 'hero.readout.item')->delete();
        UiStrings::flush();

        Schema::dropIfExists('hero_items');
    }
};
