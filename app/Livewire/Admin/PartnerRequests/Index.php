<?php

namespace App\Livewire\Admin\PartnerRequests;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\PartnerRequest;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/** /admin/partner-requests: "Become a partner" form submissions, newest first. */
class Index extends Component
{
    use LogsActivity, RequiresStaff, WithTable;

    protected array $searchable = ['company', 'contact_name', 'phone'];

    protected array $sortable = ['id', 'company', 'status', 'created_at'];

    protected string $defaultSort = 'id';

    protected string $defaultDir = 'desc';

    #[Url(as: 'status', except: '')]
    public string $filterStatus = '';

    #[Url(as: 'type', except: '')]
    public string $filterType = '';

    public function setStatus(int $id, string $status): void
    {
        if (! in_array($status, PartnerRequest::STATUSES, true)) {
            return;
        }

        $request = PartnerRequest::findOrFail($id);
        if ($request->status === $status) {
            return;
        }

        $from = $request->status;
        $request->update(['status' => $status]);
        $this->logActivity('status_changed', $request, ['status' => $status, 'from' => $from]);
    }

    public function render(): View
    {
        $query = PartnerRequest::query()
            ->with('region')
            ->when($this->filterValue($this->filterStatus), fn ($q, $status) => $q->where('status', $status))
            ->when($this->filterValue($this->filterType), fn ($q, $type) => $q->where('type', $type));

        return view('livewire.admin.partner-requests.index', [
            'rows' => $this->tableRows($query),
            'new' => PartnerRequest::where('status', 'new')->count(),
        ])->layout('components.layouts.admin', ['title' => 'Заявки на партнёрство']);
    }
}
