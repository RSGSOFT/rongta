<?php

namespace App\Livewire\Admin\Guide;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\Family;
use App\Models\GuideEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Create (/admin/guide/entries/create) and edit (/admin/guide/entries/{entry}/edit) the guide column of one family.
 */
class EntryForm extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    /** column => [label, hint, max length] */
    public const FIELDS = [
        'does' => ['Что делают', 'Коротко: для чего эти весы.', 600],
        'fits' => ['Кому подходят', 'Какие магазины и задачи.', 600],
        'limit' => ['Ограничения', 'Чего эти весы не умеют или когда не подходят.', 600],
        'peak' => ['Нагрузка в часы пик', 'Как ведут себя при большом потоке покупателей.', 600],
    ];

    #[Locked]
    public ?int $entryId = null;

    public array $form = [];

    public function mount(?GuideEntry $entry = null): void
    {
        $entry = $entry?->exists ? $entry : null;
        $this->entryId = $entry?->id;

        $this->form = [
            'family_id' => (string) ($entry->family_id ?? ''),
            'sort' => $entry->sort ?? ((int) GuideEntry::max('sort') + 1),
        ];
        foreach (array_keys(self::FIELDS) as $field) {
            $this->form[$field] = $this->transFill($entry?->{$field});
        }
    }

    protected function rules(): array
    {
        $rules = [
            'form.family_id' => ['required', 'integer', Rule::exists('families', 'id'), Rule::unique('guide_entries', 'family_id')->ignore($this->entryId)],
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
        foreach (self::FIELDS as $field => [, , $max]) {
            $rules = [...$rules, ...$this->transRules('form.'.$field, max: $max)];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            ...$this->russianMessages(),
            'form.family_id.unique' => 'Для этого семейства раздел гида уже есть. Откройте его для правки.',
        ];
    }

    protected function validationAttributes(): array
    {
        $attributes = ['form.family_id' => 'Семейство', 'form.sort' => 'Порядок'];
        foreach (self::FIELDS as $field => [$label]) {
            $attributes = [...$attributes, ...$this->transAttributes('form.'.$field, $label)];
        }

        return $attributes;
    }

    public function save()
    {
        $this->validate();

        $data = ['family_id' => (int) $this->form['family_id'], 'sort' => (int) $this->form['sort']];
        foreach (array_keys(self::FIELDS) as $field) {
            $data[$field] = $this->transClean($this->form[$field]);
        }

        $entry = $this->entryId ? GuideEntry::findOrFail($this->entryId) : new GuideEntry;
        $entry->fill($data)->save();

        $this->logSaved($entry);
        session()->flash('status', 'Раздел гида сохранён.');

        return redirect()->route('admin.guide.index');
    }

    public function render(): View
    {
        $taken = GuideEntry::query()->when($this->entryId, fn ($q) => $q->whereKeyNot($this->entryId))->pluck('family_id');
        $families = Family::whereNotIn('id', $taken)->orderBy('sort')->get()->mapWithKeys(fn (Family $f) => [$f->id => $f->tr('name', 'ru')]);

        $title = $this->entryId ? 'Гид: '.($families[(int) $this->form['family_id']] ?? 'семейство') : 'Новый раздел гида';

        return view('livewire.admin.guide.entry-form', [
            'title' => $title,
            'families' => $families,
            'fields' => self::FIELDS,
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
