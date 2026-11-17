<?php

namespace Tests\Feature\Site;

use App\Livewire\Pages\Home;
use App\Models\Setting;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;

class HomeTest extends SiteTestCase
{
    #[DataProvider('locales')]
    public function test_home_renders_every_section(string $locale, string $prefix): void
    {
        $response = $this->get($prefix ?: '/')->assertOk();

        $response->assertSee(ui('hero.h1', [], $locale))
            ->assertSee('data-readout', false)
            ->assertSee('hero-label', false)
            ->assertSee(ui('ch.h', [], $locale))
            ->assertSee(ui('fam.h', [], $locale))
            ->assertSee(ui('au.h', [], $locale))
            ->assertSee(ui('tr.h', [], $locale))
            ->assertSee(ui('kt.h', [], $locale))
            ->assertSee(ui('ps.h', [], $locale))
            ->assertSee('id="fam-label"', false)
            ->assertSee('id="fam-ai"', false)
            ->assertSee('id="fam-comm"', false)
            ->assertSee('id="au-aurora-s2l-ai"', false);

        $this->assertNoMissingStrings($response->getContent());
    }

    public function test_sections_follow_the_setting_order_and_visibility(): void
    {
        Setting::write('home.sections', [
            ['key' => 'families', 'visible' => true],
            ['key' => 'hero', 'visible' => true],
            ['key' => 'aurora', 'visible' => false],
            ['key' => 'partners', 'visible' => true],
        ]);

        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'class="hero"'), strpos($html, 'class="fam-rows"'));
        $this->assertStringNotContainsString(ui('au.h', [], 'en'), $html);
        $this->assertStringNotContainsString(ui('ch.h', [], 'en'), $html);
        $this->assertStringContainsString(ui('ps.h', [], 'en'), $html);
    }

    public function test_chooser_switches_the_recommendation(): void
    {
        app()->setLocale('en');

        Livewire::test(Home::class)
            ->assertSee('id="mini-rls1000a"', false)
            ->assertDontSee('id="mini-c11"', false)
            ->call('pick', 'market')
            ->assertSee('id="mini-c11"', false)
            ->assertSee('id="mini-c3"', false)
            ->assertDontSee('id="mini-rls1000a"', false)
            ->call('pick', 'supermarket')
            ->assertSee('id="mini-aurora-s2l-ai"', false);
    }

    public function test_family_links_open_the_filtered_catalog(): void
    {
        $this->get('/en')
            ->assertSee('href="'.lroute('catalog', ['family' => 'ai'], 'en').'" id="au-all"', false)
            ->assertSee('href="'.lroute('catalog', ['family' => 'label'], 'en').'" id="fam-label"', false);
    }

    #[DataProvider('locales')]
    public function test_home_shows_no_product_prices(string $locale, string $prefix): void
    {
        $html = $this->get($prefix ?: '/')->getContent();

        foreach (['5 500 000', "5\u{00A0}500\u{00A0}000", '4 625 000', '10 + 1', 'Цена по запросу', 'Price on request', 'Narxi soʻrov'] as $needle) {
            $this->assertStringNotContainsString($needle, $html);
        }
    }
}
