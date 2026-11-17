<?php

namespace Tests\Feature\SiteB;

use App\Livewire\Pages\Partner\Area;
use App\Livewire\Pages\Partner\Login;
use App\Models\PartnerUser;
use Database\Seeders\SiteBStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PartnerAreaTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed();
        $this->seed(SiteBStringSeeder::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['reseller1', 'integrator1', 'sleeper', 'nobody'] as $login) {
            RateLimiter::clear('partner-login:'.$login.'|127.0.0.1');
        }
    }

    public static function locales(): array
    {
        return ['ru' => ['ru', ''], 'uz' => ['uz', '/uz'], 'en' => ['en', '/en']];
    }

    protected function partnerUser(string $type = 'reseller', array $attributes = []): PartnerUser
    {
        return PartnerUser::create($attributes + [
            'name' => $type === 'reseller' ? 'Aziz Karimov' : 'Dilnoza Yusupova',
            'login' => $type.'1',
            'password' => 'correct-horse',
            'type' => $type,
            'is_active' => true,
        ]);
    }

    #[DataProvider('locales')]
    public function test_login_page_renders_in_each_locale_without_demo_login(string $locale, string $prefix): void
    {
        $this->get($prefix.'/partner/login')
            ->assertOk()
            ->assertSee(ui('pa.h', [], $locale))
            ->assertSee(ui('pa.login', [], $locale))
            ->assertSee(lroute('dealers', [], $locale).'#partner-form', false)
            ->assertDontSee(ui('pa.demo', [], $locale))
            ->assertDontSee(ui('pa.proto', [], $locale))
            ->assertDontSee('[pa.', false);
    }

    #[DataProvider('locales')]
    public function test_guest_is_redirected_to_the_login_page(string $locale, string $prefix): void
    {
        $this->get($prefix.'/partner')->assertRedirect(lroute('partner.login', [], $locale));
    }

    public function test_login_succeeds_with_the_right_password(): void
    {
        $user = $this->partnerUser();

        Livewire::test(Login::class)
            ->set('login', 'reseller1')
            ->set('password', 'correct-horse')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(lroute('partner'));

        $this->assertAuthenticatedAs($user, 'partner');
        $this->assertGuest('web');
    }

    public function test_login_fails_with_a_wrong_password_or_unknown_login(): void
    {
        $this->partnerUser();
        app()->setLocale('en');

        Livewire::test(Login::class)
            ->set('login', 'reseller1')
            ->set('password', 'wrong')
            ->call('authenticate')
            ->assertHasErrors(['login'])
            ->assertSee(ui('pa.err.login'))
            ->assertSet('password', '')
            ->assertNoRedirect();

        Livewire::test(Login::class)
            ->set('login', 'nobody')
            ->set('password', 'correct-horse')
            ->call('authenticate')
            ->assertHasErrors(['login']);

        Livewire::test(Login::class)->call('authenticate')->assertHasErrors(['login']);

        $this->assertGuest('partner');
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $this->partnerUser('reseller', ['login' => 'sleeper', 'is_active' => false]);

        Livewire::test(Login::class)
            ->set('login', 'sleeper')
            ->set('password', 'correct-horse')
            ->call('authenticate')
            ->assertHasErrors(['login'])
            ->assertNoRedirect();

        $this->assertGuest('partner');
    }

    public function test_account_switched_off_while_logged_in_is_sent_back_to_login(): void
    {
        $user = $this->partnerUser();
        $this->actingAs($user, 'partner');
        $user->update(['is_active' => false]);

        $this->get('/partner')->assertRedirect(lroute('partner.login'));
    }

    public function test_login_is_throttled(): void
    {
        $this->partnerUser();
        app()->setLocale('en');

        for ($i = 0; $i < Login::MAX_ATTEMPTS; $i++) {
            Livewire::test(Login::class)
                ->set('login', 'reseller1')
                ->set('password', 'wrong')
                ->call('authenticate')
                ->assertSee(ui('pa.err.login'));
        }

        // Even the right password is refused while the lock is on.
        Livewire::test(Login::class)
            ->set('login', 'reseller1')
            ->set('password', 'correct-horse')
            ->call('authenticate')
            ->assertHasErrors(['login'])
            ->assertDontSee(ui('pa.err.login'))
            ->assertNoRedirect();

        $this->assertGuest('partner');
    }

    public function test_logged_in_partner_skips_the_login_page(): void
    {
        $this->actingAs($this->partnerUser(), 'partner');

        $this->get('/en/partner/login')->assertRedirect(lroute('partner', [], 'en'));
    }

    #[DataProvider('locales')]
    public function test_reseller_sees_only_the_reseller_view(string $locale, string $prefix): void
    {
        $this->actingAs($this->partnerUser('reseller'), 'partner');

        $this->get($prefix.'/partner')
            ->assertOk()
            ->assertSee('id="view-reseller"', false)
            ->assertDontSee('id="view-integrator"', false)
            ->assertSee(ui('pa.res.h', [], $locale))
            ->assertSee(ui('pa.terms.onreq', [], $locale))
            ->assertSee('Aziz Karimov')
            ->assertDontSee(ui('pa.i.h', [], $locale))
            ->assertDontSee(ui('pa.i.sdk', [], $locale))
            ->assertDontSee(ui('pa.i.test', [], $locale))
            ->assertDontSee('[pa.', false);
    }

    #[DataProvider('locales')]
    public function test_integrator_sees_only_the_integrator_view(string $locale, string $prefix): void
    {
        $this->actingAs($this->partnerUser('integrator'), 'partner');

        $response = $this->get($prefix.'/partner')
            ->assertOk()
            ->assertSee('id="view-integrator"', false)
            ->assertDontSee('id="view-reseller"', false)
            ->assertSee(ui('pa.terms.onreq', [], $locale))
            ->assertSee(lroute('knowledge', [], $locale).'#kb-sdk', false)
            ->assertDontSee(ui('pa.res.h', [], $locale))
            ->assertDontSee('[pa.', false);

        foreach (['pa.i.h', 'pa.i.sdk', 'pa.i.test', 'pa.i.contact', 'pa.i.listing', 'pa.i.tech'] as $key) {
            $response->assertSee(ui($key, [], $locale));
        }
    }

    public function test_there_is_no_switch_between_views_and_no_prices(): void
    {
        foreach (['reseller', 'integrator'] as $type) {
            $user = $this->partnerUser($type);
            foreach (['/partner', '/uz/partner', '/en/partner'] as $url) {
                $html = $this->actingAs($user, 'partner')->get($url)->assertOk()->getContent();

                $this->assertStringNotContainsString('ptype-', $html);
                $this->assertStringNotContainsString('seg-tabs', $html);
                $this->assertStringNotContainsString('calc', $html);
                foreach (['4 625 000', '5 500 000', '10 + 1', '10+1', 'soʻm', ' сум', '000-00-00'] as $banned) {
                    $this->assertStringNotContainsString($banned, $html, $type.' '.$url.' shows '.$banned);
                }
            }
        }
    }

    public function test_logout(): void
    {
        $this->actingAs($this->partnerUser(), 'partner');

        Livewire::test(Area::class)
            ->call('logout')
            ->assertRedirect(lroute('partner.login'));

        $this->assertGuest('partner');
        $this->get('/partner')->assertRedirect(lroute('partner.login'));
    }
}
