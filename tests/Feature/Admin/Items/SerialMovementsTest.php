<?php

namespace Tests\Feature\Admin\Items;

use App\Livewire\Admin\Serials;
use App\Models\ActivityLog;
use App\Models\Partner;
use App\Models\ScaleModel;
use App\Models\SerialMovement;
use App\Models\SerialNumber;
use App\Models\ServiceCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class SerialMovementsTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    private ScaleModel $model;

    private Partner $dealer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-24 10:00:00');

        $this->model = $this->scaleModel();
        $this->dealer = Partner::create(['kind' => 'reseller', 'name' => $this->tr('Торг Сервис'), 'offers' => ['sales'], 'is_placeholder' => false, 'is_published' => true, 'sort' => 1]);

        $this->actingAs($this->admin());
    }

    private function serial(string $number = 'C1-0001', string $importedOn = '2026-10-01'): SerialNumber
    {
        return SerialNumber::factory()->create(['serial' => $number, 'scale_model_id' => $this->model->id, 'imported_on' => $importedOn]);
    }

    public function test_only_admins_open_the_card_and_the_handover_screen(): void
    {
        $serial = $this->serial();
        $urls = ["/admin/serials/numbers/{$serial->id}", '/admin/serials/handover'];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }

        $this->actingAs($this->editor());
        foreach ($urls as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_handover_to_a_dealer_starts_the_warranty(): void
    {
        $serial = $this->serial();
        $this->assertSame('none', $serial->warrantyState());

        Livewire::test(Serials\Card::class, ['serial' => $serial])
            ->assertSee('Не начата')
            ->set('form.happened_on', '2026-10-10')
            ->set('form.partner_id', (string) $this->dealer->id)
            ->call('addMovement')
            ->assertHasNoErrors()
            ->assertSee('Передан дилеру')
            ->assertSee('Торг Сервис')
            ->assertSee('10.10.2026 – 09.10.2027');

        $serial->refresh();
        $this->assertSame(['2026-10-10', '2027-10-09', 'active'], [$serial->warrantyStart()->format('Y-m-d'), $serial->warrantyEnd()->format('Y-m-d'), $serial->warrantyState()]);
        $this->assertSame(auth()->id(), SerialMovement::sole()->user_id);
        $this->assertSame(1, ActivityLog::where('action', 'created')->where('subject_type', SerialMovement::class)->count());
    }

    public function test_warranty_follows_the_period_setting_and_expires(): void
    {
        $serial = $this->serial('C1-0002', '2025-01-01');
        SerialMovement::factory()->create(['serial_number_id' => $serial->id, 'partner_id' => $this->dealer->id, 'happened_on' => '2025-05-20']);

        $this->assertSame(['2026-05-19', 'expired'], [$serial->warrantyEnd()->format('Y-m-d'), $serial->warrantyState()]);

        Livewire::test(Serials\Index::class)
            ->assertSet('warrantyMonths', '12')
            ->set('warrantyMonths', '24')
            ->call('saveWarranty')
            ->assertHasNoErrors();

        $serial = $serial->fresh();
        $this->assertSame(['2027-05-19', 'active'], [$serial->warrantyEnd()->format('Y-m-d'), $serial->warrantyState()]);

        Livewire::test(Serials\Index::class)->set('warrantyMonths', '0')->call('saveWarranty')->assertHasErrors(['warrantyMonths']);
        $this->assertSame(24, SerialNumber::warrantyMonths());
    }

    public function test_handover_needs_a_dealer_a_sane_date_and_happens_once(): void
    {
        $serial = $this->serial();
        $integrator = Partner::create(['kind' => 'integrator', 'name' => $this->tr('Софт'), 'offers' => [], 'is_placeholder' => false, 'is_published' => true, 'sort' => 2]);

        Livewire::test(Serials\Card::class, ['serial' => $serial])
            ->call('addMovement')
            ->assertHasErrors(['form.partner_id' => 'required'])
            ->set('form.partner_id', (string) $integrator->id)
            ->call('addMovement')
            ->assertHasErrors(['form.partner_id' => 'exists'])
            ->set('form.partner_id', (string) $this->dealer->id)
            ->set('form.happened_on', '2026-09-30')
            ->call('addMovement')
            ->assertHasErrors(['form.happened_on' => 'after_or_equal'])
            ->set('form.happened_on', '2026-11-25')
            ->call('addMovement')
            ->assertHasErrors(['form.happened_on' => 'before_or_equal']);

        $this->assertSame(0, SerialMovement::count());

        SerialMovement::factory()->create(['serial_number_id' => $serial->id, 'partner_id' => $this->dealer->id]);
        Livewire::test(Serials\Card::class, ['serial' => $serial])
            ->set('form.partner_id', (string) $this->dealer->id)
            ->call('addMovement')
            ->assertHasErrors(['form.type']);

        $this->assertSame(1, SerialMovement::count());
    }

    public function test_service_steps_are_recorded_with_a_job_and_can_be_deleted(): void
    {
        $serial = $this->serial();
        $job = ServiceCase::factory()->create(['title' => $this->tr('Калибровка')]);

        Livewire::test(Serials\Card::class, ['serial' => $serial])
            ->set('form.type', 'service_in')
            ->set('form.happened_on', '2026-11-20')
            ->set('form.service_case_id', (string) $job->id)
            ->set('form.note', '  Не взвешивает  ')
            ->call('addMovement')
            ->assertHasNoErrors()
            ->assertSee('Принят в сервис')
            ->assertSee('Калибровка')
            ->assertSee('Не взвешивает');

        $movement = SerialMovement::sole();
        $this->assertSame(['service_in', null, $job->id, 'Не взвешивает'], [$movement->type, $movement->partner_id, $movement->service_case_id, $movement->note]);
        $this->assertSame('none', $serial->fresh()->warrantyState());

        Livewire::test(Serials\Card::class, ['serial' => $serial])->call('deleteMovement', $movement->id);
        $this->assertSame(0, SerialMovement::count());
        $this->assertSame(1, ActivityLog::where('action', 'deleted')->where('subject_type', SerialMovement::class)->count());
    }

    public function test_movement_of_another_scale_cannot_be_deleted_from_this_card(): void
    {
        $serial = $this->serial();
        $other = $this->serial('C1-0009');
        $movement = SerialMovement::factory()->create(['serial_number_id' => $other->id, 'partner_id' => $this->dealer->id]);

        try {
            Livewire::test(Serials\Card::class, ['serial' => $serial])->call('deleteMovement', $movement->id);
        } catch (ModelNotFoundException) {
        }

        $this->assertSame(1, SerialMovement::count());
    }

    public function test_many_scales_are_handed_to_a_dealer_at_once_with_a_report(): void
    {
        $this->serial('C1-0001');
        $this->serial('C1-0002');
        $late = $this->serial('C1-0003', '2026-11-01');
        $taken = $this->serial('C1-0004');
        SerialMovement::factory()->create(['serial_number_id' => $taken->id, 'partner_id' => $this->dealer->id, 'happened_on' => '2026-10-05']);

        $page = Livewire::test(Serials\Handover::class)
            ->set('partnerId', (string) $this->dealer->id)
            ->set('happenedOn', '2026-10-15')
            ->set('serials', "c1-0001\nC1-0002, C1-0003; C1-0004\nNOPE-1\nc1-0001")
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('report.done', 2)
            ->assertSee('Передано: 2');

        $reasons = collect($page->get('report.problems'))->pluck('reason', 'serial');
        $this->assertSame(['C1-0003', 'C1-0004', 'NOPE-1'], $reasons->keys()->all());
        $this->assertStringContainsString('раньше даты импорта', $reasons['C1-0003']);
        $this->assertStringContainsString('Уже передан дилеру: Торг Сервис, 05.10.2026', $reasons['C1-0004']);
        $this->assertStringContainsString('Нет в базе', $reasons['NOPE-1']);

        $this->assertSame(3, SerialMovement::count());
        $this->assertSame('2026-10-15', SerialNumber::where('serial', 'C1-0001')->sole()->warrantyStart()->format('Y-m-d'));
        $this->assertSame('none', $late->fresh()->warrantyState());
    }

    public function test_handover_needs_a_dealer_and_numbers(): void
    {
        Livewire::test(Serials\Handover::class)
            ->call('submit')
            ->assertHasErrors(['partnerId' => 'required', 'serials' => 'required']);
    }
}
