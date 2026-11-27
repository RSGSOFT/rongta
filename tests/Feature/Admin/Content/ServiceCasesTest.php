<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\ServiceCases;
use App\Models\ActivityLog;
use App\Models\ServiceCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class ServiceCasesTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->editor());
    }

    public function test_editor_opens_the_screens(): void
    {
        $case = ServiceCase::factory()->create();

        foreach (['/admin/service-cases', '/admin/service-cases/create', "/admin/service-cases/{$case->id}/edit"] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_job_is_created_for_a_family_without_an_amount_and_logged(): void
    {
        $family = $this->family();

        Livewire::withQueryParams(['family' => $family->id])->test(ServiceCases\Form::class)
            ->assertSet('form.family_id', (string) $family->id)
            ->set('form.title', $this->tr('Замена печатающей головки'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.service-cases.index'));

        $case = ServiceCase::sole();
        $this->assertSame($family->id, $case->family_id);
        $this->assertSame('Замена печатающей головки uz', $case->tr('title', 'uz'));
        $this->assertNull($case->amount);
        $this->assertNull($case->note);
        $this->assertFalse($case->isListed());
        $this->assertSame(1, ActivityLog::where('action', 'created')->where('subject_type', ServiceCase::class)->count());
    }

    public function test_job_for_every_scale_takes_a_spaced_amount_and_the_from_flag(): void
    {
        Livewire::test(ServiceCases\Form::class)
            ->assertSet('form.family_id', '')
            ->set('form.title', $this->tr('Диагностика'))
            ->set('form.amount', '150 000')
            ->set('form.is_from', true)
            ->call('save')
            ->assertHasNoErrors();

        $case = ServiceCase::sole();
        $this->assertNull($case->family_id);
        $this->assertSame(150000, $case->amount);
        $this->assertTrue($case->is_from);
        $this->assertTrue($case->isListed());
    }

    public static function badAmounts(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-5000'],
            'fraction' => ['1500.50'],
            'text' => ['дорого'],
            'above the limit' => [(string) (ServiceCase::MAX_AMOUNT + 1)],
        ];
    }

    #[DataProvider('badAmounts')]
    public function test_amount_must_be_a_whole_number_in_range(string $amount): void
    {
        Livewire::test(ServiceCases\Form::class)
            ->set('form.title', $this->tr('Калибровка'))
            ->set('form.amount', $amount)
            ->call('save')
            ->assertHasErrors(['form.amount']);

        $this->assertSame(0, ServiceCase::count());

        $case = ServiceCase::factory()->create(['amount' => 90000]);
        Livewire::test(ServiceCases\Index::class)
            ->call('setAmount', $case->id, $amount)
            ->assertHasErrors(["amount.{$case->id}"]);

        $this->assertSame(90000, $case->refresh()->amount);
    }

    public function test_job_needs_a_russian_title_and_a_known_family(): void
    {
        Livewire::test(ServiceCases\Form::class)
            ->set('form.family_id', '999')
            ->call('save')
            ->assertHasErrors(['form.title.ru' => 'required', 'form.family_id' => 'exists']);
    }

    public function test_amount_is_typed_cleared_and_logged_from_the_list(): void
    {
        $case = ServiceCase::factory()->withoutAmount()->create(['title' => $this->tr('Чистка валика')]);

        Livewire::test(ServiceCases\Index::class)
            ->assertSee('Чистка валика')
            ->assertSee('Нет тарифа')
            ->call('setAmount', $case->id, "80\u{00A0}000")
            ->assertHasNoErrors()
            ->assertSee('Видна');
        $this->assertSame(80000, $case->refresh()->amount);

        Livewire::test(ServiceCases\Index::class)->call('setAmount', $case->id, '');
        $this->assertNull($case->refresh()->amount);
        $this->assertSame(2, ActivityLog::where('action', 'updated')->where('subject_type', ServiceCase::class)->count());
    }

    public function test_job_is_hidden_and_deleted_from_the_list(): void
    {
        $case = ServiceCase::factory()->create();

        Livewire::test(ServiceCases\Index::class)->call('togglePublished', $case->id)->assertSee('Скрыта');
        $this->assertFalse($case->refresh()->is_published);

        Livewire::test(ServiceCases\Index::class)->call('delete', $case->id);
        $this->assertSame(0, ServiceCase::count());
        $this->assertSame(1, ActivityLog::where('action', 'deleted')->where('subject_type', ServiceCase::class)->count());
    }
}
