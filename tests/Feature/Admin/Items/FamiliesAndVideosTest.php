<?php

namespace Tests\Feature\Admin\Items;

use App\Livewire\Admin\Items\Families;
use App\Livewire\Admin\Items\Videos;
use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class FamiliesAndVideosTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->editor());
    }

    public function test_family_is_created_with_three_languages_and_logged(): void
    {
        Livewire::test(Families\Form::class)
            ->set('form.slug', 'label')
            ->set('form.name', ['ru' => ' Весы с печатью ', 'uz' => 'Yorliq tarozilari', 'en' => 'Label scales'])
            ->set('form.line', ['ru' => 'Описание', 'uz' => '', 'en' => ''])
            ->set('form.pic', 'pole')
            ->set('form.sort', 2)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.families.index'));

        $family = Family::where('slug', 'label')->firstOrFail();
        $this->assertSame(['ru' => 'Весы с печатью', 'uz' => 'Yorliq tarozilari', 'en' => 'Label scales'], $family->name);
        $this->assertSame(['ru' => 'Описание', 'uz' => '', 'en' => ''], $family->line);
        $this->assertSame('pole', $family->pic);
        $this->assertDatabaseHas('activity_logs', ['action' => 'created', 'subject_type' => Family::class, 'subject_id' => $family->id]);
    }

    public function test_family_validation(): void
    {
        $this->family(['slug' => 'label']);

        Livewire::test(Families\Form::class)
            ->set('form.slug', 'label')
            ->set('form.name', ['ru' => '', 'uz' => 'x', 'en' => 'x'])
            ->set('form.pic', 'unknown')
            ->set('form.sort', -1)
            ->call('save')
            ->assertHasErrors(['form.slug' => 'unique', 'form.name.ru' => 'required', 'form.line.ru' => 'required', 'form.pic' => 'in', 'form.sort' => 'min'])
            ->assertNoRedirect();

        Livewire::test(Families\Form::class)
            ->set('form.slug', 'Bad Slug!')
            ->call('save')
            ->assertHasErrors(['form.slug' => 'regex']);

        $this->assertSame(1, Family::count());
    }

    public function test_family_is_updated_and_only_changes_are_logged(): void
    {
        $family = $this->family();

        Livewire::test(Families\Form::class, ['family' => $family])
            ->assertSet('form.slug', 'label')
            ->assertSet('form.name.uz', 'Label scales uz')
            ->set('form.name.en', 'Label printing scales')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Label printing scales', $family->fresh()->name['en']);
        $log = ActivityLog::where('action', 'updated')->firstOrFail();
        $this->assertSame(['name'], array_keys($log->changes));
    }

    public function test_family_with_models_cannot_be_deleted(): void
    {
        $family = $this->family();
        $this->scaleModel($family);
        $empty = $this->family(['slug' => 'empty']);

        Livewire::test(Families\Index::class)
            ->call('delete', $family->id)
            ->assertSee('Нельзя удалить семейство');
        $this->assertNotNull($family->fresh());

        Livewire::test(Families\Index::class)->call('delete', $empty->id);
        $this->assertNull($empty->fresh());
        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'subject_type' => Family::class, 'subject_id' => $empty->id]);
    }

    public function test_family_list_flags_missing_translations(): void
    {
        $this->family(['slug' => 'gap', 'name' => ['ru' => 'Без перевода', 'uz' => '', 'en' => 'x']]);

        Livewire::test(Families\Index::class)->assertSee('Без перевода')->assertSee('нет: UZ');
    }

    public function test_video_crud(): void
    {
        $family = $this->family();

        Livewire::test(Videos\Form::class)
            ->assertSet('form.is_placeholder', true)
            ->set('form.title', $this->tr('Как заправить ленту'))
            ->set('form.url', 'https://example.com/watch?v=1')
            ->set('form.family_id', $family->id)
            ->set('form.is_placeholder', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.videos.index'));

        $video = Video::firstOrFail();
        $this->assertSame('https://example.com/watch?v=1', $video->url);
        $this->assertSame($family->id, $video->family_id);
        $this->assertFalse($video->is_placeholder);
        $this->assertNull($video->slug);

        Livewire::test(Videos\Form::class, ['video' => $video])
            ->set('form.url', '')
            ->set('form.family_id', '')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertNull($video->fresh()->url);
        $this->assertNull($video->fresh()->family_id);

        Livewire::test(Videos\Index::class)->assertSee('Как заправить ленту')->assertSee('нет ссылки')->call('delete', $video->id);
        $this->assertSame(0, Video::count());
        $this->assertSame(['created', 'updated', 'deleted'], ActivityLog::orderBy('id')->pluck('action')->all());
    }

    public function test_video_validation_rejects_unsafe_links(): void
    {
        foreach (['javascript:alert(1)', 'ftp://example.com/a', 'not a link'] as $url) {
            Livewire::test(Videos\Form::class)
                ->set('form.title', $this->tr('Видео'))
                ->set('form.url', $url)
                ->call('save')
                ->assertHasErrors('form.url');
        }

        Livewire::test(Videos\Form::class)
            ->set('form.family_id', 999)
            ->call('save')
            ->assertHasErrors(['form.title.ru' => 'required', 'form.family_id' => 'exists']);

        $this->assertSame(0, Video::count());
    }
}
