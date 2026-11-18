<?php

namespace Tests\Feature\Admin\Items;

use App\Livewire\Admin\Items\Models\Form;
use App\Livewire\Admin\Items\Models\Index;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\ModelFeature;
use App\Models\ModelSpec;
use App\Models\ScaleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs($this->editor());
    }

    public function test_model_is_created_with_all_fields_and_features(): void
    {
        $family = $this->family();

        Livewire::test(Form::class)
            ->assertSet('form.is_published', false)
            ->set('form.slug', 'rls1000a')
            ->set('form.family_id', $family->id)
            ->set('form.name', 'RLS1000/RLS1100A')
            ->set('form.kind', 'pole')
            ->set('form.head', 'box')
            ->set('form.cam', true)
            ->set('form.is_aurora', true)
            ->set('form.label_print', 'yes')
            ->set('form.conn', ['eth' => 'std', 'rs232' => 'std', 'usb' => '', 'wifi' => 'opt', 'bt' => ''])
            ->set('form.type', $this->tr('Весы с печатью этикеток'))
            ->set('form.headline', $this->tr('Заголовок'))
            ->set('form.intro', $this->tr('Вступление'))
            ->set('form.source_url', 'https://www.rongtatech.com/x.html')
            ->set('form.sort', 4)
            ->set('form.is_published', true)
            ->call('addFeature')
            ->set('features.0.icon', 'label')
            ->set('features.0.title', $this->tr('Печать этикеток'))
            ->set('features.0.text', $this->tr('Текст'))
            ->call('addFeature')
            ->set('features.1.title', $this->tr('Второй блок'))
            ->set('features.1.text', $this->tr('Текст 2'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.models.edit', 'rls1000a'));

        $model = ScaleModel::where('slug', 'rls1000a')->firstOrFail();
        $this->assertSame('RLS1000/RLS1100A', $model->name);
        $this->assertSame(['head' => 'box', 'cam' => true, 'receipt' => false, 'dual' => false], $model->illustration);
        $this->assertSame(['eth' => 'std', 'rs232' => 'std', 'wifi' => 'opt'], $model->conn);
        $this->assertTrue($model->is_aurora);
        $this->assertTrue($model->is_published);
        $this->assertSame('yes', $model->label_print);
        $this->assertNull($model->image);
        $this->assertNull($model->gallery);

        $features = $model->features()->get();
        $this->assertSame(['Печать этикеток', 'Второй блок'], $features->map->tr('title', 'ru')->all());
        $this->assertSame([1, 2], $features->pluck('sort')->all());
        $this->assertSame(['label', null], $features->pluck('icon')->all());

        $log = ActivityLog::where('action', 'created')->firstOrFail();
        $this->assertSame($model->id, $log->subject_id);
        $this->assertSame(2, $log->changes['features']);
    }

    public function test_new_model_address_follows_the_name_until_typed(): void
    {
        $this->scaleModel(null, ['slug' => 'rls1000-rls1100a']);

        Livewire::test(Form::class)
            ->set('form.name', 'RLS1000/RLS1100A')
            ->assertSet('form.slug', 'rls1000-rls1100a-2')
            ->set('form.name', 'AURORA D2-AI')
            ->assertSet('form.slug', 'aurora-d2-ai')
            ->set('form.slug', 'd2')
            ->set('form.name', 'AURORA S2L-AI')
            ->assertSet('form.slug', 'd2')
            ->call('slugFromName')
            ->assertSet('form.slug', 'aurora-s2l-ai');
    }

    public function test_published_model_keeps_its_address(): void
    {
        $model = $this->scaleModel(null, ['slug' => 'c1', 'name' => 'C1', 'is_published' => true]);

        Livewire::test(Form::class, ['model' => $model])
            ->assertSet('slugLocked', true)
            ->set('form.name', 'C1 Pro')
            ->assertSet('form.slug', 'c1')
            ->set('form.slug', 'c1-pro')
            ->call('slugFromName')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['C1 Pro', 'c1'], [$model->fresh()->name, $model->fresh()->slug]);

        $draft = $this->scaleModel($model->family, ['slug' => 'c3', 'name' => 'C3', 'is_published' => false]);
        Livewire::test(Form::class, ['model' => $draft])
            ->assertSet('slugLocked', false)
            ->set('form.name', 'C3 Plus')
            ->assertSet('form.slug', 'c3')
            ->call('slugFromName')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('c3-plus', $draft->fresh()->slug);
    }

    public function test_model_validation(): void
    {
        $this->scaleModel(null, ['slug' => 'c1']);

        Livewire::test(Form::class)
            ->set('form.slug', 'c1')
            ->set('form.family_id', 999)
            ->set('form.kind', 'spaceship')
            ->set('form.label_print', 'maybe')
            ->set('form.conn.eth', 'always')
            ->set('form.source_url', 'javascript:alert(1)')
            ->call('addFeature')
            ->call('save')
            ->assertHasErrors([
                'form.slug' => 'unique',
                'form.family_id' => 'exists',
                'form.name' => 'required',
                'form.kind' => 'in',
                'form.label_print' => 'in',
                'form.conn.eth' => 'in',
                'form.type.ru' => 'required',
                'form.headline.ru' => 'required',
                'form.intro.ru' => 'required',
                'form.source_url',
                'features.0.title.ru' => 'required',
                'features.0.text.ru' => 'required',
            ])
            ->assertNoRedirect();

        $this->assertSame(1, ScaleModel::count());
        $this->assertSame(0, ModelFeature::count());
    }

    public function test_features_are_reordered_edited_and_removed(): void
    {
        $model = $this->scaleModel();
        $a = ModelFeature::create(['scale_model_id' => $model->id, 'title' => $this->tr('A'), 'text' => $this->tr('a'), 'sort' => 1]);
        $b = ModelFeature::create(['scale_model_id' => $model->id, 'title' => $this->tr('B'), 'text' => $this->tr('b'), 'sort' => 2]);
        $c = ModelFeature::create(['scale_model_id' => $model->id, 'title' => $this->tr('C'), 'text' => $this->tr('c'), 'sort' => 3]);

        Livewire::test(Form::class, ['model' => $model])
            ->assertSet('features.1.id', $b->id)
            ->call('moveFeature', 2, -1)      // A C B
            ->call('removeFeature', 0)        // C B
            ->set('features.1.title.ru', 'B2')
            ->call('save')
            ->assertHasNoErrors();

        $features = $model->features()->get();
        $this->assertSame([$c->id, $b->id], $features->pluck('id')->all());
        $this->assertSame(['C', 'B2'], $features->map->tr('title', 'ru')->all());
        $this->assertNull(ModelFeature::find($a->id));
    }

    public function test_a_feature_id_from_another_model_cannot_be_overwritten(): void
    {
        $model = $this->scaleModel();
        $other = $this->scaleModel($model->family, ['slug' => 'c3', 'name' => 'C3']);
        $foreign = ModelFeature::create(['scale_model_id' => $other->id, 'title' => $this->tr('Чужой'), 'text' => $this->tr('t'), 'sort' => 1]);

        Livewire::test(Form::class, ['model' => $model])
            ->call('addFeature')
            ->set('features.0.id', $foreign->id)
            ->set('features.0.title', $this->tr('Подмена'))
            ->set('features.0.text', $this->tr('t'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Чужой', $foreign->fresh()->tr('title', 'ru'));
        $this->assertSame($other->id, $foreign->fresh()->scale_model_id);
        $this->assertSame(1, $model->features()->count());
    }

    public function test_image_and_gallery_are_uploaded_to_the_public_disk(): void
    {
        $model = $this->scaleModel();

        Livewire::test(Form::class, ['model' => $model])
            ->set('newImage', UploadedFile::fake()->image('main.jpg', 800, 800))
            ->set('newGallery', [UploadedFile::fake()->image('one.png'), UploadedFile::fake()->image('two.webp')])
            ->assertHasNoErrors()
            ->call('save')
            ->assertHasNoErrors();

        $model->refresh();
        $this->assertStringStartsWith('models/', $model->image);
        Storage::disk('public')->assertExists($model->image);
        $this->assertCount(2, $model->gallery);
        foreach ($model->gallery as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_replaced_and_removed_images_are_deleted_only_on_save(): void
    {
        Storage::disk('public')->put('models/old.jpg', 'x');
        Storage::disk('public')->put('models/g1.jpg', 'x');
        Storage::disk('public')->put('models/g2.jpg', 'x');
        $model = $this->scaleModel(null, ['image' => 'models/old.jpg', 'gallery' => ['models/g1.jpg', 'models/g2.jpg']]);

        $page = Livewire::test(Form::class, ['model' => $model])
            ->set('newImage', UploadedFile::fake()->image('new.jpg'))
            ->call('moveGalleryImage', 1, -1)
            ->call('removeGalleryImage', 1);

        // Nothing is lost if the editor leaves without saving.
        Storage::disk('public')->assertExists(['models/old.jpg', 'models/g1.jpg', 'models/g2.jpg']);

        $page->call('save')->assertHasNoErrors();

        $model->refresh();
        $this->assertNotSame('models/old.jpg', $model->image);
        $this->assertSame(['models/g2.jpg'], $model->gallery);
        Storage::disk('public')->assertMissing(['models/old.jpg', 'models/g1.jpg']);
        Storage::disk('public')->assertExists(['models/g2.jpg', $model->image]);
    }

    public function test_upload_rejects_wrong_type_and_oversize(): void
    {
        $model = $this->scaleModel();

        Livewire::test(Form::class, ['model' => $model])
            ->set('newImage', UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'))
            ->assertHasErrors('newImage')
            ->assertSet('newImage', null);

        Livewire::test(Form::class, ['model' => $model])
            ->set('newImage', UploadedFile::fake()->createWithContent('drawing.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'))
            ->assertHasErrors('newImage');

        Livewire::test(Form::class, ['model' => $model])
            ->set('newImage', UploadedFile::fake()->image('huge.jpg')->size(6000))
            ->assertHasErrors('newImage');

        Livewire::test(Form::class, ['model' => $model])
            ->set('newGallery', [UploadedFile::fake()->image('ok.jpg'), UploadedFile::fake()->create('bad.zip', 10)])
            ->assertHasErrors('newGallery.1')
            ->assertSet('newGallery', []);

        $this->assertNull($model->fresh()->image);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_stored_paths_cannot_be_set_from_the_browser(): void
    {
        $model = $this->scaleModel();

        $this->expectExceptionMessageMatches('/locked/i');
        Livewire::test(Form::class, ['model' => $model])->set('image', '../../.env');
    }

    public function test_publish_toggle_from_the_list(): void
    {
        $model = $this->scaleModel();

        Livewire::test(Index::class)->call('togglePublished', $model->id)->assertSee('скрыта');
        $this->assertFalse($model->fresh()->is_published);
        $this->assertDatabaseHas('activity_logs', ['action' => 'unpublished', 'subject_id' => $model->id]);

        Livewire::test(Index::class)->call('togglePublished', $model->id);
        $this->assertTrue($model->fresh()->is_published);
        $this->assertDatabaseHas('activity_logs', ['action' => 'published', 'subject_id' => $model->id]);
    }

    public function test_list_filters_and_search(): void
    {
        $label = $this->family();
        $comm = $this->family(['slug' => 'comm', 'name' => $this->tr('Comm')]);
        $this->scaleModel($label, ['slug' => 'rls1000a', 'name' => 'RLS1000A']);
        $this->scaleModel($comm, ['slug' => 'c1', 'name' => 'C1', 'is_published' => false]);

        $names = fn ($page) => $page->viewData('rows')->pluck('name')->all();

        $this->assertSame(['RLS1000A'], $names(Livewire::test(Index::class)->set('filterFamily', (string) $label->id)));
        $this->assertSame(['C1'], $names(Livewire::test(Index::class)->set('filterPublished', '0')));
        $this->assertSame(['RLS1000A'], $names(Livewire::test(Index::class)->set('filterPublished', '1')));
        $this->assertSame(['RLS1000A'], $names(Livewire::test(Index::class)->set('search', 'rls')));
    }

    public function test_deleting_a_model_removes_its_children_and_files(): void
    {
        Storage::disk('public')->put('models/main.jpg', 'x');
        Storage::disk('public')->put('documents/manual.pdf', 'x');
        $model = $this->scaleModel(null, ['image' => 'models/main.jpg']);
        ModelFeature::create(['scale_model_id' => $model->id, 'title' => $this->tr('A'), 'text' => $this->tr('a'), 'sort' => 1]);
        ModelSpec::create(['scale_model_id' => $model->id, 'group' => 'weighing', 'key' => 'maxCap', 'value' => $this->tr('15 kg'), 'sort' => 1]);
        Document::create(['scale_model_id' => $model->id, 'type' => 'manual', 'file_path' => 'documents/manual.pdf']);

        Livewire::test(Index::class)->call('delete', $model->id);

        $this->assertSame(0, ScaleModel::count() + ModelFeature::count() + ModelSpec::count() + Document::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'subject_type' => ScaleModel::class, 'subject_id' => $model->id]);
    }

    public function test_there_is_no_price_anywhere_in_the_items_area(): void
    {
        $model = $this->scaleModel();

        foreach (['scale_models', 'model_specs', 'model_features', 'documents', 'videos', 'families'] as $table) {
            $this->assertSame([], array_values(preg_grep('/price|cost|amount/i', Schema::getColumnListing($table))));
        }

        foreach (['/admin/models', '/admin/models/create', "/admin/models/{$model->slug}/edit", "/admin/models/{$model->slug}/specs"] as $url) {
            $html = mb_strtolower($this->get($url)->assertOk()->getContent());
            $this->assertStringNotContainsString('price', $html);
            $this->assertStringNotContainsString('стоимость', $html);
            $this->assertStringNotContainsString('сум', $html);
            $this->assertStringNotContainsString('soʻm', $html);
            // The only mention of prices is the note that they are not kept here.
            $this->assertLessThanOrEqual(1, substr_count($html, 'цен'));
        }
    }
}
