<?php

namespace App\Livewire\Admin\ServiceCases;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\Family;
use App\Models\ServiceCase;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create (/admin/service-cases/create?family=3) and edit (/admin/service-cases/{case}/edit) a service job. */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public ?int $caseId = null;

    public array $form = [];

    public function mount(?ServiceCase $case = null): void
    {
        $case = $case?->exists ? $case : null;
        $this->caseId = $case?->id;

        $familyId = $case ? $case->family_id : Family::whereKey((int) request()->query('family'))->value('id');

        $this->form = [
            'family_id' => (string) ($familyId ?? ''),
            'title' => $this->transFill($case?->title),
            'note' => $this->transFill($case?->note),
            'amount' => $case->amount ?? '',
            'is_from' => $case->is_from ?? false,
            'sort' => $case->sort ?? ((int) ServiceCase::max('sort') + 1),
            'is_published' => $case->is_published ?? true,
        ];
    }

    protected function rules(): array
    {
        return [
            'form.family_id' => ['nullable', Rule::exists('families', 'id')],
            ...$this->transRules('form.title', max: 160),
            ...$this->transRules('form.note', required: false, max: 300),
            'form.amount' => ['nullable', 'integer', 'min:1', 'max:'.ServiceCase::MAX_AMOUNT],
            'form.is_from' => ['boolean'],
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
            'form.is_published' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            ...$this->russianMessages(),
            'form.amount.*' => 'Целое число от 1 до '.ServiceCase::spaced(ServiceCase::MAX_AMOUNT).', без копеек.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.family_id' => 'Весы',
            'form.sort' => 'Порядок',
            ...$this->transAttributes('form.title', 'Работа'),
            ...$this->transAttributes('form.note', 'Примечание'),
        ];
    }

    public function save()
    {
        $this->form['amount'] = preg_replace('/[\s\x{00A0}]+/u', '', (string) ($this->form['amount'] ?? ''));
        $this->validate();

        $case = $this->caseId ? ServiceCase::findOrFail($this->caseId) : new ServiceCase;
        $case->fill([
            'family_id' => $this->form['family_id'] === '' ? null : (int) $this->form['family_id'],
            'title' => $this->transClean($this->form['title']),
            'note' => $this->transBlank($this->form['note']) ? null : $this->transClean($this->form['note']),
            'amount' => $this->form['amount'] === '' ? null : (int) $this->form['amount'],
            'is_from' => (bool) $this->form['is_from'],
            'sort' => (int) $this->form['sort'],
            'is_published' => (bool) $this->form['is_published'],
        ])->save();

        $this->logSaved($case);
        session()->flash('status', 'Работа сохранена.');

        return redirect()->route('admin.service-cases.index');
    }

    public function render(): View
    {
        $title = $this->caseId ? 'Работа сервиса' : 'Новая работа сервиса';

        return view('livewire.admin.service-cases.form', [
            'title' => $title,
            'families' => Family::orderBy('sort')->get()->mapWithKeys(fn (Family $family) => [$family->id => $family->tr('name', 'ru')])->all(),
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
