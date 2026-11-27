<?php

namespace App\Livewire\Admin\SerialRequests;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\SerialDetailRequest;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * /admin/serial-requests (admins only): service centres asking for the full history of a scale
 * they have not serviced. "Открыть доступ" shows that scale's history to the whole centre.
 */
class Index extends Component
{
    use LogsActivity, RequiresStaff, WithPagination;

    protected bool $adminOnly = true;

    #[Url(as: 'status', except: '')]
    public string $filterStatus = '';

    /** Optional answer to the service centre, by request id. */
    public array $answers = [];

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function decide(int $id, string $status): void
    {
        if (! in_array($status, [SerialDetailRequest::GRANTED, 'refused'], true)) {
            return;
        }

        $request = SerialDetailRequest::findOrFail($id);
        if ($request->status === $status) {
            return;
        }

        $answer = trim((string) ($this->answers[$id] ?? ''));
        if (mb_strlen($answer) > 500) {
            $this->addError('answers.'.$id, 'Ответ: не больше 500 знаков.');

            return;
        }

        $from = $request->status;
        $request->update(['status' => $status, 'answer' => $answer ?: null, 'decided_at' => now(), 'decided_by' => auth()->id()]);
        unset($this->answers[$id]);

        $this->logActivity('status_changed', $request, ['status' => $status, 'from' => $from]);
    }

    public function render(): View
    {
        $rows = SerialDetailRequest::query()
            ->with(['partner', 'cabinetAccount', 'serialNumber.scaleModel', 'decidedBy'])
            ->when(isset(SerialDetailRequest::STATUSES[$this->filterStatus]), fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByRaw("case when status = 'new' then 0 else 1 end")
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.admin.serial-requests.index', [
            'rows' => $rows,
            'new' => SerialDetailRequest::where('status', 'new')->count(),
        ])->layout('components.layouts.admin', ['title' => 'Запросы по весам']);
    }
}
