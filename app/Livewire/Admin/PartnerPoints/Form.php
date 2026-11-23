<?php

namespace App\Livewire\Admin\PartnerPoints;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\PartnerPoint;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create (/admin/partner-points/create?kind=term) and edit (/admin/partner-points/{point}/edit). */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    public const KINDS = ['advantage' => 'Преимущество', 'term' => 'Условие'];

    #[Locked]
    public ?int $pointId = null;

    public array $form = [];

    public function mount(?PartnerPoint $point = null): void
    {
        $point = $point?->exists ? $point : null;
        $this->pointId = $point?->id;

        $kind = $point->kind ?? request()->query('kind');
        $kind = in_array($kind, PartnerPoint::KINDS, true) ? $kind : 'advantage';

        $this->form = [
            'kind' => $kind,
            'title' => $this->transFill($point?->title),
            'body' => $this->transFill($point?->body),
            'sort' => $point->sort ?? ((int) PartnerPoint::where('kind', $kind)->max('sort') + 1),
            'is_published' => $point->is_published ?? true,
        ];
    }

    protected function rules(): array
    {
        return [
            'form.kind' => ['required', Rule::in(PartnerPoint::KINDS)],
            ...$this->transRules('form.title', max: 160),
            ...$this->transRules('form.body', required: false, max: 600),
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
            'form.is_published' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.kind' => 'Блок',
            'form.sort' => 'Порядок',
            ...$this->transAttributes('form.title', 'Заголовок'),
            ...$this->transAttributes('form.body', 'Пояснение'),
        ];
    }

    public function save()
    {
        $this->validate();

        $point = $this->pointId ? PartnerPoint::findOrFail($this->pointId) : new PartnerPoint;
        $point->fill([
            'kind' => $this->form['kind'],
            'title' => $this->transClean($this->form['title']),
            'body' => $this->transBlank($this->form['body']) ? null : $this->transClean($this->form['body']),
            'sort' => (int) $this->form['sort'],
            'is_published' => (bool) $this->form['is_published'],
        ])->save();

        $this->logSaved($point);
        session()->flash('status', 'Пункт сохранён.');

        return redirect()->route('admin.partner-points.index');
    }

    public function render(): View
    {
        $title = $this->pointId ? 'Пункт страницы «Партнёрам»' : 'Новый пункт страницы «Партнёрам»';

        return view('livewire.admin.partner-points.form', ['title' => $title])
            ->layout('components.layouts.admin', ['title' => $title]);
    }
}
