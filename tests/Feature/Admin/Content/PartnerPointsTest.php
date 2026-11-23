<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\PartnerPoints;
use App\Models\ActivityLog;
use App\Models\PartnerPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class PartnerPointsTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->editor());
    }

    public function test_point_needs_a_russian_title_and_a_known_block(): void
    {
        Livewire::test(PartnerPoints\Form::class)
            ->set('form.kind', 'discount')
            ->call('save')
            ->assertHasErrors(['form.title.ru' => 'required', 'form.kind' => 'in']);

        $this->assertSame(0, PartnerPoint::count());
    }

    public function test_advantage_is_created_without_a_body_and_logged(): void
    {
        Livewire::test(PartnerPoints\Form::class)
            ->assertSet('form.kind', 'advantage')
            ->set('form.title', $this->tr('Техническая поддержка'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.partner-points.index'));

        $point = PartnerPoint::sole();
        $this->assertSame('advantage', $point->kind);
        $this->assertSame('Техническая поддержка en', $point->tr('title', 'en'));
        $this->assertNull($point->body);
        $this->assertTrue($point->is_published);
        $this->assertSame(1, ActivityLog::where('action', 'created')->where('subject_type', PartnerPoint::class)->count());
    }

    public function test_create_link_preselects_the_terms_block_and_continues_its_order(): void
    {
        PartnerPoint::factory()->term()->create(['sort' => 4]);

        Livewire::withQueryParams(['kind' => 'term'])->test(PartnerPoints\Form::class)
            ->assertSet('form.kind', 'term')
            ->assertSet('form.sort', 5);

        Livewire::withQueryParams(['kind' => 'bogus'])->test(PartnerPoints\Form::class)
            ->assertSet('form.kind', 'advantage')
            ->assertSet('form.sort', 1);
    }

    public function test_point_is_edited_hidden_and_deleted(): void
    {
        $point = PartnerPoint::factory()->create(['title' => $this->tr('Обучение')]);

        Livewire::test(PartnerPoints\Form::class, ['point' => $point])
            ->assertSet('form.title.ru', 'Обучение')
            ->set('form.body.ru', 'Обучаем продавцов')
            ->set('form.is_published', false)
            ->call('save')
            ->assertHasNoErrors();

        $point->refresh();
        $this->assertSame('Обучаем продавцов', $point->tr('body', 'ru'));
        $this->assertFalse($point->is_published);

        Livewire::test(PartnerPoints\Index::class)
            ->assertSee('Обучение')
            ->assertSee('Скрыт')
            ->call('delete', $point->id);

        $this->assertSame(0, PartnerPoint::count());
        $this->assertSame(1, ActivityLog::where('action', 'deleted')->where('subject_type', PartnerPoint::class)->count());
    }
}
