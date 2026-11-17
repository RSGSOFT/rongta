<?php

namespace App\Livewire\Admin\Items\Documents;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Items\Catalog;
use App\Models\Document;
use App\Models\ScaleModel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Create (/admin/documents/create?model={id}) and edit (/admin/documents/{document}/edit).
 * The file goes to the public disk, folder "documents"; file_path is relative to that disk.
 */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation, WithFileUploads;

    #[Locked]
    public ?int $documentId = null;

    public array $form = [];

    /** Stored file path on the public disk (changed only through actions). */
    #[Locked]
    public ?string $filePath = null;

    #[Locked]
    public bool $removeFile = false;

    /** New file (temporary upload). */
    public $newFile = null;

    public function mount(?Document $document = null): void
    {
        $document = $document?->exists ? $document : null;
        $this->documentId = $document?->id;

        $presetModel = (int) request()->query('model');

        $this->form = [
            'scale_model_id' => $document->scale_model_id ?? (ScaleModel::whereKey($presetModel)->exists() ? $presetModel : ''),
            'type' => $document->type ?? 'manual',
            'slug' => $document->slug ?? '',
            'title' => $this->transFill($document?->title),
            'format' => $document->format ?? '',
            'version' => $document->version ?? '',
            'released_on' => $document?->released_on?->format('Y-m-d') ?? '',
            'size_label' => $this->transFill($document?->size_label),
            // No file yet = placeholder by default.
            'is_placeholder' => (bool) ($document->is_placeholder ?? true),
            'sort' => $document->sort ?? ((int) Document::max('sort') + 1),
        ];

        $this->filePath = $document?->file_path ?: null;
    }

    protected function rules(): array
    {
        return [
            'form.scale_model_id' => ['required', 'integer', Rule::exists('scale_models', 'id')],
            'form.type' => ['required', Rule::in(Document::TYPES)],
            'form.slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('documents', 'slug')->ignore($this->documentId)],
            ...$this->transRules('form.title', required: false, max: 200),
            'form.format' => ['nullable', 'string', 'max:20'],
            'form.version' => ['nullable', 'string', 'max:30'],
            'form.released_on' => ['nullable', 'date'],
            ...$this->transRules('form.size_label', required: false, max: 30),
            'form.is_placeholder' => ['boolean'],
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
            // The file name must have an allowed extension AND the content must really be that kind of file.
            'newFile' => ['nullable', 'file', 'extensions:'.Catalog::FILE_MIMES, 'max:'.Catalog::FILE_MAX_KB, $this->contentMatchesExtension(...)],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.scale_model_id' => 'Модель',
            'form.type' => 'Тип документа',
            'form.slug' => 'Код',
            'form.format' => 'Формат',
            'form.version' => 'Версия',
            'form.released_on' => 'Дата выпуска',
            'form.sort' => 'Порядок',
            'newFile' => 'Файл',
            ...$this->transAttributes('form.title', 'Название'),
            ...$this->transAttributes('form.size_label', 'Размер'),
        ];
    }

    /**
     * Validation rule: the content type read from the file itself (not from its name) must fit
     * the extension. Stops a script or program renamed to "manual.pdf".
     */
    protected function contentMatchesExtension(string $attribute, mixed $file, \Closure $fail): void
    {
        $extension = Str::lower((string) $file->getClientOriginalExtension());
        $path = $file->getRealPath();
        $detected = $path && is_file($path) ? (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path) : '';

        if (! in_array($detected, Catalog::FILE_CONTENT_TYPES[$extension] ?? [], true)) {
            $fail('Содержимое файла не соответствует расширению .'.$extension.'. Загрузите настоящий файл этого типа.');
        }
    }

    /** Check the upload at once and fill "format" and "size" from it. */
    public function updatedNewFile(): void
    {
        try {
            $this->validateOnly('newFile');
        } catch (ValidationException $e) {
            $this->newFile = null;

            throw $e;
        }

        if ($this->newFile) {
            $this->removeFile = false;
            $this->form['format'] = Str::upper($this->newFile->getClientOriginalExtension());
            $this->form['size_label'] = self::sizeLabel((int) $this->newFile->getSize());
            $this->form['is_placeholder'] = false;
        }
    }

    /** {ru: "3,1 МБ", uz: "3,1 MB", en: "3.1 MB"} */
    public static function sizeLabel(int $bytes): array
    {
        if ($bytes >= 1048576) {
            $number = round($bytes / 1048576, 1);
            $units = ['ru' => 'МБ', 'uz' => 'MB', 'en' => 'MB'];
        } else {
            $number = max(1, (int) round($bytes / 1024));
            $units = ['ru' => 'КБ', 'uz' => 'KB', 'en' => 'KB'];
        }
        $plain = rtrim(rtrim(number_format($number, 1, '.', ''), '0'), '.');
        $comma = str_replace('.', ',', $plain);

        return ['ru' => $comma.' '.$units['ru'], 'uz' => $comma.' '.$units['uz'], 'en' => $plain.' '.$units['en']];
    }

    public function removeStoredFile(): void
    {
        $this->newFile = null;
        if ($this->filePath) {
            $this->removeFile = true;
        }
    }

    public function keepStoredFile(): void
    {
        $this->removeFile = false;
    }

    public function save()
    {
        $this->validate();

        $disk = Storage::disk('public');
        $document = $this->documentId ? Document::findOrFail($this->documentId) : new Document;
        $oldPath = $document->file_path ?: null;
        $path = $this->removeFile ? null : $oldPath;
        $uploaded = false;

        if ($this->newFile) {
            $name = Str::slug(pathinfo($this->newFile->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
            $extension = Str::lower($this->newFile->getClientOriginalExtension());
            // Readable download name plus a random part, so files never overwrite each other.
            $path = $this->newFile->storeAs('documents', Str::limit($name, 80, '').'-'.Str::lower(Str::random(8)).'.'.$extension, 'public');
            $uploaded = true;
        }

        $sizeLabel = $this->transClean($this->form['size_label']);

        try {
            $document->fill([
                'scale_model_id' => (int) $this->form['scale_model_id'],
                'type' => $this->form['type'],
                'slug' => trim((string) $this->form['slug']) ?: null,
                'title' => $this->transBlank($this->form['title']) ? null : $this->transClean($this->form['title']),
                'format' => Str::upper(trim((string) $this->form['format'])) ?: null,
                'version' => trim((string) $this->form['version']) ?: null,
                'released_on' => $this->form['released_on'] ?: null,
                'size_label' => $this->transBlank($sizeLabel) ? null : $sizeLabel,
                'file_path' => $path,
                'is_placeholder' => (bool) $this->form['is_placeholder'],
                'sort' => (int) $this->form['sort'],
            ])->save();
        } catch (\Throwable $e) {
            if ($uploaded) {
                $disk->delete($path);
            }

            throw $e;
        }

        if ($oldPath && $oldPath !== $path) {
            $disk->delete($oldPath);
        }

        $this->logSaved($document);
        session()->flash('status', 'Документ сохранён.');

        return redirect()->route('admin.documents.index', ['model' => $document->scale_model_id]);
    }

    public function render(): View
    {
        $title = $this->documentId ? 'Документ' : 'Новый документ';

        return view('livewire.admin.items.documents-form', [
            'title' => $title,
            'models' => ScaleModel::orderBy('sort')->pluck('name', 'id'),
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
