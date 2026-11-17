<?php

namespace App\Livewire\Admin\PartnerUsers;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\PartnerUser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/** /admin/partner-users: logins of the partner area. Admins only. */
class Index extends Component
{
    use LogsActivity, RequiresStaff, WithTable;

    protected bool $adminOnly = true;

    protected array $searchable = ['name', 'login'];

    protected array $sortable = ['name', 'login', 'type', 'created_at'];

    protected string $defaultSort = 'name';

    #[Url(as: 'type', except: '')]
    public string $filterType = '';

    /** '' | active | off */
    #[Url(as: 'status', except: '')]
    public string $filterStatus = '';

    public function toggleActive(int $id): void
    {
        $user = PartnerUser::findOrFail($id);
        $user->is_active = ! $user->is_active;
        if (! $user->is_active) {
            $user->remember_token = null;
        }
        $user->save();

        $this->logActivity($user->is_active ? 'activated' : 'deactivated', $user, ['is_active' => $user->is_active]);
    }

    public function delete(int $id): void
    {
        $user = PartnerUser::findOrFail($id);
        $user->delete();

        $this->logDeleted($user);
        session()->flash('status', "Доступ «{$user->login}» удалён.");
    }

    public function render(): View
    {
        $query = PartnerUser::query()
            ->with('partner')
            ->when($this->filterValue($this->filterType), fn ($q, $type) => $q->where('type', $type))
            ->when($this->filterStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filterStatus === 'off', fn ($q) => $q->where('is_active', false));

        return view('livewire.admin.partner-users.index', [
            'rows' => $this->tableRows($query),
        ])->layout('components.layouts.admin', ['title' => 'Доступы партнёров']);
    }
}
