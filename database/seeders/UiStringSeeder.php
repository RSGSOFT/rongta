<?php

namespace Database\Seeders;

use App\Models\UiString;
use App\Support\UiStrings;
use Database\Seeders\Concerns\ReadsDesignData;
use Illuminate\Database\Seeder;

/**
 * Interface strings from ui-strings.json. Price-related keys are not imported
 * (project rule: no prices anywhere). Existing rows keep staff edits: only missing
 * keys are created, so re-seeding never overwrites text changed in the admin panel.
 */
class UiStringSeeder extends Seeder
{
    use ReadsDesignData;

    /** Exact keys that are never imported. */
    public const SKIP_KEYS = ['cur', 'fact.price', 'pa.i.no101', 'chat.a.price', 'chat.chip.price'];

    /** Key prefixes that are never imported. "chat.a." are the prototype's scripted bot answers. */
    public const SKIP_PREFIXES = ['price.', 'pa.r.', 'pa.offer.', 'pa.calc.', 'pa.c.', 'chat.a.'];

    /**
     * Prototype notices, sample-data notes and price wording that no page uses. Also removed
     * from existing databases by the 2026_11_17 "remove_prototype_ui_strings" migration.
     */
    public const PROTOTYPE_KEYS = [
        'buy.lead', 'pa.i.terms', 'ftr.note', 'call.note', 'file.proto', 'file.sample', 'file.samplenote',
        'dl.samplenote', 'ph.placeholder', 'pa.demo', 'pa.proto', 'pa.preview', 'pf.proto', 'pf.done.p',
        'kb.sample', 'chat.proto', 'chat.sub',
    ];

    public static function isSkipped(string $key): bool
    {
        if (in_array($key, self::SKIP_KEYS, true) || in_array($key, self::PROTOTYPE_KEYS, true) || str_contains($key, 'price')) {
            return true;
        }
        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function run(): void
    {
        $strings = $this->designData('ui-strings.json')['strings'];

        // "to be confirmed" lives in models.json; pages need it as an interface string too.
        $strings['val.tbc'] = $this->designData('models.json')['toBeConfirmed'];

        foreach ($strings as $key => $text) {
            if (self::isSkipped($key)) {
                continue;
            }
            UiString::firstOrCreate(
                ['key' => $key],
                ['ru' => $text['ru'] ?? null, 'uz' => $text['uz'] ?? null, 'en' => $text['en'] ?? null],
            );
        }

        UiStrings::flush();
    }
}
