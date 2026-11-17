<?php

namespace App\Livewire\Admin\Guide;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\GuideCheck;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create (/admin/guide/checks/create) and edit (/admin/guide/checks/{check}/edit) a checklist line. */
class CheckForm extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public ?int $checkId = null;

    public array $form = [];

    public function mount(?GuideCheck $check = null): void
    {
        $check = $check?->exists ? $check : null;
        $this->checkId = $check?->id;

        $this->form = [
            'text' => $this->transFill($check?->text),
            'sort' => $check->sort ?? ((int) GuideCheck::max('sort') + 1),
        ];
    }

    protected function rules(): array
    {
        return [
            ...$this->transRules('form.text', max: 400),
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['form.sort' => 'Порядок', ...$this->transAttributes('form.text', 'Текст пункта')];
    }

    public function save()
    {
        $this->validate();

        $check = $this->checkId ? GuideCheck::findOrFail($this->checkId) : new GuideCheck;
        $check->fill([
            'text' => $this->transClean($this->form['text']),
            'sort' => (int) $this->form['sort'],
        ])->save();

        $this->logSaved($check);
        session()->flash('status', 'Пункт списка сохранён.');

        return redirect()->route('admin.guide.index');
    }

    public function render(): View
    {
        $title = $this->checkId ? 'Пункт списка проверки' : 'Новый пункт списка проверки';

        return view('livewire.admin.guide.check-form', ['title' => $title])
            ->layout('components.layouts.admin', ['title' => $title]);
    }
}
