<?php

namespace App\Livewire\Admin\Staff;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Create (/admin/staff/create) and edit (/admin/staff/{user}/edit) a staff account.
 * Admins only. An admin cannot deactivate or demote their own account.
 */
class Form extends Component
{
    use LogsActivity, RequiresStaff, RussianValidation;

    protected bool $adminOnly = true;

    #[Locked]
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'editor';

    public bool $is_active = true;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(?User $user = null): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if ($user?->exists) {
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->role;
            $this->is_active = (bool) $user->is_active;
        }
    }

    protected function isSelf(): bool
    {
        return $this->userId !== null && $this->userId === auth()->id();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'role' => ['required', Rule::in(User::ROLES)],
            'is_active' => ['boolean'],
            'password' => [$this->userId ? 'nullable' : 'required', 'string', 'confirmed', Password::min(10)->letters()->numbers(), 'max:255'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'Имя',
            'email' => 'Эл. почта',
            'role' => 'Роль',
            'password' => 'Пароль',
        ];
    }

    public function save()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $this->email = mb_strtolower(trim($this->email));
        $this->validate();

        if ($this->isSelf() && ($this->role !== 'admin' || ! $this->is_active)) {
            throw ValidationException::withMessages([
                $this->role !== 'admin' ? 'role' : 'is_active' => 'Нельзя понизить роль или отключить собственную учётную запись. Попросите другого администратора.',
            ]);
        }

        $user = $this->userId ? User::findOrFail($this->userId) : new User;
        $user->fill([
            'name' => trim($this->name),
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->is_active,
        ]);

        $passwordChanged = $this->password !== '';
        if ($passwordChanged) {
            $user->password = $this->password; // hashed by the model cast
        }
        if ($passwordChanged || ! $this->is_active) {
            $user->remember_token = null;
        }
        $user->save();

        $this->logSaved($user, $passwordChanged && ! $user->wasRecentlyCreated ? ['password_changed' => true] : []);
        session()->flash('status', "Учётная запись {$user->name} сохранена.");

        return redirect()->route('admin.staff.index');
    }

    public function render(): View
    {
        $title = $this->userId ? 'Сотрудник: '.$this->name : 'Новый сотрудник';

        return view('livewire.admin.staff.form', ['title' => $title, 'self' => $this->isSelf()])
            ->layout('components.layouts.admin', ['title' => $title]);
    }
}
