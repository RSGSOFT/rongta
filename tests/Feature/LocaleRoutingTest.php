<?php

namespace Tests\Feature;

use App\Models\PartnerUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        foreach (['', '/catalog', '/compare', '/model/c1', '/dealers', '/knowledge', '/partner/login'] as $path) {
            $url = ($prefix.$path) ?: '/';
            $this->get($url)
                ->assertOk()
                ->assertSee('<html lang="'.$locale.'">', false);
        }
    }

    #[DataProvider('locales')]
    public function test_partner_area_needs_a_partner_login(string $locale, string $prefix): void
    {
        $this->get($prefix.'/partner')->assertRedirect(lroute('partner.login', [], $locale));

        $partnerUser = PartnerUser::create(['is_active' => true,
            'name' => 'Test', 'login' => 'test-'.$locale, 'password' => 'secret-secret', 'type' => 'reseller',
        ]);
        $this->actingAs($partnerUser, 'partner')->get($prefix.'/partner')->assertOk();

        $partnerUser->update(['is_active' => false]);
        $this->actingAs($partnerUser, 'partner')->get($prefix.'/partner')->assertRedirect();
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
