<?php

namespace Tests\Feature\Site;

use App\Models\Setting;
use PHPUnit\Framework\Attributes\DataProvider;

class ShellTest extends SiteTestCase
{
    #[DataProvider('locales')]
    public function test_shell_renders_on_every_page(string $locale, string $prefix): void
    {
        foreach (['', '/catalog', '/compare', '/model/c1', '/dealers', '/knowledge', '/partner/login'] as $path) {
            $response = $this->get(($prefix.$path) ?: '/')->assertOk();

            $response->assertSee(ui('badge.dist', [], $locale))
                ->assertSee('class="hdr"', false)
                ->assertSee('id="hdr-cta"', false)
                ->assertSee(ui('cta.dealer', [], $locale))
                ->assertSee('class="ftr"', false)
                ->assertSee('class="bar"', false)
                ->assertSee('id="bar-call"', false)
                ->assertSee('id="tray"', false)
                ->assertSee('id="toast"', false)
                ->assertSee(ui('site.ftr.note', [], $locale));
        }
    }

    #[DataProvider('locales')]
    public function test_language_switch_points_at_the_same_page(string $locale, string $prefix): void
    {
        $html = $this->get($prefix.'/model/c1')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.url('/model/c1').'" id="lang-ru"', $html);
        $this->assertStringContainsString('href="'.url('/uz/model/c1').'" id="lang-uz"', $html);
        $this->assertStringContainsString('href="'.url('/en/model/c1').'" id="lang-en"', $html);
        $this->assertMatchesRegularExpression('/id="lang-'.$locale.'"[^>]*aria-current="true"/', $html);
    }

    public function test_nav_marks_the_current_section(): void
    {
        $this->assertMatchesRegularExpression('/id="nav-catalog"\s+aria-current="page"/', $this->get('/catalog')->getContent());
        // Model and compare pages belong to the catalog section.
        $this->assertMatchesRegularExpression('/id="nav-catalog"\s+aria-current="page"/', $this->get('/model/c1')->getContent());
        $this->assertMatchesRegularExpression('/id="bar-catalog"\s+aria-current="page"/', $this->get('/en/compare')->getContent());
        $this->assertDoesNotMatchRegularExpression('/id="nav-catalog"\s+aria-current="page"/', $this->get('/')->getContent());
    }

    #[DataProvider('locales')]
    public function test_placeholder_contacts_are_never_shown(string $locale, string $prefix): void
    {
        $this->assertTrue((bool) setting('contacts.is_placeholder'));

        foreach (['', '/catalog', '/model/c1', '/compare'] as $path) {
            $this->get(($prefix.$path) ?: '/')
                ->assertOk()
                ->assertSee(ui('site.contacts.tbc', [], $locale))
                ->assertDontSee('000-00-00')
                ->assertDontSee('@rongta_uz')
                ->assertDontSee('href="tel:', false);
        }
    }

    public function test_real_contacts_are_shown_once_confirmed(): void
    {
        Setting::write('contacts', ['phone' => '+998 (71) 123-45-67', 'telegram' => '@example_handle', 'is_placeholder' => false]);

        $this->get('/en')
            ->assertOk()
            ->assertSee('+998 (71) 123-45-67')
            ->assertSee('href="tel:+998711234567"', false)
            ->assertSee('https://t.me/example_handle', false)
            ->assertDontSee(ui('site.contacts.tbc', [], 'en'));
    }

    public function test_accent_colour_setting_reaches_the_page(): void
    {
        Setting::write('accent', '#123ABC');

        $this->get('/')->assertOk()->assertSee('--accent:#123ABC', false);
    }
}
