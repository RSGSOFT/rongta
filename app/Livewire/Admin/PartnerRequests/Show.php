<?php

namespace App\Livewire\Admin\PartnerRequests;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\PartnerRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * /admin/partner-requests/{partnerRequest}: one request and its status.
 * The table has no notes column, so only the status is kept here.
 */
class Show extends Component
{
    use LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public int $requestId;

    public string $status = 'new';

    public function mount(PartnerRequest $partnerRequest): void
    {
        $this->requestId = $partnerRequest->id;
        $this->status = $partnerRequest->status;
    }

    protected function rules(): array
    {
        return ['status' => ['required', Rule::in(PartnerRequest::STATUSES)]];
    }

    protected function validationAttributes(): array
    {
        return ['status' => 'Статус'];
    }

    public function save(): void
    {
        $this->validate();

        $request = PartnerRequest::findOrFail($this->requestId);
        if ($request->status !== $this->status) {
            $from = $request->status;
            $request->update(['status' => $this->status]);
            $this->logActivity('status_changed', $request, ['status' => $this->status, 'from' => $from]);
        }

        session()->flash('status', 'Статус заявки сохранён.');
    }

    public function delete()
    {
        $request = PartnerRequest::findOrFail($this->requestId);
        $request->delete();

        $this->logDeleted($request);
        session()->flash('status', 'Заявка удалена.');

        return redirect()->route('admin.partner-requests.index');
    }

    public function render(): View
    {
        $request = PartnerRequest::with('region')->findOrFail($this->requestId);

        return view('livewire.admin.partner-requests.show', ['request' => $request])
            ->layout('components.layouts.admin', ['title' => 'Заявка: '.$request->company]);
    }
}
