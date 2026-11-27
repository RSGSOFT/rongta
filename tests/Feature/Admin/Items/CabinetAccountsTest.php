<?php

namespace Tests\Feature\Admin\Items;

use App\Livewire\Admin\CabinetAccounts;
use App\Livewire\Admin\SerialRequests;
use App\Livewire\Admin\Serials;
use App\Models\ActivityLog;
use App\Models\CabinetAccount;
use App\Models\Partner;
use App\Models\SerialDetailRequest;
use App\Models\SerialMovement;
use App\Models\SerialNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

/** Admin side of the partner cabinet: approving accounts and answering service centres' requests. */
class CabinetAccountsTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-25 10:00:00');
        $this->actingAs($this->admin(['name' => 'Admin One']));
    }

    /** @param list<string> $offers */
    private function partner(string $name, array $offers, string $kind = 'reseller'): Partner
    {
        return Partner::create(['kind' => $kind, 'name' => $this->tr($name), 'offers' => $offers, 'is_placeholder' => false, 'is_published' => true, 'sort' => 1]);
    }

    public function test_only_admins_open_the_cabinet_screens(): void
    {
        $account = CabinetAccount::factory()->create(['company' => 'Torg Servis']);
        $urls = ['/admin/cabinet-accounts', "/admin/cabinet-accounts/{$account->id}", '/admin/serial-requests'];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/cabinet-accounts')->assertSee('Torg Servis')->assertSee('Ждёт одобрения');

        $this->actingAs($this->editor());
        foreach ($urls as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->get('/admin')->assertDontSee(route('admin.cabinet-accounts.index'))->assertDontSee(route('admin.serial-requests.index'));
    }

    public function test_list_is_filtered_by_status_and_searched(): void
    {
        CabinetAccount::factory()->create(['company' => 'Waiting Co']);
        CabinetAccount::factory()->approved($this->partner('Партнёр', ['sales']))->create(['company' => 'Approved Co', 'email' => 'boss@approved.uz']);

        Livewire::test(CabinetAccounts\Index::class)
            ->assertSee('Waiting Co')->assertSee('Approved Co')
            ->set('filterStatus', 'pending')
            ->assertSee('Waiting Co')->assertDontSee('Approved Co')
            ->set('filterStatus', '')
            ->set('search', 'boss@approved')
            ->assertSee('Approved Co')->assertDontSee('Waiting Co');
    }

    public function test_approval_needs_a_partner_and_warns_when_the_partner_lacks_the_asked_service(): void
    {
        $account = CabinetAccount::factory()->create(['wants_sales' => true, 'wants_service' => true]);
        $partner = $this->partner('Торг Сервис', ['sales']);

        Livewire::test(CabinetAccounts\Show::class, ['account' => $account])
            ->call('approve')
            ->assertHasErrors(['partnerId' => 'required'])
            ->set('partnerId', '999999')
            ->call('approve')
            ->assertHasErrors(['partnerId' => 'exists'])
            ->set('partnerId', (string) $partner->id)
            ->assertSee('id="account-missing"', false)
            ->call('approve')
            ->assertHasNoErrors()
            ->assertSee('Одобрен');

        $account->refresh();
        $this->assertSame(['approved', $partner->id, auth()->id()], [$account->status, $account->partner_id, $account->decided_by]);
        $this->assertTrue($account->canEnter() && $account->sells() && ! $account->services());
        $this->assertSame(1, ActivityLog::where('action', 'status_changed')->where('subject_type', CabinetAccount::class)->count());

        $partner->update(['offers' => ['sales', 'service']]);
        Livewire::test(CabinetAccounts\Show::class, ['account' => $account])->assertDontSee('id="account-missing"', false);
        $this->assertTrue($account->fresh()->services());
    }

    public function test_account_can_be_blocked_given_a_new_password_and_deleted(): void
    {
        $partner = $this->partner('Сервис', ['service']);
        $account = CabinetAccount::factory()->approved($partner)->create(['remember_token' => 'token']);
        $scale = SerialNumber::factory()->create(['scale_model_id' => $this->scaleModel()->id]);
        $step = SerialMovement::factory()->service()->create(['serial_number_id' => $scale->id, 'partner_id' => $partner->id, 'cabinet_account_id' => $account->id]);

        $page = Livewire::test(CabinetAccounts\Show::class, ['account' => $account])
            ->call('block')
            ->assertSee('Заблокирован');
        $account->refresh();
        $this->assertSame(['blocked', null, false], [$account->status, $account->remember_token, $account->canEnter()]);

        $page->set('password', 'short')->set('password_confirmation', 'short')->call('savePassword')->assertHasErrors(['password'])
            ->set('password', 'new-secret-1')->set('password_confirmation', 'other')->call('savePassword')->assertHasErrors(['password'])
            ->set('password_confirmation', 'new-secret-1')->call('savePassword')->assertHasNoErrors()->assertSet('password', '');
        $this->assertTrue(Hash::check('new-secret-1', $account->fresh()->password));
        $this->assertStringNotContainsString('new-secret-1', (string) ActivityLog::latest('id')->first()->changes_json);

        $page->call('delete')->assertRedirect(route('admin.cabinet-accounts.index'));
        $this->assertSame(0, CabinetAccount::count());
        $this->assertSame([$partner->id, null], [$step->fresh()->partner_id, $step->fresh()->cabinet_account_id]);
    }

    public function test_request_for_details_is_granted_or_refused_with_an_answer(): void
    {
        $partner = $this->partner('Сервис', ['service']);
        $scale = SerialNumber::factory()->create(['serial' => 'C1-0042', 'scale_model_id' => $this->scaleModel()->id]);
        $first = SerialDetailRequest::factory()->create(['partner_id' => $partner->id, 'serial_number_id' => $scale->id, 'note' => 'Нужна история']);
        $second = SerialDetailRequest::factory()->create(['partner_id' => $partner->id, 'serial_number_id' => $scale->id]);

        Livewire::test(SerialRequests\Index::class)
            ->assertSee('C1-0042')->assertSee('Нужна история')->assertSee('Сервис')
            ->call('decide', $first->id, 'granted')
            ->set('answers.'.$second->id, '  Сначала позвоните  ')
            ->call('decide', $second->id, 'refused')
            ->call('decide', $second->id, 'deleted')
            ->assertHasNoErrors()
            ->set('filterStatus', 'refused')
            ->assertSee('Сначала позвоните')->assertDontSee('Нужна история');

        $this->assertSame(['granted', null, auth()->id()], [$first->fresh()->status, $first->fresh()->answer, $first->fresh()->decided_by]);
        $this->assertSame(['refused', 'Сначала позвоните'], [$second->fresh()->status, $second->fresh()->answer]);
        $this->assertSame(2, ActivityLog::where('action', 'status_changed')->where('subject_type', SerialDetailRequest::class)->count());
    }

    public function test_scales_are_handed_to_any_partner_that_sells_whatever_its_kind(): void
    {
        $seller = $this->partner('Софт и весы', ['sales', 'integration'], 'integrator');
        $workshop = $this->partner('Только сервис', ['service']);
        $scale = SerialNumber::factory()->create(['serial' => 'C1-0001', 'scale_model_id' => $this->scaleModel()->id, 'imported_on' => '2026-10-01']);

        Livewire::test(Serials\Handover::class)
            ->assertSee('Софт и весы')->assertDontSee('Только сервис')
            ->set('partnerId', (string) $workshop->id)->set('happenedOn', '2026-10-15')->set('serials', 'C1-0001')
            ->call('submit')
            ->assertHasErrors(['partnerId' => 'exists'])
            ->set('partnerId', (string) $seller->id)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('report.done', 1);

        $this->assertSame($seller->id, $scale->movements()->sole()->partner_id);

        Livewire::test(Serials\Card::class, ['serial' => $scale])->assertSee('Софт и весы')->assertDontSee('Только сервис');
    }
}
