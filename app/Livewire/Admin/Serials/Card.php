<?php

namespace App\Livewire\Admin\Serials;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\Partner;
use App\Models\SerialMovement;
use App\Models\SerialNumber;
use App\Models\ServiceCase;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * /admin/serials/numbers/{serial} (admins only): one imported scale, its warranty and its history,
 * with a form to record the next step (handover to a dealer, service).
 */
class Card extends Component
{
    use LogsActivity, RequiresStaff, RussianValidation;

    protected bool $adminOnly = true;

    #[Locked]
    public int $serialId;

    public array $form = [];

    public function mount(SerialNumber $serial): void
    {
        $this->serialId = $serial->id;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->form = [
            'type' => SerialMovement::DEALER,
            'happened_on' => now()->format('Y-m-d'),
            'partner_id' => '',
            'service_case_id' => '',
            'note' => '',
        ];
    }

    private function serial(): SerialNumber
    {
        return SerialNumber::with(['scaleModel', 'batch', 'movements.partner', 'movements.serviceCase', 'movements.user', 'movements.cabinetAccount'])->findOrFail($this->serialId);
    }

    protected function rules(): array
    {
        $isDealer = ($this->form['type'] ?? '') === SerialMovement::DEALER;

        return [
            'form.type' => ['required', Rule::in(array_keys(SerialMovement::TYPES))],
            'form.happened_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$this->serial()->imported_on->format('Y-m-d'), 'before_or_equal:today'],
            'form.partner_id' => [$isDealer ? 'required' : 'nullable', Rule::exists('partners', 'id')->where(fn ($query) => $query->whereJsonContains('offers', 'sales'))],
            'form.service_case_id' => ['nullable', Rule::exists('service_cases', 'id')],
            'form.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            ...$this->russianMessages(),
            'form.happened_on.after_or_equal' => 'Дата не может быть раньше даты импорта весов.',
            'form.happened_on.before_or_equal' => 'Дата не может быть в будущем.',
            'form.partner_id.required' => 'Выберите дилера.',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['form.type' => 'Событие', 'form.happened_on' => 'Дата', 'form.partner_id' => 'Дилер', 'form.service_case_id' => 'Работа сервиса', 'form.note' => 'Примечание'];
    }

    public function addMovement(): void
    {
        $this->validate();
        $serial = $this->serial();
        $isDealer = $this->form['type'] === SerialMovement::DEALER;

        if ($isDealer && $serial->movements->contains('type', SerialMovement::DEALER)) {
            $this->addError('form.type', 'Эти весы уже переданы дилеру. Чтобы исправить, удалите прежнюю запись.');

            return;
        }

        $movement = $serial->movements()->create([
            'type' => $this->form['type'],
            'partner_id' => $isDealer ? (int) $this->form['partner_id'] : null,
            'service_case_id' => ! $isDealer && $this->form['service_case_id'] !== '' ? (int) $this->form['service_case_id'] : null,
            'happened_on' => $this->form['happened_on'],
            'note' => trim((string) $this->form['note']) ?: null,
            'user_id' => auth()->id(),
        ]);

        $this->logSaved($movement, ['serial' => $serial->serial]);
        $this->resetForm();
        session()->flash('status', 'Запись добавлена в историю.');
    }

    public function deleteMovement(int $id): void
    {
        $movement = SerialMovement::where('serial_number_id', $this->serialId)->findOrFail($id);
        $movement->delete();

        $this->logDeleted($movement);
        session()->flash('status', 'Запись удалена из истории.');
    }

    public function render(): View
    {
        $serial = $this->serial();

        return view('livewire.admin.serials.card', [
            'serial' => $serial,
            'months' => SerialNumber::warrantyMonths(),
            'dealers' => Partner::sellers()->orderBy('sort')->orderBy('id')->get()->mapWithKeys(fn (Partner $p) => [$p->id => $p->tr('name', 'ru')])->all(),
            'cases' => ServiceCase::where(fn ($q) => $q->whereNull('family_id')->orWhere('family_id', $serial->scaleModel->family_id))
                ->orderBy('sort')->orderBy('id')->get()->mapWithKeys(fn (ServiceCase $c) => [$c->id => $c->tr('title', 'ru')])->all(),
        ])->layout('components.layouts.admin', ['title' => 'Весы '.$serial->serial]);
    }
}
