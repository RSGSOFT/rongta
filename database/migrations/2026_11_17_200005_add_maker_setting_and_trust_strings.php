<?php

use App\Models\Setting;
use App\Models\UiString;
use App\Support\Maker;
use App\Support\UiStrings;
use Database\Seeders\SiteStringSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * The "About the manufacturer" figures move from the trust block template into the "maker"
 * setting (edited under admin settings), seeded with today's values: the stock exchange row
 * is kept but hidden. Adds the interface strings the trust material needs. Existing rows are
 * never overwritten. Fresh databases get the same from SettingsSeeder and SiteStringSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(['key' => Maker::KEY], ['value' => Maker::defaults()]);
        Setting::flush();

        foreach (['tr.open', 'tr.address'] as $key) {
            UiString::firstOrCreate(['key' => $key], SiteStringSeeder::STRINGS[$key]);
        }
        UiStrings::flush();
    }

    public function down(): void
    {
        Setting::where('key', Maker::KEY)->delete();
        UiString::whereIn('key', ['tr.open', 'tr.address'])->delete();
    }
};
