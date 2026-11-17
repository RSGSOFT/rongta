<?php

use App\Models\UiString;
use App\Support\UiStrings;
use Database\Seeders\SiteStringSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds "ftr.dist", the footer's own distributor badge text, so it can be edited apart from
 * the top strip's "badge.dist". It starts as a copy of the current badge.dist wording.
 * An existing row is never overwritten. Fresh databases get it from SiteStringSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        $badge = UiString::where('key', 'badge.dist')->first();
        $text = $badge ? $badge->only(['ru', 'uz', 'en']) : SiteStringSeeder::STRINGS['ftr.dist'];

        UiString::firstOrCreate(['key' => 'ftr.dist'], $text);
        UiStrings::flush();
    }

    public function down(): void
    {
        UiString::where('key', 'ftr.dist')->delete();
        UiStrings::flush();
    }
};
