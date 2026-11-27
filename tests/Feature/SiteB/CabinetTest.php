<?php

namespace Tests\Feature\SiteB;

use App\Livewire\Cabinet;
use App\Models\CabinetAccount;
use App\Models\Partner;
use App\Models\ScaleModel;
use App\Models\SerialDetailRequest;
use App\Models\SerialMovement;
use App\Models\SerialNumber;
use App\Models\ServiceCase;
use App\Models\User;
use Database\Seeders\SiteBStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Partner cabinet (owner decision 2026-11-25): self-registration, admin approval, a dealer
 * section for partners that sell and a service section for partners that service.
 */
class CabinetTest extends TestCase
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
        $this->travelTo('2026-11-25 10:00:00');
        $this->model = ScaleModel::where('slug', 'c11')->firstOrFail();
        app()->setLocale('en');
    }

    /** @param list<string> $offers */
    private function partner(string $name, array $offers): Partner
    {
        return Partner::create(['kind' => 'reseller', 'name' => ['ru' => $name, 'uz' => $name, 'en' => $name], 'offers' => $offers, 'is_placeholder' => false, 'is_published' => true, 'sort' => 1]);
    }

    /** @param list<string> $offers */
    private function account(string $company = 'Torg Servis', array $offers = ['sales', 'service']): CabinetAccount
    {
        return CabinetAccount::factory()->approved($this->partner($company, $offers))->create(['company' => $company]);
    }

    private function scale(string $serial, ?Partner $dealer = null, string $handedOn = '2026-10-10'): SerialNumber
    {
        $scale = SerialNumber::factory()->create(['serial' => $serial, 'scale_model_id' => $this->model->id, 'imported_on' => '2026-09-01']);
        if ($dealer) {
            SerialMovement::factory()->create(['serial_number_id' => $scale->id, 'partner_id' => $dealer->id, 'happened_on' => $handedOn]);
        }

        return $scale;
    }

    public static function locales(): array
    {
        return ['ru' => ['ru', ''], 'uz' => ['uz', '/uz'], 'en' => ['en', '/en']];
    }

    #[DataProvider('locales')]
    public function test_login_and_registration_pages_exist_and_guests_are_sent_to_login(string $locale, string $prefix): void
    {
        $this->get($prefix.'/cabinet/login')->assertOk()->assertSee(ui('cab.login.h', [], $locale))->assertSee('id="cab-login"', false)->assertDontSee('[cab.', false);
        $this->get($prefix.'/cabinet/register')->assertOk()->assertSee(ui('cab.reg.h', [], $locale))->assertSee('id="cab-register"', false)->assertDontSee('[cab.', false);
        $this->get($prefix.'/cabinet')->assertRedirect($prefix.'/cabinet/login');
        $this->get($prefix.'/')->assertSee('id="ftr-cabinet"', false)->assertSee(ui('cab.nav', [], $locale));
    }

    public function test_registration_is_saved_as_pending_and_cannot_log_in_until_approved(): void
    {
        Livewire::test(Cabinet\Register::class)
            ->set('company', ' Torg Servis ')
            ->set('contact_name', 'Aziz Karimov')
            ->set('phone', '+998 90 123-45-67')
            ->set('email', ' Aziz@Torg.UZ ')
            ->set('password', 'secret-pass')
            ->set('password_confirmation', 'secret-pass')
            ->set('service', true)
            ->call('register')
            ->assertHasNoErrors()
            ->assertSee(ui('cab.reg.done.h'))
            ->assertSee('aziz@torg.uz');

        $account = CabinetAccount::sole();
        $this->assertSame(
            ['Torg Servis', 'Aziz Karimov', '+998901234567', 'aziz@torg.uz', false, true, 'en', 'pending', null],
            [$account->company, $account->contact_name, $account->phone, $account->email, $account->wants_sales, $account->wants_service, $account->locale, $account->status, $account->partner_id],
        );
        $this->assertTrue(Hash::check('secret-pass', $account->password));

        Livewire::test(Cabinet\Login::class)
            ->set('email', 'aziz@torg.uz')->set('password', 'secret-pass')
            ->call('login')
            ->assertHasErrors(['email'])
            ->assertSee(ui('cab.login.pending'))
            ->assertNoRedirect();
        $this->assertTrue(Auth::guard('cabinet')->guest());
    }

    public function test_registration_refuses_incomplete_or_repeated_details(): void
    {
        CabinetAccount::factory()->create(['email' => 'taken@torg.uz']);
        $filled = fn () => Livewire::test(Cabinet\Register::class)
            ->set('company', 'Torg')->set('contact_name', 'Aziz Karimov')->set('phone', '+998901234567')
            ->set('email', 'new@torg.uz')->set('password', 'secret-pass')->set('password_confirmation', 'secret-pass')->set('sales', true);

        $filled()->set('sales', false)->call('register')->assertHasErrors(['roles'])->assertSee(ui('cab.reg.err.roles'));
        $filled()->set('password_confirmation', 'other-pass')->call('register')->assertHasErrors(['password'])->assertSee(ui('cab.reg.err.confirm'));
        $filled()->set('password', 'short')->set('password_confirmation', 'short')->call('register')->assertHasErrors(['password'])->assertSee(ui('cab.reg.err.password'));
        $filled()->set('email', 'TAKEN@torg.uz')->call('register')->assertHasErrors(['email'])->assertSee(ui('cab.reg.err.taken'));
        $filled()->set('phone', '12345')->call('register')->assertHasErrors(['phone']);
        $filled()->set('website', 'http://spam.example')->call('register')->assertHasNoErrors();

        $this->assertSame(1, CabinetAccount::count());
    }

    public function test_registrations_from_one_address_are_limited_per_hour(): void
    {
        for ($i = 0; $i <= Cabinet\Register::REGISTRATIONS_PER_HOUR; $i++) {
            $page = Livewire::test(Cabinet\Register::class)
                ->set('company', 'Torg')->set('contact_name', 'Aziz Karimov')->set('phone', '+998901234567')
                ->set('email', "n{$i}@torg.uz")->set('password', 'secret-pass')->set('password_confirmation', 'secret-pass')->set('sales', true)
                ->call('register');
        }

        $page->assertHasErrors(['form'])->assertSee(ui('cab.reg.err.throttle'));
        $this->assertSame(Cabinet\Register::REGISTRATIONS_PER_HOUR, CabinetAccount::count());
    }

    public function test_approved_account_logs_in_to_the_cabinet_but_never_to_the_admin_panel(): void
    {
        $account = $this->account();
        $account->update(['email' => 'aziz@torg.uz', 'password' => 'secret-pass']);

        Livewire::test(Cabinet\Login::class)
            ->set('email', 'aziz@torg.uz')->set('password', 'wrong-pass')
            ->call('login')
            ->assertHasErrors(['email'])
            ->assertSee(ui('cab.login.err'))
            ->assertSet('password', '');
        $this->assertTrue(Auth::guard('cabinet')->guest());

        Livewire::test(Cabinet\Login::class)
            ->set('email', ' Aziz@Torg.uz')->set('password', 'secret-pass')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(lroute('cabinet'));

        $this->assertTrue(Auth::guard('cabinet')->user()->is($account));
        $this->assertTrue(Auth::guard('web')->guest());
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/en/cabinet')->assertOk()->assertSee('Torg Servis')->assertSee('id="cab-logout"', false);

        $this->post('/en/cabinet/logout')->assertRedirect('/en/cabinet/login');
        $this->assertTrue(Auth::guard('cabinet')->guest());
    }

    public function test_blocked_account_is_told_so_and_is_stopped_at_its_next_click(): void
    {
        $account = $this->account();
        $this->actingAs($account, 'cabinet');
        $this->get('/en/cabinet')->assertOk();

        $account->update(['status' => 'blocked']);

        Livewire::test(Cabinet\Scales::class)->assertForbidden();
        Livewire::test(Cabinet\Service::class)->assertForbidden();
        $this->get('/en/cabinet')->assertRedirect('/en/cabinet/login');

        $account->update(['password' => 'secret-pass']);
        Livewire::test(Cabinet\Login::class)
            ->set('email', $account->email)->set('password', 'secret-pass')
            ->call('login')
            ->assertSee(ui('cab.login.blocked'));
        $this->assertTrue(Auth::guard('cabinet')->guest());
    }

    public function test_sections_follow_the_partner_offers_and_staff_cannot_use_them(): void
    {
        $this->actingAs($this->account('Seller', ['sales']), 'cabinet');
        $this->get('/en/cabinet')->assertOk()->assertSee('id="cab-sales"', false)->assertDontSee('id="cab-service"', false);
        Livewire::test(Cabinet\Service::class)->assertForbidden();

        $this->actingAs($this->account('Workshop', ['service', 'install']), 'cabinet');
        $this->get('/en/cabinet')->assertOk()->assertSee('id="cab-service"', false)->assertDontSee('id="cab-sales"', false);
        Livewire::test(Cabinet\Scales::class)->assertForbidden();

        $this->actingAs($this->account('Neither', ['integration']), 'cabinet');
        $this->get('/en/cabinet')->assertOk()->assertSee('id="cab-noroles"', false)->assertDontSee('id="cab-sales"', false)->assertDontSee('id="cab-service"', false);

        Auth::guard('cabinet')->logout();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]), 'web');
        $this->get('/en/cabinet')->assertRedirect('/en/cabinet/login');
        Livewire::test(Cabinet\Scales::class)->assertForbidden();
    }

    public function test_dealer_sees_only_the_scales_handed_over_to_it(): void
    {
        $account = $this->account('Seller', ['sales']);
        $other = $this->partner('Other Dealer', ['sales']);
        $this->scale('C11-MINE-1', $account->partner, '2026-10-10');
        $this->scale('C11-MINE-2', $account->partner, '2025-10-10');
        $this->scale('C11-THEIRS', $other);
        $this->scale('C11-STOCK');
        $this->actingAs($account, 'cabinet');

        Livewire::test(Cabinet\Scales::class)
            ->assertSee('C11-MINE-1')->assertSee('10.10.2026')->assertSee('09.10.2027')->assertSee(ui('cab.w.active'))
            ->assertSee('C11-MINE-2')->assertSee(ui('cab.w.expired'))
            ->assertDontSee('C11-THEIRS')->assertDontSee('C11-STOCK')
            ->set('search', ' mine-2 ')
            ->assertSee('C11-MINE-2')->assertDontSee('C11-MINE-1')
            ->set('search', 'THEIRS')
            ->assertDontSee('C11-THEIRS')->assertSee(ui('cab.sales.notfound'));
    }

    public function test_service_centre_sees_only_status_and_warranty_of_a_scale_it_has_not_serviced(): void
    {
        $dealer = $this->partner('Secret Dealer', ['sales']);
        $scale = $this->scale('C11-0042', $dealer);
        SerialMovement::factory()->service()->create(['serial_number_id' => $scale->id, 'partner_id' => $this->partner('Other Workshop', ['service'])->id, 'happened_on' => '2026-11-01', 'note' => 'Other centre note']);
        $this->actingAs($this->account('Workshop', ['service']), 'cabinet');

        Livewire::test(Cabinet\Service::class)
            ->set('serial', ' c11-0042 ')
            ->call('find')
            ->assertHasNoErrors()
            ->assertSee(ui('sn.official'))
            ->assertSee($this->model->name)
            ->assertSee('09.10.2027')
            ->assertSee(ui('sn.w.active'))
            ->assertSee(ui('cab.svc.limited'))
            ->assertSee('id="cab-request-btn"', false)
            ->assertDontSee('id="cab-history"', false)
            ->assertDontSee('Secret Dealer')
            ->assertDontSee('Other Workshop')
            ->assertDontSee('Other centre note');

        Livewire::test(Cabinet\Service::class)->set('serial', 'NOPE-1')->call('find')
            ->assertSee(ui('sn.none', ['serial' => 'NOPE-1']))->assertDontSee('id="cab-step"', false);
        Livewire::test(Cabinet\Service::class)->set('serial', '  ')->call('find')->assertHasErrors(['serial']);
    }

    public function test_recording_a_service_step_opens_the_full_history_to_that_centre(): void
    {
        $account = $this->account('Workshop', ['service']);
        $scale = $this->scale('C11-0042', $this->partner('Known Dealer', ['sales']));
        $job = ServiceCase::factory()->create(['title' => ['ru' => 'Калибровка', 'uz' => 'Kalibrlash', 'en' => 'Calibration']]);
        $this->actingAs($account, 'cabinet');

        Livewire::test(Cabinet\Service::class)
            ->set('serial', 'C11-0042')->call('find')
            ->set('form.type', 'service_in')
            ->set('form.happened_on', '2026-11-20')
            ->set('form.service_case_id', (string) $job->id)
            ->set('form.note', '  Does not weigh  ')
            ->call('addStep')
            ->assertHasNoErrors()
            ->assertSee(ui('cab.svc.add.done'))
            ->assertSee('id="cab-history"', false)
            ->assertSee(ui('cab.mv.import'))
            ->assertSee('Known Dealer')
            ->assertSee(ui('cab.mv.service_in'))
            ->assertSee('Calibration')
            ->assertSee('Does not weigh')
            ->assertSee('id="cab-recent"', false)
            ->assertDontSee('id="cab-request-btn"', false);

        $step = SerialMovement::where('type', 'service_in')->sole();
        $this->assertSame(
            [$scale->id, $account->partner_id, $account->id, null, $job->id, '2026-11-20', 'Does not weigh'],
            [$step->serial_number_id, $step->partner_id, $step->cabinet_account_id, $step->user_id, $step->service_case_id, $step->happened_on->format('Y-m-d'), $step->note],
        );
        $this->assertSame('2026-10-10', $scale->fresh()->warrantyStart()->format('Y-m-d'));
    }

    public function test_service_step_needs_a_service_event_and_a_sane_date(): void
    {
        $this->scale('C11-0042');
        $this->actingAs($this->account('Workshop', ['service']), 'cabinet');

        Livewire::test(Cabinet\Service::class)
            ->set('serial', 'C11-0042')->call('find')
            ->set('form.type', SerialMovement::DEALER)->call('addStep')->assertHasErrors(['form.type'])
            ->set('form.type', 'service_done')
            ->set('form.happened_on', '2026-08-31')->call('addStep')->assertHasErrors(['form.happened_on'])->assertSee(ui('cab.svc.err.date'))
            ->set('form.happened_on', '2026-11-26')->call('addStep')->assertHasErrors(['form.happened_on'])
            ->set('form.happened_on', '2026-11-25')
            ->set('form.service_case_id', '999999')->call('addStep')->assertHasErrors(['form.service_case_id'])
            ->set('form.service_case_id', '')
            ->set('form.note', str_repeat('x', 501))->call('addStep')->assertHasErrors(['form.note']);

        $this->assertSame(0, SerialMovement::count());
    }

    public function test_centre_asks_rsg_for_details_once_and_a_grant_opens_the_history(): void
    {
        $account = $this->account('Workshop', ['service']);
        $scale = $this->scale('C11-0042', $this->partner('Known Dealer', ['sales']));
        $this->actingAs($account, 'cabinet');

        $page = Livewire::test(Cabinet\Service::class)
            ->set('serial', 'C11-0042')->call('find')
            ->set('requestNote', '  Customer lost the papers ')
            ->call('requestDetails')
            ->assertHasNoErrors()
            ->assertSee(ui('cab.req.new', ['date' => '25.11.2026']))
            ->assertDontSee('id="cab-request-btn"', false)
            ->call('requestDetails');

        $request = SerialDetailRequest::sole();
        $this->assertSame(
            [$account->partner_id, $account->id, $scale->id, 'Customer lost the papers', 'new'],
            [$request->partner_id, $request->cabinet_account_id, $request->serial_number_id, $request->note, $request->status],
        );

        $request->update(['status' => 'refused', 'answer' => 'Call us first']);
        $page->call('find')
            ->assertSee(ui('cab.req.refused', ['date' => '25.11.2026']))
            ->assertSee('Call us first')
            ->assertSee('id="cab-request-btn"', false)
            ->assertDontSee('Known Dealer');

        $request->update(['status' => SerialDetailRequest::GRANTED]);
        $page->call('find')
            ->assertSee('id="cab-history"', false)
            ->assertSee(ui('cab.req.granted'))
            ->assertSee('Known Dealer')
            ->assertDontSee('id="cab-request-btn"', false);

        // A grant to one centre opens nothing to another.
        $this->actingAs($this->account('Second Workshop', ['service']), 'cabinet');
        Livewire::test(Cabinet\Service::class)->set('serial', 'C11-0042')->call('find')
            ->assertDontSee('id="cab-history"', false)->assertDontSee('Known Dealer');
    }
}
