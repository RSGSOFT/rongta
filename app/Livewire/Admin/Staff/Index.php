<?php

namespace App\Livewire\Admin\Staff;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** Staff accounts. Admins only (route middleware staff:admin). Accounts are deactivated, never deleted. */
class Index extends Component
{
    use LogsActivity, RequiresStaff, WithTable;

    protected bool $adminOnly = true;

    protected array $searchable = ['name', 'email'];

    protected array $sortable = ['name', 'email', 'role', 'is_active', 'created_at'];

    protected string $defaultSort = 'name';

    public function toggleActive(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $user = User::findOrFail($id);

        if ($user->is(auth()->user())) {
            session()->flash('error', 'Нельзя отключить собственную учётную запись.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
        if (! $user->is_active) {
            // Ends "remember me" logins; open sessions stop at the next request (staff middleware).
            $user->forceFill(['remember_token' => null])->save();
        }

        $this->logActivity($user->is_active ? 'activated' : 'deactivated', $user, ['is_active' => $user->is_active]);
        session()->flash('status', $user->is_active
            ? "Доступ для {$user->name} включён."
            : "Доступ для {$user->name} отключён.");
    }

    public function render(): View
    {
        return view('livewire.admin.staff.index', [
            'rows' => $this->tableRows(User::query()),
        ])->layout('components.layouts.admin', ['title' => 'Сотрудники']);
    }
}
