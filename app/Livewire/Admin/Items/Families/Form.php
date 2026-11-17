<?php

namespace App\Livewire\Admin\Items\Families;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Items\Catalog;
use App\Models\Family;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create (/admin/families/create) and edit (/admin/families/{family}/edit). */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public ?int $familyId = null;

    public array $form = [];

    public function mount(?Family $family = null): void
    {
        $family = $family?->exists ? $family : null;
        $this->familyId = $family?->id;

        $this->form = [
            'slug' => $family->slug ?? '',
            'name' => $this->transFill($family?->name),
            'line' => $this->transFill($family?->line),
            'pic' => $family->pic ?? '',
            'sort' => $family->sort ?? ((int) Family::max('sort') + 1),
        ];
    }

    protected function rules(): array
    {
        return [
            'form.slug' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('families', 'slug')->ignore($this->familyId)],
            ...$this->transRules('form.name', max: 120),
            ...$this->transRules('form.line', max: 400),
            'form.pic' => ['nullable', Rule::in(array_keys(Catalog::KINDS))],
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.slug' => 'Код',
            'form.pic' => 'Иллюстрация',
            'form.sort' => 'Порядок',
            ...$this->transAttributes('form.name', 'Название'),
            ...$this->transAttributes('form.line', 'Описание'),
        ];
    }

    public function save()
    {
        $this->validate();

        $family = $this->familyId ? Family::findOrFail($this->familyId) : new Family;
        $family->fill([
            'slug' => $this->form['slug'],
            'name' => $this->transClean($this->form['name']),
            'line' => $this->transClean($this->form['line']),
            'pic' => $this->form['pic'] ?: null,
            'sort' => (int) $this->form['sort'],
        ])->save();

        $this->logSaved($family);
        session()->flash('status', 'Семейство сохранено.');

        return redirect()->route('admin.families.index');
    }

    public function render(): View
    {
        $title = $this->familyId ? 'Семейство: '.trv($this->form['name'], 'ru') : 'Новое семейство';

        return view('livewire.admin.items.families-form', ['title' => $title])
            ->layout('components.layouts.admin', ['title' => $title]);
    }
}
