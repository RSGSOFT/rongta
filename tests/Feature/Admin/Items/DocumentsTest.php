<?php

namespace Tests\Feature\Admin\Items;

use App\Livewire\Admin\Items\Documents\Form;
use App\Livewire\Admin\Items\Documents\Index;
use App\Models\ActivityLog;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs($this->editor());
    }

    /** A file that really starts like a PDF, padded to the wanted size. */
    private function pdf(string $name, int $kilobytes = 10): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, str_pad('%PDF-1.4'.chr(10).'%'.hex2bin('e2e3cfd3').chr(10), $kilobytes * 1024, ' '));
    }

    /** A file that really starts like a ZIP archive. */
    private function zip(string $name, int $kilobytes = 10): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, str_pad('PK'.hex2bin('0304140000000800'), $kilobytes * 1024, chr(0)));
    }

    public function test_document_without_a_file_is_saved_as_a_placeholder(): void
    {
        $model = $this->scaleModel();

        Livewire::test(Form::class)
            ->assertSet('form.is_placeholder', true)
            ->set('form.scale_model_id', $model->id)
            ->set('form.type', 'manual')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.documents.index', ['model' => $model->id]));

        $document = Document::firstOrFail();
        $this->assertNull($document->file_path);
        $this->assertNull($document->title);
        $this->assertNull($document->version);
        $this->assertNull($document->released_on);
        $this->assertNull($document->size_label);
        $this->assertTrue($document->is_placeholder);
        $this->assertDatabaseHas('activity_logs', ['action' => 'created', 'subject_type' => Document::class, 'subject_id' => $document->id]);
    }

    public function test_create_page_preselects_the_model_from_the_query_string(): void
    {
        $model = $this->scaleModel();

        Livewire::withQueryParams(['model' => $model->id])->test(Form::class)->assertSet('form.scale_model_id', $model->id);
        Livewire::withQueryParams(['model' => 999])->test(Form::class)->assertSet('form.scale_model_id', '');
    }

    public function test_file_upload_fills_format_and_size_and_is_stored(): void
    {
        $model = $this->scaleModel();

        Livewire::test(Form::class)
            ->set('form.scale_model_id', $model->id)
            ->set('form.type', 'manual')
            ->set('form.title', $this->tr('Руководство пользователя'))
            ->set('form.version', '1.2')
            ->set('form.released_on', '2026-03-01')
            ->set('newFile', $this->pdf('User Manual C1.pdf', 3200))
            ->assertHasNoErrors()
            ->assertSet('form.format', 'PDF')
            ->assertSet('form.size_label', ['ru' => '3,1 МБ', 'uz' => '3,1 MB', 'en' => '3.1 MB'])
            ->assertSet('form.is_placeholder', false)
            ->call('save')
            ->assertHasNoErrors();

        $document = Document::firstOrFail();
        $this->assertMatchesRegularExpression('~^documents/user-manual-c1-[a-z0-9]{8}\.pdf$~', $document->file_path);
        Storage::disk('public')->assertExists($document->file_path);
        $this->assertSame('PDF', $document->format);
        $this->assertSame('1.2', $document->version);
        $this->assertSame('2026-03-01', $document->released_on->format('Y-m-d'));
        $this->assertSame('3.1 MB', $document->tr('size_label', 'en'));
        $this->assertFalse($document->is_placeholder);
    }

    public function test_size_label_formats(): void
    {
        $this->assertSame(['ru' => '512 КБ', 'uz' => '512 KB', 'en' => '512 KB'], Form::sizeLabel(512 * 1024));
        $this->assertSame(['ru' => '2 МБ', 'uz' => '2 MB', 'en' => '2 MB'], Form::sizeLabel(2 * 1048576));
        $this->assertSame(['ru' => '1 КБ', 'uz' => '1 KB', 'en' => '1 KB'], Form::sizeLabel(10));
    }

    public function test_upload_rejects_executables_disguised_files_and_oversize(): void
    {
        $model = $this->scaleModel();
        $page = fn () => Livewire::test(Form::class)->set('form.scale_model_id', $model->id);

        $page()->set('newFile', UploadedFile::fake()->create('driver.exe', 100))->assertHasErrors('newFile')->assertSet('newFile', null);
        $page()->set('newFile', UploadedFile::fake()->create('shell.php', 1, 'text/x-php'))->assertHasErrors('newFile');
        $page()->set('newFile', UploadedFile::fake()->create('page.html', 1, 'text/html'))->assertHasErrors('newFile');
        // PHP code renamed to .pdf: the content gives it away.
        $page()->set('newFile', UploadedFile::fake()->createWithContent('manual.pdf', "<?php echo 'x'; ?>"))->assertHasErrors('newFile');
        // A Windows program renamed to .zip.
        $page()->set('newFile', UploadedFile::fake()->createWithContent('driver.zip', 'MZ'.hex2bin('900003000000').str_repeat(chr(0), 200)))->assertHasErrors('newFile');
        // An empty file is not a document either.
        $page()->set('newFile', UploadedFile::fake()->create('empty.pdf', 0))->assertHasErrors('newFile');
        // Too big.
        $page()->set('newFile', $this->zip('big.zip', 13000))->assertHasErrors('newFile');

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, Document::count());
    }

    public function test_replacing_a_file_deletes_the_old_one_after_save(): void
    {
        $model = $this->scaleModel();
        Storage::disk('public')->put('documents/old.pdf', 'x');
        $document = Document::create(['scale_model_id' => $model->id, 'type' => 'manual', 'file_path' => 'documents/old.pdf', 'format' => 'PDF']);

        $page = Livewire::test(Form::class, ['document' => $document])
            ->assertSet('filePath', 'documents/old.pdf')
            ->set('newFile', $this->zip('new.zip'))
            ->assertHasNoErrors()
            ->assertSet('form.format', 'ZIP');
        Storage::disk('public')->assertExists('documents/old.pdf');

        $page->call('save')->assertHasNoErrors();

        $document->refresh();
        $this->assertStringEndsWith('.zip', $document->file_path);
        Storage::disk('public')->assertMissing('documents/old.pdf');
        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_removing_the_file_can_be_undone_before_saving(): void
    {
        $model = $this->scaleModel();
        Storage::disk('public')->put('documents/keep.pdf', 'x');
        $document = Document::create(['scale_model_id' => $model->id, 'type' => 'manual', 'file_path' => 'documents/keep.pdf']);

        Livewire::test(Form::class, ['document' => $document])
            ->call('removeStoredFile')
            ->assertSee('Будет удалён при сохранении')
            ->call('keepStoredFile')
            ->call('save');
        $this->assertSame('documents/keep.pdf', $document->fresh()->file_path);
        Storage::disk('public')->assertExists('documents/keep.pdf');

        Livewire::test(Form::class, ['document' => $document])->call('removeStoredFile')->call('save')->assertHasNoErrors();
        $this->assertNull($document->fresh()->file_path);
        Storage::disk('public')->assertMissing('documents/keep.pdf');
    }

    public function test_document_validation(): void
    {
        $model = $this->scaleModel();
        Document::create(['scale_model_id' => $model->id, 'type' => 'manual', 'slug' => 'c1-manual']);

        Livewire::test(Form::class)
            ->set('form.scale_model_id', 999)
            ->set('form.type', 'brochure')
            ->set('form.slug', 'c1-manual')
            ->set('form.released_on', 'yesterday-ish')
            ->set('form.version', str_repeat('9', 31))
            ->set('form.sort', 'abc')
            ->call('save')
            ->assertHasErrors([
                'form.scale_model_id' => 'exists',
                'form.type' => 'in',
                'form.slug' => 'unique',
                'form.released_on' => 'date',
                'form.version' => 'max',
                'form.sort' => 'integer',
            ])
            ->assertNoRedirect();

        $this->assertSame(1, Document::count());
    }

    public function test_stored_file_path_cannot_be_set_from_the_browser(): void
    {
        $this->expectExceptionMessageMatches('/locked/i');
        Livewire::test(Form::class)->set('filePath', '../../.env');
    }

    public function test_list_filters_by_model_type_and_missing_file(): void
    {
        $c1 = $this->scaleModel();
        $c3 = $this->scaleModel($c1->family, ['slug' => 'c3', 'name' => 'C3']);
        Document::create(['scale_model_id' => $c1->id, 'type' => 'manual', 'file_path' => 'documents/a.pdf', 'sort' => 1]);
        Document::create(['scale_model_id' => $c1->id, 'type' => 'sdk', 'sort' => 2]);
        Document::create(['scale_model_id' => $c3->id, 'type' => 'manual', 'sort' => 3]);

        $count = fn ($page) => $page->viewData('rows')->total();

        $this->assertSame(3, $count(Livewire::test(Index::class)));
        $this->assertSame(2, $count(Livewire::test(Index::class)->set('filterModel', (string) $c1->id)));
        $this->assertSame(2, $count(Livewire::test(Index::class)->set('filterType', 'manual')));
        $this->assertSame(2, $count(Livewire::withQueryParams(['file' => 'missing'])->test(Index::class)));
        $this->assertSame(1, $count(Livewire::test(Index::class)->set('filterFile', 'present')));

        Livewire::test(Index::class)->assertSee('нет файла')->assertSee('Руководство');
    }

    public function test_deleting_a_document_removes_its_file(): void
    {
        $model = $this->scaleModel();
        Storage::disk('public')->put('documents/a.pdf', 'x');
        $document = Document::create(['scale_model_id' => $model->id, 'type' => 'manual', 'file_path' => 'documents/a.pdf']);

        Livewire::test(Index::class)->call('delete', $document->id);

        $this->assertSame(0, Document::count());
        Storage::disk('public')->assertMissing('documents/a.pdf');
        $this->assertSame(['deleted'], ActivityLog::pluck('action')->all());
    }
}
