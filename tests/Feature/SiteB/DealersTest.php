<?php

namespace Tests\Feature\SiteB;

use App\Livewire\Pages\Dealers;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\Region;
use Database\Seeders\SiteBStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DealersTest extends TestCase
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

    protected function realPartner(array $attributes = []): Partner
    {
        return Partner::create($attributes + [
            'kind' => 'reseller',
            'name' => ['ru' => 'Торговый дом Тест', 'uz' => 'Test savdo uyi', 'en' => 'Test Trading House'],
            'region_id' => Region::where('slug', 'samarkand')->value('id'),
            'offers' => ['sales'],
            'phone' => '+998 71 200 00 01',
            'is_placeholder' => false,
            'is_published' => true,
            'sort' => 99,
        ]);
    }

    #[DataProvider('locales')]
    public function test_page_renders_in_each_locale(string $locale, string $prefix): void
    {
        $this->get($prefix.'/dealers')
            ->assertOk()
            ->assertSee(ui('dl.h', [], $locale))
            ->assertSee(ui('dl.official', [], $locale))
            ->assertSee(ui('pf.h', [], $locale))
            ->assertSee('id="partner-form"', false)
            ->assertDontSee('[dl.', false)
            ->assertDontSee('[pf.', false);
    }

    public function test_map_has_a_tile_for_each_of_the_14_regions(): void
    {
        $html = $this->get('/en/dealers')->assertOk()->getContent();

        $this->assertSame(14, Region::count());
        $this->assertSame(14, substr_count($html, 'class="pb-tile'));
        $this->assertSame(1, substr_count($html, 'id="region-select"'));
    }

    public function test_rsg_card_comes_first_and_shows_no_placeholder_phone(): void
    {
        $html = $this->get('/en/dealers')->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'pb-dealer-sample'), strpos($html, 'pb-dealer-official'));
        // The seeded contact is a placeholder, so no number is printed anywhere on the page.
        $this->assertStringNotContainsString('000-00-00', $html);
        $this->assertStringNotContainsString('href="tel:', $html);
        $this->assertStringContainsString(ui('dl.contact.tbc', [], 'en'), $html);
    }

    public function test_placeholder_partners_are_labelled_and_never_show_a_phone(): void
    {
        Partner::query()->update(['phone' => '+998 90 111 22 33']);

        $this->get('/en/dealers')
            ->assertOk()
            ->assertSee(ui('dl.sample', [], 'en'))
            ->assertSee(ui('dl.sample.contact', [], 'en'))
            ->assertDontSee('111 22 33');
    }

    public function test_real_partner_shows_its_phone_and_no_sample_label(): void
    {
        Partner::query()->delete();
        $this->realPartner();

        $this->get('/en/dealers')
            ->assertOk()
            ->assertSee('Test Trading House')
            ->assertSee('href="tel:+998712000001"', false)
            ->assertDontSee(ui('dl.sample', [], 'en'));
    }

    public function test_unpublished_partners_are_hidden(): void
    {
        $this->realPartner(['is_published' => false]);

        $this->get('/en/dealers')->assertOk()->assertDontSee('Test Trading House');
    }

    public function test_tab_and_region_come_from_the_query_string(): void
    {
        $this->realPartner();
        $this->realPartner([
            'kind' => 'integrator',
            'name' => ['ru' => 'Интегратор Тест', 'uz' => 'Test integrator', 'en' => 'Test Integrator Ltd'],
        ]);

        $this->get('/en/dealers?tab=integrator')
            ->assertOk()
            ->assertSee('Test Integrator Ltd')
            ->assertDontSee('Test Trading House');

        $this->get('/en/dealers?region=samarkand')
            ->assertOk()
            ->assertSee('Test Trading House')
            ->assertDontSee('Test Integrator Ltd');

        $this->get('/en/dealers?region=tashkent-city&tab=reseller')
            ->assertOk()
            ->assertDontSee('Test Trading House');

        // Unknown values fall back instead of failing.
        $this->get('/en/dealers?region=nowhere&tab=other')->assertOk()->assertSee('Test Trading House');
    }

    public function test_region_filter_counts_and_empty_state(): void
    {
        Partner::query()->delete();
        $this->realPartner();
        app()->setLocale('en');

        Livewire::test(Dealers::class)
            ->assertSee('Test Trading House')
            ->assertSee(ui('dl.n.res', ['n' => 1]))
            ->call('setRegion', 'samarkand')
            ->assertSet('region', 'samarkand')
            ->assertSee('Test Trading House')
            ->call('setRegion', 'bukhara')
            ->assertDontSee('Test Trading House')
            ->assertSee(ui('dl.empty'))
            ->assertSee(ui('dl.official'))
            ->call('setTab', 'integrator')
            ->assertSet('tab', 'integrator')
            ->assertSee(ui('dl.n.int', ['n' => 0]))
            ->call('setRegion', '')
            ->call('setTab', 'bogus')
            ->assertSet('tab', 'reseller');
    }

    public function test_partner_form_validates(): void
    {
        app()->setLocale('en');

        Livewire::test(Dealers::class)
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
            Livewire::test(Dealers::class)
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

        Livewire::test(Dealers::class)
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
        Livewire::test(Dealers::class)
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
        Livewire::test(Dealers::class)
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

        for ($i = 0; $i < Dealers::REQUESTS_PER_HOUR; $i++) {
            Livewire::test(Dealers::class)
                ->set('company', 'Savdo MChJ '.$i)
                ->set('contact_name', 'Aziz Karimov')
                ->set('phone', '+998901234567')
                ->call('submitRequest')
                ->assertSet('sent', true);
        }

        Livewire::test(Dealers::class)
            ->set('company', 'Savdo MChJ')
            ->set('contact_name', 'Aziz Karimov')
            ->set('phone', '+998901234567')
            ->call('submitRequest')
            ->assertHasErrors(['form'])
            ->assertSee(ui('pf.err.throttle'))
            ->assertSet('sent', false);

        $this->assertSame(Dealers::REQUESTS_PER_HOUR, PartnerRequest::count());
    }

    public function test_no_prices_on_the_page(): void
    {
        foreach (['/dealers', '/uz/dealers', '/en/dealers'] as $url) {
            $html = $this->get($url)->getContent();
            foreach (['4 625 000', '5 500 000', '10 + 1', 'soʻm', ' сум'] as $banned) {
                $this->assertStringNotContainsString($banned, $html, $url.' shows '.$banned);
            }
        }
    }
}
