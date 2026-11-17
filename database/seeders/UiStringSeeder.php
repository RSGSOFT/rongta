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

    /**
     * Strings of features the owner removed: the buying guide's cost row, the hero label's
     * currency word, and the partner login and partner area (every "pa." key). Also removed
     * from existing databases by the 2026_11_17_2000xx migrations.
     */
    public const REMOVED_KEYS = ['g.cost', 'lbl.cur', 'ps.login'];

    public const REMOVED_PREFIXES = ['pa.'];

    /**
     * Texts that replace the prototype's wording. The hero readout is a decorative scale display:
     * its captions and description carry no currency. Applied to existing databases by the
     * 2026_11_17_200004 migration.
     */
    public const REWORDED = [
        'ro.unit' => ['ru' => 'Цена за кг', 'uz' => '1 kg narxi', 'en' => 'Price per kg'],
        'ro.total' => ['ru' => 'Сумма', 'uz' => 'Summa', 'en' => 'Total'],
        'hero.readout.alt' => [
            'ru' => 'Табло весов: вес 1,250 кг, цена за килограмм и итоговая сумма',
            'uz' => 'Tarozi tablosi: vazn 1,250 kg, 1 kg narxi va jami summa',
            'en' => 'Scale display: weight 1.250 kg, price per kilogram and total',
        ],
        // The trust block shows only material RSG has supplied: no more placeholder frames.
        'tr.lead' => [
            'ru' => 'Документы и сведения, которые подтверждают официальную поставку и сервис.',
            'uz' => 'Rasmiy yetkazib berish va servisni tasdiqlovchi hujjatlar va maʼlumotlar.',
            'en' => 'Documents and details that confirm official supply and service.',
        ],
        'm.works.note' => [
            'ru' => 'Кассовые программы, с которыми работают весы Rongta.',
            'uz' => 'Rongta tarozilari ishlaydigan kassa dasturlari.',
            'en' => 'POS software that works with Rongta scales.',
        ],
    ];

    public static function isSkipped(string $key): bool
    {
        if (in_array($key, self::SKIP_KEYS, true) || in_array($key, self::PROTOTYPE_KEYS, true) || in_array($key, self::REMOVED_KEYS, true) || str_contains($key, 'price')) {
            return true;
        }
        foreach ([...self::SKIP_PREFIXES, ...self::REMOVED_PREFIXES] as $prefix) {
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
            $text = self::REWORDED[$key] ?? $text;
            UiString::firstOrCreate(
                ['key' => $key],
                ['ru' => $text['ru'] ?? null, 'uz' => $text['uz'] ?? null, 'en' => $text['en'] ?? null],
            );
        }

        UiStrings::flush();
    }
}
