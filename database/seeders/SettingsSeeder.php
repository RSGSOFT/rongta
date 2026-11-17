<?php

namespace Database\Seeders;

use App\Models\Setting;
use Database\Seeders\Concerns\ReadsDesignData;
use Illuminate\Database\Seeder;

/**
 * Site settings. Only missing keys are created, so re-seeding never overwrites
 * values changed in the admin panel.
 */
class SettingsSeeder extends Seeder
{
    use ReadsDesignData;

    public function run(): void
    {
        $sample = $this->designData('sample-data.json');
        $models = $this->designData('models.json');

        $defaults = [
            // Placeholder contact details from the prototype; RSG still has to supply the real ones.
            'contacts' => [
                'phone' => $sample['contacts']['phone'],
                'telegram' => $sample['contacts']['telegram'],
                'is_placeholder' => true,
            ],

            // Brand colour. accent_ink / accent_soft are optional overrides (null = stylesheet default).
            'accent' => '#FF6400',
            'accent_ink' => null,
            'accent_soft' => null,

            // Paths under public/.
            'logo' => [
                'logo' => 'images/rongta-logo.png',
                'symbol' => 'images/rongta-symbol.png',
                'wordmark' => 'images/rongta-wordmark.png',
            ],

            // Home page sections, in display order.
            'home.sections' => [
                ['key' => 'hero', 'visible' => true],
                ['key' => 'chooser', 'visible' => true],
                ['key' => 'families', 'visible' => true],
                ['key' => 'aurora', 'visible' => true],
                ['key' => 'trust', 'visible' => true],
                ['key' => 'knowledge', 'visible' => true],
                ['key' => 'partners', 'visible' => true],
            ],

            // Hero texts are interface strings; these are their ui() keys.
            'hero' => [
                'title_key' => 'hero.h1',
                'lead_key' => 'hero.lead',
                'contact_key' => 'hero.contact',
                'readout_alt_key' => 'hero.readout.alt',
            ],

            // Names of the connection codes used in scale_models.conn (same in every language).
            'connection_names' => $models['connectionNames'],
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        Setting::flush();
    }
}
