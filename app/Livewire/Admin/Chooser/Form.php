<?php

namespace App\Livewire\Admin\Chooser;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\ChooserOption;
use App\Models\Family;
use App\Models\ScaleModel;
use App\Models\UiString;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Create (/admin/chooser/create) and edit (/admin/chooser/{option}/edit) one answer of the home page chooser:
 * the button label (interface string "ch.{key}"), the recommended family, the reason and the recommended models.
 */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    public const MAX_MODELS = 6;

    #[Locked]
    public ?int $optionId = null;

    public array $form = [];

    /** Recommended models in display order: [['id' => '12', 'note' => {ru, uz, en}], ...] */
    public array $models = [];

    public function mount(?ChooserOption $option = null): void
    {
        $option = $option?->exists ? $option : null;
        $this->optionId = $option?->id;

        $label = $option ? UiString::where('key', 'ch.'.$option->key)->first() : null;

        $this->form = [
            'key' => $option->key ?? '',
            'label' => $this->transFill(['ru' => $label?->ru, 'uz' => $label?->uz, 'en' => $label?->en]),
            'family_id' => (string) ($option->family_id ?? ''),
            'why' => $this->transFill($option?->why),
            'sort' => $option->sort ?? ((int) ChooserOption::max('sort') + 1),
        ];

        $this->models = $option
            ? $option->scaleModels->map(fn (ScaleModel $m) => ['id' => (string) $m->id, 'note' => $this->transFill($m->pivot->note)])->values()->all()
            : [];
    }

    public function addModel(): void
    {
        if (count($this->models) < self::MAX_MODELS) {
            $this->models[] = ['id' => '', 'note' => $this->transEmpty()];
        }
    }

    public function removeModel(int $index): void
    {
        unset($this->models[$index]);
        $this->models = array_values($this->models);
        $this->resetErrorBag();
    }

    public function moveModel(int $index, int $step): void
    {
        $target = $index + ($step < 0 ? -1 : 1);
        if (! isset($this->models[$index], $this->models[$target])) {
            return;
        }
        [$this->models[$index], $this->models[$target]] = [$this->models[$target], $this->models[$index]];
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        $rules = [
            'form.key' => ['required', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('chooser_options', 'key')->ignore($this->optionId)],
            ...$this->transRules('form.label', max: 60),
            'form.family_id' => ['required', 'integer', Rule::exists('families', 'id')],
            ...$this->transRules('form.why', max: 600),
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
            'models' => ['array', 'max:'.self::MAX_MODELS],
            'models.*.id' => ['required', 'integer', 'distinct', Rule::exists('scale_models', 'id')],
        ];
        foreach (array_keys($this->models) as $i) {
            $rules = [...$rules, ...$this->transRules("models.{$i}.note", required: false, max: 160)];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            ...$this->russianMessages(),
            'form.key.regex' => 'Код: строчные латинские буквы, цифры и подчёркивание; начинается с буквы.',
            'models.*.id.required' => 'Выберите модель или удалите пустую строку.',
            'models.*.id.distinct' => 'Эта модель уже есть в списке.',
        ];
    }

    protected function validationAttributes(): array
    {
        $attributes = [
            'form.key' => 'Код',
            'form.family_id' => 'Семейство',
            'form.sort' => 'Порядок',
            'models' => 'Модели',
            'models.*.id' => 'Модель',
            ...$this->transAttributes('form.label', 'Надпись на кнопке'),
            ...$this->transAttributes('form.why', 'Почему подходит'),
        ];
        foreach (array_keys($this->models) as $i) {
            $attributes = [...$attributes, ...$this->transAttributes("models.{$i}.note", 'Примечание')];
        }

        return $attributes;
    }

    public function save()
    {
        $this->models = array_values($this->models);
        $this->validate();

        $option = DB::transaction(function () {
            $option = $this->optionId ? ChooserOption::findOrFail($this->optionId) : new ChooserOption;
            $before = $option->exists ? $this->pivotState($option) : [];

            $option->fill([
                // The key ties the option to its "ch.{key}" interface string, so it is fixed after creation.
                'key' => $option->exists ? $option->key : $this->form['key'],
                'family_id' => (int) $this->form['family_id'],
                'why' => $this->transClean($this->form['why']),
                'sort' => (int) $this->form['sort'],
            ])->save();

            $sync = [];
            foreach ($this->models as $i => $row) {
                $note = $this->transClean($row['note'] ?? []);
                $sync[(int) $row['id']] = ['note' => $this->transBlank($note) ? null : $note, 'sort' => $i + 1];
            }
            $option->scaleModels()->sync($sync);
            $option->unsetRelation('scaleModels');

            $label = $this->transClean($this->form['label']);
            $string = UiString::firstOrNew(['key' => 'ch.'.$option->key]);
            $string->fill(['ru' => $label['ru'], 'uz' => $label['uz'] !== '' ? $label['uz'] : null, 'en' => $label['en'] !== '' ? $label['en'] : null])->save();

            $extra = [];
            $after = $this->pivotState($option);
            if ($after !== $before) {
                $extra['models'] = $after;
            }
            if ($string->wasRecentlyCreated || $string->wasChanged()) {
                $extra['label'] = $label;
            }
            $this->logSaved($option, $extra);

            return $option;
        });

        session()->flash('status', "Вариант подбора «{$option->key}» сохранён.");

        return redirect()->route('admin.chooser.index');
    }

    /** [['model' => 'c1', 'note' => {...}|null], ...] in display order, for the activity log. */
    protected function pivotState(ChooserOption $option): array
    {
        return $option->scaleModels()->get()
            ->map(fn (ScaleModel $m) => ['model' => $m->slug, 'note' => $m->pivot->note])
            ->values()->all();
    }

    public function render(): View
    {
        $title = $this->optionId ? 'Подбор: '.(trv($this->form['label'], 'ru') ?: $this->form['key']) : 'Новый вариант подбора';

        return view('livewire.admin.chooser.form', [
            'title' => $title,
            'families' => Family::orderBy('sort')->get()->mapWithKeys(fn (Family $f) => [$f->id => $f->tr('name', 'ru')]),
            'allModels' => ScaleModel::orderBy('sort')->get(['id', 'name', 'is_published'])
                ->mapWithKeys(fn (ScaleModel $m) => [$m->id => $m->name.($m->is_published ? '' : ' (скрыта с сайта)')]),
            'max' => self::MAX_MODELS,
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
