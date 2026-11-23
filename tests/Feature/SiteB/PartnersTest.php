<?php

namespace Tests\Feature\SiteB;

use App\Livewire\Pages\Partners;
use App\Models\PartnerPoint;
use App\Models\PartnerRequest;
use Database\Seeders\SiteBStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PartnersTest extends TestCase
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
        RateLimiter::clear('partner-request:127.0.0.1');
    }

    public static function locales(): array
    {
        return ['ru' => ['ru', ''], 'uz' => ['uz', '/uz'], 'en' => ['en', '/en']];
    }

    #[DataProvider('locales')]
    public function test_page_renders_in_each_locale_with_the_form(string $locale, string $prefix): void
    {
        $html = $this->get($prefix.'/partners')
            ->assertOk()
            ->assertSee(ui('pp.h', [], $locale))
            ->assertSee(ui('pf.h', [], $locale))
            ->assertSee('id="partner-form"', false)
            ->assertDontSee('[pp.', false)
            ->assertDontSee('[pf.', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/id="nav-partner"\s+aria-current="page"/', $html);
    }

    public function test_blocks_stay_hidden_until_staff_publish_a_line(): void
    {
        PartnerPoint::factory()->hidden()->create(['title' => ['ru' => 'Скрыто', 'uz' => 'Yashirin', 'en' => 'Hidden advantage']]);

        $this->get('/en/partners')
            ->assertOk()
            ->assertDontSee('id="partner-advantages"', false)
            ->assertDontSee('id="partner-terms"', false)
            ->assertDontSee('Hidden advantage');
    }

    public function test_published_advantages_and_terms_show_in_their_blocks_in_order(): void
    {
        PartnerPoint::factory()->create(['sort' => 2, 'title' => ['ru' => 'Обучение', 'uz' => 'Oʻqitish', 'en' => 'Staff training']]);
        PartnerPoint::factory()->create(['sort' => 1, 'title' => ['ru' => 'Поддержка', 'uz' => 'Yordam', 'en' => 'Technical support'], 'body' => null]);
        PartnerPoint::factory()->term()->create(['title' => ['ru' => 'Договор', 'uz' => 'Shartnoma', 'en' => 'Signed agreement']]);

        $html = $this->get('/en/partners')->assertOk()
            ->assertSee(ui('pp.adv.h', [], 'en'))
            ->assertSee(ui('pp.terms.h', [], 'en'))
            ->getContent();

        $this->assertLessThan(strpos($html, 'Staff training'), strpos($html, 'Technical support'));
        $this->assertLessThan(strpos($html, 'id="partner-terms"'), strpos($html, 'Staff training'));
        $this->assertLessThan(strpos($html, 'Signed agreement'), strpos($html, 'id="partner-terms"'));

        $this->get('/uz/partners')->assertSee('Shartnoma')->assertDontSee('Signed agreement');
    }

    public function test_only_the_advantages_block_shows_when_there_are_no_terms(): void
    {
        PartnerPoint::factory()->create();

        $this->get('/en/partners')
            ->assertOk()
            ->assertSee('id="partner-advantages"', false)
            ->assertDontSee('id="partner-terms"', false);
    }

    public function test_partner_form_validates(): void
    {
        app()->setLocale('en');

        Livewire::test(Partners::class)
            ->set('company', 'A')
            ->set('contact_name', '1')
            ->set('phone', '+998 90 123')
            ->call('submitRequest')
            ->assertHasErrors(['company', 'contact_name', 'phone'])
            ->assertSee(ui('err.company'))
            ->assertSee(ui('err.name'))
            ->assertSee(ui('err.phone'))
            ->assertSet('sent', false);

        foreach (['+998901234567x', '998901234567', '+7 900 123 45 67', '+998 90 123 45 678'] as $bad) {
            Livewire::test(Partners::class)
                ->set('company', 'Savdo MChJ')
                ->set('contact_name', 'Aziz Karimov')
                ->set('phone', $bad)
                ->call('submitRequest')
                ->assertHasErrors(['phone']);
        }

        $this->assertSame(0, PartnerRequest::count());
    }

    public function test_partner_form_saves_a_request_and_confirms(): void
    {
        app()->setLocale('en');

        Livewire::test(Partners::class)
            ->set('company', '  Savdo MChJ ')
            ->set('contact_name', 'Oʻgʻiloy Karimova-Aliyeva')
            ->set('phone', '+998 (90) 123-45-67')
            ->set('form_region', 'samarkand')
            ->set('type', 'integrator')
            ->set('message', 'We build POS software.')
            ->call('submitRequest')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee(ui('pf.sent.h'))
            ->assertSee('Savdo MChJ')
            ->call('again')
            ->assertSet('sent', false)
            ->assertSet('company', '');

        $request = PartnerRequest::sole();
        $this->assertSame('Savdo MChJ', $request->company);
        $this->assertSame('Oʻgʻiloy Karimova-Aliyeva', $request->contact_name);
        $this->assertSame('+998901234567', $request->phone);
        $this->assertSame('integrator', $request->type);
        $this->assertSame('new', $request->status);
        $this->assertSame('samarkand', $request->region->slug);
        $this->assertSame('We build POS software.', $request->message);
    }

    public function test_request_without_a_region_or_message_is_saved(): void
    {
        Livewire::test(Partners::class)
            ->set('company', 'Savdo MChJ')
            ->set('contact_name', 'Aziz Karimov')
            ->set('phone', '+998901234567')
            ->call('submitRequest')
            ->assertSet('sent', true);

        $request = PartnerRequest::sole();
        $this->assertNull($request->region_id);
        $this->assertNull($request->message);
        $this->assertSame('reseller', $request->type);
    }

    public function test_honeypot_stops_bots_without_saving(): void
    {
        Livewire::test(Partners::class)
            ->set('company', 'Savdo MChJ')
            ->set('contact_name', 'Aziz Karimov')
            ->set('phone', '+998901234567')
            ->set('website', 'http://spam.example')
            ->call('submitRequest')
            ->assertSet('sent', true);

        $this->assertSame(0, PartnerRequest::count());
    }

    public function test_partner_form_is_rate_limited(): void
    {
        app()->setLocale('en');

        for ($i = 0; $i < Partners::REQUESTS_PER_HOUR; $i++) {
            Livewire::test(Partners::class)
                ->set('company', 'Savdo MChJ '.$i)
                ->set('contact_name', 'Aziz Karimov')
                ->set('phone', '+998901234567')
                ->call('submitRequest')
                ->assertSet('sent', true);
        }

        Livewire::test(Partners::class)
            ->set('company', 'Savdo MChJ')
            ->set('contact_name', 'Aziz Karimov')
            ->set('phone', '+998901234567')
            ->call('submitRequest')
            ->assertHasErrors(['form'])
            ->assertSee(ui('pf.err.throttle'))
            ->assertSet('sent', false);

        $this->assertSame(Partners::REQUESTS_PER_HOUR, PartnerRequest::count());
    }

    public function test_no_prices_on_the_page(): void
    {
        foreach (['/partners', '/uz/partners', '/en/partners'] as $url) {
            $html = $this->get($url)->getContent();
            foreach (['4 625 000', '5 500 000', '10 + 1', 'soʻm', ' сум'] as $banned) {
                $this->assertStringNotContainsString($banned, $html, $url.' shows '.$banned);
            }
        }
    }
}
