<?php

namespace Tests\Feature;

use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocaleRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        // The schema is migrated once per test run, so seed explicitly (inside the test transaction).
        $this->seed();
    }

    public static function locales(): array
    {
        return ['ru' => ['ru', ''], 'uz' => ['uz', '/uz'], 'en' => ['en', '/en']];
    }

    #[DataProvider('locales')]
    public function test_public_pages_return_200_in_each_locale(string $locale, string $prefix): void
    {
        foreach (['', '/catalog', '/compare', '/model/c1', '/dealers', '/partners', '/knowledge'] as $path) {
            $url = ($prefix.$path) ?: '/';
            $this->get($url)
                ->assertOk()
                ->assertSee('<html lang="'.$locale.'">', false);
        }
    }

    #[DataProvider('locales')]
    public function test_there_is_no_partner_area(string $locale, string $prefix): void
    {
        $this->get($prefix.'/partner')->assertNotFound();
        $this->get($prefix.'/partner/login')->assertNotFound();
        $this->assertFalse(Route::has(Locales::routeName('partner', $locale)));
        $this->assertFalse(Route::has(Locales::routeName('partner.login', $locale)));
    }

    public function test_unknown_model_is_404(): void
    {
        $this->get('/model/nope')->assertNotFound();
        $this->get('/uz/model/nope')->assertNotFound();
    }

    public function test_lroute_builds_locale_urls(): void
    {
        $this->assertSame('/catalog', lroute('catalog', [], 'ru', false));
        $this->assertSame('/uz/catalog', lroute('catalog', [], 'uz', false));
        $this->assertSame('/en/model/c1', lroute('model', ['slug' => 'c1'], 'en', false));
        $this->assertSame('/uz', lroute('home', [], 'uz', false));

        // Without a locale it follows the current one.
        app()->setLocale('en');
        $this->assertSame('/en/dealers', lroute('dealers', [], null, false));
    }

    public function test_locale_url_points_at_the_same_page_in_another_language(): void
    {
        $this->get('/uz/model/c1?tab=specs')
            ->assertOk()
            ->assertSee('href="'.e(url('/model/c1?tab=specs')).'"', false)
            ->assertSee('href="'.e(url('/en/model/c1?tab=specs')).'"', false);
    }
}
