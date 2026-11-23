<?php

use App\Models\UiString;
use App\Support\UiStrings;
use Database\Seeders\SiteBStringSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds the headings of the "For partners" page to databases that are already seeded.
 * An existing row is never overwritten. Fresh databases get them from SiteBStringSeeder.
 */
return new class extends Migration
{
    private const KEYS = ['pp.h', 'pp.adv.h', 'pp.terms.h'];

    public function up(): void
    {
        foreach (self::KEYS as $key) {
            UiString::firstOrCreate(['key' => $key], SiteBStringSeeder::STRINGS[$key]);
        }
        UiStrings::flush();
    }

    public function down(): void
    {
        UiString::whereIn('key', self::KEYS)->delete();
        UiStrings::flush();
    }
};
