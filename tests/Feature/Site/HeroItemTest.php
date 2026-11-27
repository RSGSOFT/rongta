<?php

namespace Tests\Feature\Site;

use App\Models\HeroItem;
use Database\Seeders\HeroItemSeeder;

/** The home hero weighs one sample product: the display and the label show the same numbers. */
class HeroItemTest extends SiteTestCase
{
    private function hero(string $url = '/en'): string
    {
        $html = $this->get($url)->assertOk()->getContent();
        $start = strpos($html, '<section class="hero"');

        return substr($html, $start, strpos($html, '</section>', $start) - $start);
    }

    public function test_ten_starter_items_stay_inside_the_scale_range(): void
    {
        $this->assertSame(count(HeroItemSeeder::ITEMS), HeroItem::count());
        $this->assertSame(10, HeroItem::published()->count());
        $this->assertSame(HeroItem::MIN_WEIGHT_G, HeroItem::min('weight_g'));
        $this->assertSame(HeroItem::MAX_WEIGHT_G, HeroItem::max('weight_g'));
        $this->assertGreaterThanOrEqual(HeroItem::MIN_UNIT_PRICE, HeroItem::min('unit_price'));
        $this->assertSame(HeroItem::MAX_UNIT_PRICE, HeroItem::max('unit_price'));

        foreach (HeroItem::all() as $item) {
            foreach (['ru', 'uz', 'en'] as $locale) {
                $this->assertNotSame('', $item->tr('name', $locale));
            }
        }
    }

    public function test_seeder_does_not_bring_back_items_staff_deleted(): void
    {
        HeroItem::query()->where('sort', '>', 1)->delete();

        $this->seed(HeroItemSeeder::class);

        $this->assertSame(1, HeroItem::count());
    }

    public function test_display_and_label_show_the_published_item_without_currency(): void
    {
        HeroItem::query()->delete();
        HeroItem::factory()->create([
            'name' => ['ru' => 'Форель свежая', 'uz' => 'Yangi forel', 'en' => 'Fresh trout'],
            'brand' => 'Test Brand',
            'weight_g' => 2125,
            'unit_price' => 85000,
        ]);

        $hero = $this->hero('/uz');

        $this->assertStringContainsString('data-weight="2125" data-unit="85000"', $hero);
        $this->assertStringContainsString('<span class="brand">Test Brand</span>', $hero);
        $this->assertStringContainsString('<b>Yangi forel</b>', $hero);
        $this->assertStringContainsString('<dd>2.125</dd>', $hero);
        $this->assertStringContainsString("<dd>85\u{00A0}000</dd>", $hero);
        $this->assertStringContainsString("<div class=\"sum num\">180\u{00A0}625</div>", $hero);
        $this->assertStringContainsString("Yangi forel, vazn 2.125 kg, 1 kg narxi 85\u{00A0}000, jami 180\u{00A0}625", $hero);
        $this->assertDoesNotMatchRegularExpression("/сум(?!\\p{L})|so[ʻ'’]m|UZS|\\[hero\\./ui", $hero);
    }

    public function test_largest_weight_and_price_fit_the_display_windows(): void
    {
        $item = HeroItem::factory()->make(['weight_g' => HeroItem::MAX_WEIGHT_G, 'unit_price' => HeroItem::MAX_UNIT_PRICE]);

        $this->assertSame(150000000, $item->total());
        $this->assertSame(['weight' => '30.000', 'unit' => '5000000', 'total' => '150000000'], $item->readout());

        $small = HeroItem::factory()->make(['weight_g' => HeroItem::MIN_WEIGHT_G, 'unit_price' => 180000]);
        $this->assertSame(['weight' => ' 0.040', 'unit' => ' 180000', 'total' => '     7200'], $small->readout());
    }

    public function test_item_without_a_brand_prints_the_default_brand_line(): void
    {
        HeroItem::query()->delete();
        HeroItem::factory()->create(['brand' => null]);

        $this->assertStringContainsString('<span class="brand">'.HeroItem::DEFAULT_BRAND.'</span>', $this->hero());
    }

    public function test_hidden_items_are_never_picked_and_the_original_label_is_the_fallback(): void
    {
        HeroItem::query()->delete();
        HeroItem::factory()->hidden()->create(['name' => ['ru' => 'Скрытый', 'uz' => 'Yashirin', 'en' => 'Hidden item']]);

        $hero = $this->hero();

        $this->assertStringNotContainsString('Hidden item', $hero);
        $this->assertStringContainsString('<b>'.ui('lbl.product', [], 'en').'</b>', $hero);
        $this->assertStringContainsString("<div class=\"sum num\">15\u{00A0}000</div>", $hero);
        $this->assertStringContainsString(e(ui('hero.readout.alt', [], 'en')), $hero);
    }

    public function test_each_page_load_can_show_a_different_item(): void
    {
        $seen = [];
        for ($load = 0; $load < 40 && count($seen) < 2; $load++) {
            preg_match('/data-weight="(\d+)"/', $this->hero(), $match);
            $seen[$match[1]] = true;
        }

        $this->assertGreaterThan(1, count($seen), 'Forty page loads always showed the same item.');
    }
}
