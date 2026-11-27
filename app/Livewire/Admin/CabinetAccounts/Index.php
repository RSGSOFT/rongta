<?php

namespace App\Livewire\Admin\CabinetAccounts;

use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\CabinetAccount;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/** /admin/cabinet-accounts (admins only): partner cabinet registrations, newest first. */
class Index extends Component
{
    use RequiresStaff, WithTable;

    protected bool $adminOnly = true;

    protected array $searchable = ['company', 'contact_name', 'phone', 'email'];

    protected array $sortable = ['id', 'company', 'status', 'created_at'];

    protected string $defaultSort = 'id';

    protected string $defaultDir = 'desc';

    #[Url(as: 'status', except: '')]
    public string $filterStatus = '';

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = CabinetAccount::query()
            ->with('partner')
            ->when($this->filterValue($this->filterStatus), fn ($q, $status) => $q->where('status', $status));

        return view('livewire.admin.cabinet-accounts.index', [
            'rows' => $this->tableRows($query),
            'pending' => CabinetAccount::where('status', 'pending')->count(),
        ])->layout('components.layouts.admin', ['title' => 'Кабинеты партнёров']);
    }
}
