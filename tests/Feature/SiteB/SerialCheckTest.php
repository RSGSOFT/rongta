<?php

namespace Tests\Feature\SiteB;

use App\Livewire\Pages\SerialCheck;
use App\Models\Partner;
use App\Models\ScaleModel;
use App\Models\SerialMovement;
use App\Models\SerialNumber;
use App\Models\Setting;
use App\Support\Trust;
use Database\Seeders\SiteBStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Public serial number check: model and warranty period, nothing about dealers or service history. */
class SerialCheckTest extends TestCase
{
    use RefreshDatabase;

    private ScaleModel $model;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed();
        $this->seed(SiteBStringSeeder::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-24 10:00:00');
        RateLimiter::clear('serial-check:127.0.0.1');
        $this->model = ScaleModel::where('slug', 'c11')->firstOrFail();
    }

    private function scale(string $serial = 'C11-0042', ?string $handedOn = null): SerialNumber
    {
        $scale = SerialNumber::factory()->create(['serial' => $serial, 'scale_model_id' => $this->model->id, 'imported_on' => '2026-09-01']);
        if ($handedOn) {
            $dealer = Partner::create(['kind' => 'reseller', 'name' => ['ru' => 'Секретный дилер', 'uz' => 'Sirli diler', 'en' => 'Secret Dealer'], 'offers' => [], 'is_placeholder' => false, 'is_published' => true, 'sort' => 1]);
            SerialMovement::factory()->create(['serial_number_id' => $scale->id, 'partner_id' => $dealer->id, 'happened_on' => $handedOn]);
        }

        return $scale;
    }

    public static function locales(): array
    {
        return ['ru' => ['ru', ''], 'uz' => ['uz', '/uz'], 'en' => ['en', '/en']];
    }

    #[DataProvider('locales')]
    public function test_page_and_footer_link_exist_in_each_locale(string $locale, string $prefix): void
    {
        $this->get($prefix.'/serial')
            ->assertOk()
            ->assertSee(ui('sn.h', [], $locale))
            ->assertSee(ui('sn.btn', [], $locale))
            ->assertSee('id="sn-serial"', false)
            ->assertDontSee('id="sn-result"', false)
            ->assertDontSee('[sn.', false);

        $this->get($prefix.'/')->assertSee('id="ftr-serial"', false)->assertSee(ui('sn.nav', [], $locale));
    }

    public function test_scale_under_warranty_shows_model_period_and_dates_but_no_dealer(): void
    {
        $this->scale('C11-0042', '2026-10-10');
        app()->setLocale('en');

        Livewire::test(SerialCheck::class)
            ->set('serial', '  c11-0042 ')
            ->call('check')
            ->assertHasNoErrors()
            ->assertSee('C11-0042')
            ->assertSee(ui('sn.official'))
            ->assertSee($this->model->name)
            ->assertSee(ui('sn.w.months.v', ['n' => 12]))
            ->assertSee('10.10.2026')
            ->assertSee('09.10.2027')
            ->assertSee(ui('sn.w.active'))
            ->assertDontSee('Secret Dealer')
            ->assertDontSee('import.csv');
    }

    public function test_expired_and_not_started_warranties_say_so(): void
    {
        $this->scale('C11-OLD', '2025-10-10');
        $this->scale('C11-NEW');
        app()->setLocale('en');

        Livewire::test(SerialCheck::class)->set('serial', 'C11-OLD')->call('check')
            ->assertSee(ui('sn.w.expired'))->assertSee('09.10.2026');

        Livewire::test(SerialCheck::class)->set('serial', 'C11-NEW')->call('check')
            ->assertSee(ui('sn.w.none'))
            ->assertDontSee(ui('sn.w.from'))
            ->assertDontSee(ui('sn.w.active'));
    }

    public function test_unknown_or_empty_number_is_answered_plainly(): void
    {
        app()->setLocale('en');

        Livewire::test(SerialCheck::class)->set('serial', 'NOPE-1')->call('check')
            ->assertSee(ui('sn.none', ['serial' => 'NOPE-1']))
            ->assertDontSee(ui('sn.official'));

        Livewire::test(SerialCheck::class)->set('serial', '   ')->call('check')
            ->assertHasErrors(['serial'])
            ->assertSee(ui('sn.err.required'))
            ->assertDontSee('id="sn-result"', false);
    }

    public function test_warranty_terms_show_only_when_staff_entered_them(): void
    {
        $this->scale('C11-0042', '2026-10-10');
        app()->setLocale('en');

        Livewire::test(SerialCheck::class)->set('serial', 'C11-0042')->call('check')->assertDontSee(ui('sn.terms'));

        Setting::write(Trust::KEY, ['service' => ['text' => ['ru' => 'Условия', 'uz' => 'Shartlar', 'en' => 'Repairs at the RSG service centre'], 'address' => []]]);

        Livewire::test(SerialCheck::class)->set('serial', 'C11-0042')->call('check')
            ->assertSee(ui('sn.terms'))
            ->assertSee('Repairs at the RSG service centre');
    }

    public function test_checks_from_one_address_are_limited_per_hour(): void
    {
        $this->scale('C11-0042', '2026-10-10');
        app()->setLocale('en');

        for ($i = 0; $i < SerialCheck::CHECKS_PER_HOUR; $i++) {
            Livewire::test(SerialCheck::class)->set('serial', 'GUESS-'.$i)->call('check')->assertHasNoErrors();
        }

        Livewire::test(SerialCheck::class)->set('serial', 'C11-0042')->call('check')
            ->assertHasErrors(['serial'])
            ->assertSee(ui('sn.err.throttle'))
            ->assertDontSee(ui('sn.official'));
    }
}
