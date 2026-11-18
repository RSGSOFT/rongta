<?php

namespace App\Livewire\Admin\Items\Models;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Items\Catalog;
use App\Models\Family;
use App\Models\ModelFeature;
use App\Models\ScaleModel;
use App\Services\Catalog\AltTextGenerator;
use App\Services\Catalog\SeoGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * Create (/admin/models/create) and edit (/admin/models/{model}/edit) one scale model:
 * main fields, photo and gallery (public disk, folder "models"), and the feature blocks.
 * Specs have their own screen (Specs). There is no price field.
 */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation, WithFileUploads;

    #[Locked]
    public ?int $modelId = null;

    public array $form = [];

    /** Feature blocks: [['id' => ?int, 'icon' => '', 'title' => {ru,uz,en}, 'text' => {ru,uz,en}], ...] */
    public array $features = [];

    /** Stored main photo path on the public disk (changed only through actions). */
    #[Locked]
    public ?string $image = null;

    /** Stored gallery paths on the public disk. */
    #[Locked]
    public array $gallery = [];

    /** Stored files to delete from the disk once the form is saved. */
    #[Locked]
    public array $removedFiles = [];

    /** A published model keeps its page address: links and search results point to it. */
    #[Locked]
    public bool $slugLocked = false;

    /** The address follows the name until staff type their own. */
    #[Locked]
    public bool $slugAuto = false;

    /** Result line of the "Generate SEO" button. */
    #[Locked]
    public ?string $seoStatus = null;

    #[Locked]
    public ?string $seoProblem = null;

    /** Photo descriptions (alt texts): md5(stored path) => {ru, uz, en}. */
    public array $alts = [];

    /** Result line of the "Describe photos" button. */
    #[Locked]
    public ?string $altStatus = null;

    #[Locked]
    public ?string $altProblem = null;

    /** New main photo (temporary upload). */
    public $newImage = null;

    /** New gallery images (temporary uploads). */
    public array $newGallery = [];

    public function mount(?ScaleModel $model = null): void
    {
        $model = $model?->exists ? $model : null;
        $this->modelId = $model?->id;

        $conn = [];
        foreach (array_keys(Catalog::CONN) as $key) {
            $state = $model->conn[$key] ?? '';
            $conn[$key] = in_array($state, ['std', 'opt'], true) ? $state : '';
        }

        $this->form = [
            'slug' => $model->slug ?? '',
            'family_id' => $model->family_id ?? '',
            'name' => $model->name ?? '',
            'kind' => $model->kind ?? '',
            'head' => (string) ($model->illustration['head'] ?? ''),
            'cam' => (bool) ($model->illustration['cam'] ?? false),
            'receipt' => (bool) ($model->illustration['receipt'] ?? false),
            'dual' => (bool) ($model->illustration['dual'] ?? false),
            'is_aurora' => (bool) ($model->is_aurora ?? false),
            'label_print' => $model->label_print ?? 'no',
            'conn' => $conn,
            'type' => $this->transFill($model?->type),
            'headline' => $this->transFill($model?->headline),
            'intro' => $this->transFill($model?->intro),
            'seo_title' => $this->transFill($model?->seo_title),
            'seo_description' => $this->transFill($model?->seo_description),
            'source_url' => $model->source_url ?? '',
            'sort' => $model->sort ?? ((int) ScaleModel::max('sort') + 1),
            // A new model starts hidden: it has no specs yet.
            'is_published' => (bool) ($model->is_published ?? false),
        ];

        $this->slugLocked = (bool) ($model->is_published ?? false);
        $this->slugAuto = $model === null;

        $this->image = $model?->image ?: null;
        $this->gallery = array_values(array_filter($model?->gallery ?? [], 'is_string'));
        foreach ($model?->photoPaths() ?? [] as $path) {
            $this->alts[md5($path)] = $this->transFill($model->image_alts[$path] ?? null);
        }

        $this->features = $model
            ? $model->features->map(fn (ModelFeature $f) => [
                'id' => $f->id,
                'icon' => (string) $f->icon,
                'title' => $this->transFill($f->title),
                'text' => $this->transFill($f->text),
            ])->all()
            : [];
    }

    protected function rules(): array
    {
        $rules = [
            'form.slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('scale_models', 'slug')->ignore($this->modelId)],
            'form.family_id' => ['required', 'integer', Rule::exists('families', 'id')],
            'form.name' => ['required', 'string', 'max:120'],
            'form.kind' => ['required', Rule::in(array_keys(Catalog::KINDS))],
            'form.head' => ['nullable', Rule::in(array_keys(Catalog::HEADS))],
            'form.cam' => ['boolean'],
            'form.receipt' => ['boolean'],
            'form.dual' => ['boolean'],
            'form.is_aurora' => ['boolean'],
            'form.label_print' => ['required', Rule::in(array_keys(Catalog::LABEL_PRINT))],
            'form.conn' => ['array'],
            'form.conn.*' => ['nullable', Rule::in(['', 'std', 'opt'])],
            ...$this->transRules('form.type', max: 160),
            ...$this->transRules('form.headline', max: 200),
            ...$this->transRules('form.intro', max: 1000),
            ...$this->transRules('form.seo_title', required: false, max: SeoGenerator::TITLE_MAX + 20),
            ...$this->transRules('form.seo_description', required: false, max: SeoGenerator::DESCRIPTION_MAX + 20),
            'form.source_url' => ['nullable', 'url:http,https', 'max:255'],
            'alts' => ['array'],
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
            'form.is_published' => ['boolean'],

            'newImage' => ['nullable', 'image', 'mimes:'.Catalog::IMAGE_MIMES, 'max:'.Catalog::IMAGE_MAX_KB],
            'newGallery' => ['array', 'max:'.max(0, Catalog::GALLERY_MAX - count($this->gallery))],
            'newGallery.*' => ['image', 'mimes:'.Catalog::IMAGE_MIMES, 'max:'.Catalog::IMAGE_MAX_KB],

            'features' => ['array', 'max:12'],
            'features.*.icon' => ['nullable', Rule::in(Catalog::FEATURE_ICONS)],
        ];

        foreach (array_keys($this->alts) as $key) {
            $rules += $this->transRules("alts.{$key}", required: false, max: AltTextGenerator::ALT_MAX + 25);
        }

        foreach (array_keys($this->features) as $i) {
            $rules += $this->transRules("features.{$i}.title", max: 160);
            $rules += $this->transRules("features.{$i}.text", max: 600);
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $names = [
            'form.slug' => 'Адрес (slug)',
            'form.family_id' => 'Семейство',
            'form.name' => 'Название модели',
            'form.kind' => 'Тип рисунка',
            'form.head' => 'Голова весов',
            'form.label_print' => 'Печать',
            'form.conn.*' => 'Подключение',
            'form.source_url' => 'Страница производителя',
            'form.sort' => 'Порядок',
            'newImage' => 'Фото',
            'newGallery' => 'Галерея',
            'newGallery.*' => 'Изображение галереи',
            'features' => 'Преимущества',
            'features.*.icon' => 'Значок',
            ...$this->transAttributes('form.type', 'Тип весов'),
            ...$this->transAttributes('form.headline', 'Заголовок'),
            ...$this->transAttributes('form.intro', 'Вступление'),
            ...$this->transAttributes('form.seo_title', 'Заголовок для поиска'),
            ...$this->transAttributes('form.seo_description', 'Описание для поиска'),
        ];

        foreach (array_keys($this->alts) as $key) {
            $names += $this->transAttributes("alts.{$key}", 'Описание фото');
        }

        foreach (array_keys($this->features) as $i) {
            $n = $i + 1;
            $names += $this->transAttributes("features.{$i}.title", "Преимущество {$n}: заголовок");
            $names += $this->transAttributes("features.{$i}.text", "Преимущество {$n}: текст");
        }

        return $names;
    }

    /** Validate an upload as soon as it arrives, so a wrong file never waits for "Save". */
    public function updatedForm(mixed $value, string $key): void
    {
        if ($key === 'name' && $this->slugAuto && ! $this->slugLocked && trim((string) $value) !== '') {
            $this->form['slug'] = ScaleModel::slugFrom((string) $value, $this->modelId);
        }
        if ($key === 'slug') {
            $this->slugAuto = false;
        }
    }

    /**
     * "Generate SEO": Claude writes the search title and description in three languages from
     * the facts in the form (and the saved specs). Only the form is filled; staff save it.
     */
    public function generateSeo(SeoGenerator $generator): void
    {
        $this->seoProblem = $this->seoStatus = null;

        $model = $this->modelId ? ScaleModel::with(['family', 'specs', 'features'])->findOrFail($this->modelId) : new ScaleModel;
        $model->forceFill([
            'name' => trim((string) $this->form['name']),
            'family_id' => (int) $this->form['family_id'] ?: null,
            'is_aurora' => (bool) $this->form['is_aurora'],
            'label_print' => $this->form['label_print'],
            'conn' => array_filter($this->form['conn'], fn ($state) => in_array($state, ['std', 'opt'], true)),
            'type' => $this->transClean($this->form['type']),
            'headline' => $this->transClean($this->form['headline']),
            'intro' => $this->transClean($this->form['intro']),
        ]);
        $model->setRelation('family', $model->family_id ? Family::find($model->family_id) : null);

        try {
            $seo = $generator->generate($model);
            $this->form['seo_title'] = $seo['title'];
            $this->form['seo_description'] = $seo['description'];
            $this->resetValidation(['form.seo_title.*', 'form.seo_description.*']);
            $this->seoStatus = 'Готово. Проверьте тексты и нажмите «Сохранить».';
        } catch (RuntimeException $e) {
            $this->seoProblem = $e->getMessage();
        } finally {
            if ($this->modelId) {
                $reply = $generator->lastReply;
                $this->logActivity('seo_generated', $model, [
                    'ok' => $this->seoProblem === null,
                    'input_tokens' => $reply?->inputTokens,
                    'output_tokens' => $reply?->outputTokens,
                ]);
            }
        }
    }

    /**
     * Written photo descriptions of the photos the model keeps, ready to store; null when none.
     *
     * @param  list<string>  $paths
     * @return array<string, array{ru: string, uz: string, en: string}>|null
     */
    private function altsFor(array $paths): ?array
    {
        $out = [];
        foreach ($paths as $path) {
            $alt = $this->alts[md5($path)] ?? null;
            if ($alt !== null && ! $this->transBlank($alt)) {
                $out[$path] = $this->transClean($alt);
            }
        }

        return $out ?: null;
    }

    /**
     * "Describe photos": Claude writes alt texts for saved photos that have no Russian
     * description yet, a few at a time. Only the form is filled; staff save it.
     */
    public function generateAlts(AltTextGenerator $generator): void
    {
        $this->altStatus = $this->altProblem = null;
        if (! $this->modelId || ! $this->image) {
            $this->altProblem = 'Сначала загрузите фото и сохраните модель.';

            return;
        }

        $paths = array_values(array_filter(
            array_unique([$this->image, ...$this->gallery]),
            fn (string $path) => trim((string) ($this->alts[md5($path)]['ru'] ?? '')) === '',
        ));
        if ($paths === []) {
            $this->altProblem = 'У всех фото уже есть описания. Чтобы написать заново, очистите поле RU у нужного фото.';

            return;
        }
        $batch = array_slice($paths, 0, AltTextGenerator::MAX_PHOTOS);

        $model = ScaleModel::findOrFail($this->modelId);
        $model->forceFill(['name' => trim((string) $this->form['name']) ?: $model->name, 'type' => $this->transClean($this->form['type'])]);

        try {
            foreach ($generator->generate($model, $batch) as $path => $alt) {
                $this->alts[md5($path)] = $alt;
                $this->resetValidation('alts.'.md5($path).'.*');
            }
            $left = count($paths) - count($batch);
            $this->altStatus = 'Готово: описано фото '.count($batch).'.'
                .($left > 0 ? " Без описания ещё {$left}: нажмите кнопку снова." : '')
                .' Проверьте тексты и нажмите «Сохранить».';
        } catch (RuntimeException $e) {
            $this->altProblem = $e->getMessage();
        } finally {
            $reply = $generator->lastReply;
            $this->logActivity('alt_generated', $model, [
                'photos' => count($batch),
                'ok' => $this->altProblem === null,
                'input_tokens' => $reply?->inputTokens,
                'output_tokens' => $reply?->outputTokens,
            ]);
        }
    }

    /** "From the name" button next to the address field. */
    public function slugFromName(): void
    {
        if (! $this->slugLocked && trim((string) $this->form['name']) !== '') {
            $this->form['slug'] = ScaleModel::slugFrom((string) $this->form['name'], $this->modelId);
            $this->resetValidation('form.slug');
        }
    }

    public function updatedNewImage(): void
    {
        try {
            $this->validateOnly('newImage');
        } catch (ValidationException $e) {
            $this->newImage = null;

            throw $e;
        }
    }

    public function updatedNewGallery(): void
    {
        try {
            $this->validate(array_intersect_key($this->rules(), array_flip(['newGallery', 'newGallery.*'])));
        } catch (ValidationException $e) {
            $this->newGallery = [];

            throw $e;
        }
    }

    public function removeImage(): void
    {
        if ($this->image) {
            $this->removedFiles[] = $this->image;
            $this->image = null;
        }
        $this->newImage = null;
    }

    public function removeGalleryImage(int $index): void
    {
        if (isset($this->gallery[$index])) {
            $this->removedFiles[] = $this->gallery[$index];
            unset($this->gallery[$index]);
            $this->gallery = array_values($this->gallery);
        }
    }

    public function removeNewGalleryImage(int $index): void
    {
        unset($this->newGallery[$index]);
        $this->newGallery = array_values($this->newGallery);
    }

    public function moveGalleryImage(int $index, int $step): void
    {
        $this->gallery = $this->moved($this->gallery, $index, $step);
    }

    public function addFeature(): void
    {
        if (count($this->features) >= 12) {
            return;
        }
        $this->features[] = ['id' => null, 'icon' => '', 'title' => $this->transEmpty(), 'text' => $this->transEmpty()];
    }

    public function removeFeature(int $index): void
    {
        unset($this->features[$index]);
        $this->features = array_values($this->features);
        $this->resetErrorBag();
    }

    public function moveFeature(int $index, int $step): void
    {
        $this->features = $this->moved($this->features, $index, $step);
        $this->resetErrorBag();
    }

    private function moved(array $list, int $index, int $step): array
    {
        $list = array_values($list);
        $target = $index + ($step < 0 ? -1 : 1);
        if (isset($list[$index], $list[$target])) {
            [$list[$index], $list[$target]] = [$list[$target], $list[$index]];
        }

        return $list;
    }

    public function save()
    {
        if ($this->slugLocked) {
            $this->form['slug'] = ScaleModel::whereKey($this->modelId)->value('slug');
        } elseif (trim((string) $this->form['slug']) === '' && trim((string) $this->form['name']) !== '') {
            $this->form['slug'] = ScaleModel::slugFrom((string) $this->form['name'], $this->modelId);
        }

        $this->validate();

        $disk = Storage::disk('public');
        $stored = [];

        try {
            $model = DB::transaction(function () use (&$stored) {
                $model = $this->modelId ? ScaleModel::findOrFail($this->modelId) : new ScaleModel;

                $image = $this->image;
                if ($this->newImage) {
                    if ($image) {
                        $this->removedFiles[] = $image;
                    }
                    $image = $stored[] = $this->newImage->store('models', 'public');
                }

                $gallery = $this->gallery;
                foreach ($this->newGallery as $file) {
                    $gallery[] = $stored[] = $file->store('models', 'public');
                }

                $model->fill([
                    'slug' => $this->form['slug'],
                    'family_id' => (int) $this->form['family_id'],
                    'name' => trim($this->form['name']),
                    'kind' => $this->form['kind'],
                    'illustration' => [
                        'head' => $this->form['head'] ?: null,
                        'cam' => (bool) $this->form['cam'],
                        'receipt' => (bool) $this->form['receipt'],
                        'dual' => (bool) $this->form['dual'],
                    ],
                    'is_aurora' => (bool) $this->form['is_aurora'],
                    'label_print' => $this->form['label_print'],
                    'conn' => array_filter($this->form['conn'], fn ($state) => in_array($state, ['std', 'opt'], true)),
                    'type' => $this->transClean($this->form['type']),
                    'headline' => $this->transClean($this->form['headline']),
                    'intro' => $this->transClean($this->form['intro']),
                    'seo_title' => $this->transBlank($this->form['seo_title']) ? null : $this->transClean($this->form['seo_title']),
                    'seo_description' => $this->transBlank($this->form['seo_description']) ? null : $this->transClean($this->form['seo_description']),
                    'source_url' => trim((string) $this->form['source_url']) ?: null,
                    'image' => $image,
                    'gallery' => $gallery ?: null,
                    'image_alts' => $this->altsFor($image ? [$image, ...$gallery] : []),
                    'sort' => (int) $this->form['sort'],
                    'is_published' => (bool) $this->form['is_published'],
                ])->save();

                $featuresChanged = $this->saveFeatures($model);
                $this->logSaved($model, $featuresChanged ? ['features' => count($this->features)] : []);

                return $model;
            });
        } catch (\Throwable $e) {
            $disk->delete($stored);

            throw $e;
        }

        // Only now are replaced and removed files really deleted.
        $disk->delete(array_values(array_unique($this->removedFiles)));

        session()->flash('status', "Модель {$model->name} сохранена.");

        return redirect()->route('admin.models.edit', $model);
    }

    /** Sync the feature blocks; returns true when anything was written. */
    private function saveFeatures(ScaleModel $model): bool
    {
        $existing = $model->features()->get()->keyBy('id');
        $changed = false;
        $kept = [];

        foreach (array_values($this->features) as $i => $row) {
            $feature = ($row['id'] ?? null) && $existing->has($row['id']) ? $existing[$row['id']] : new ModelFeature(['scale_model_id' => $model->id]);
            $feature->fill([
                'icon' => $row['icon'] ?: null,
                'title' => $this->transClean($row['title']),
                'text' => $this->transClean($row['text']),
                'sort' => $i + 1,
            ]);
            $changed = $changed || ! $feature->exists || $feature->isDirty();
            $feature->save();
            $kept[] = $feature->id;
        }

        $gone = $existing->keys()->diff($kept);
        if ($gone->isNotEmpty()) {
            ModelFeature::whereIn('id', $gone)->delete();
            $changed = true;
        }

        return $changed;
    }

    public function render(): View
    {
        $title = $this->modelId ? 'Модель: '.($this->form['name'] ?: '…') : 'Новая модель';

        return view('livewire.admin.items.models-form', [
            'title' => $title,
            'model' => $this->modelId ? ScaleModel::withCount(['specs', 'documents'])->find($this->modelId) : null,
            'seoAvailable' => app(SeoGenerator::class)->available(),
            'altPhotos' => $this->image ? array_values(array_unique([$this->image, ...$this->gallery])) : [],
            'families' => Family::orderBy('sort')->get()->mapWithKeys(fn ($f) => [$f->id => $f->tr('name', 'ru')]),
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
