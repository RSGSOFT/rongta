<?php

namespace Database\Seeders;

use App\Models\HeroItem;
use Illuminate\Database\Seeder;

/**
 * Ten sample products for the home page scale display and label. They are an illustration
 * (no currency is shown) and are only created while the table is empty, so staff edits and
 * deletions survive a re-run. The Russian and Uzbek names need a native-speaker review.
 */
class HeroItemSeeder extends Seeder
{
    /** [name ru, name uz, name en, weight in grams, price per kilogram]. */
    public const ITEMS = [
        ['Яблоки красные сладкие', 'Qizil shirin olma', 'Sweet red apples', 1250, 18000],
        ['Помидоры розовые тепличные', 'Pushti issiqxona pomidori', 'Pink greenhouse tomatoes', 2460, 22000],
        ['Картофель молодой мытый', 'Yuvilgan yangi kartoshka', 'Washed new potatoes', 30000, 6500],
        ['Виноград кишмиш без косточек', 'Urugʻsiz kishmish uzum', 'Seedless kishmish grapes', 765, 28000],
        ['Шоколад молочный с фундуком', 'Yongʻoqli sutli shokolad', 'Milk chocolate with hazelnuts', 40, 180000],
        ['Колбаса варёная докторская', 'Qaynatilgan doktorlik kolbasasi', 'Boiled doctor’s sausage', 485, 95000],
        ['Говядина охлаждённая, вырезка', 'Sovutilgan mol goʻshti, lahm', 'Chilled beef tenderloin', 1830, 140000],
        ['Курага отборная сушёная', 'Saralangan quritilgan oʻrik', 'Selected dried apricots', 350, 120000],
        ['Форель радужная свежая', 'Yangi kamalak forel baligʻi', 'Fresh rainbow trout', 2125, 85000],
        ['Икра осетровая чёрная', 'Osetr qora ikrasi', 'Black sturgeon caviar', 50, 5000000],
    ];

    public function run(): void
    {
        if (HeroItem::query()->exists()) {
            return;
        }

        foreach (self::ITEMS as $position => [$ru, $uz, $en, $weight, $unitPrice]) {
            HeroItem::create([
                'name' => ['ru' => $ru, 'uz' => $uz, 'en' => $en],
                'weight_g' => $weight,
                'unit_price' => $unitPrice,
                'sort' => $position + 1,
                'is_published' => true,
            ]);
        }
    }
}
