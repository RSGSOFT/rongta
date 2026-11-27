<?php

namespace App\Livewire\Admin\CabinetAccounts;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Partners\Options;
use App\Models\CabinetAccount;
use App\Models\Partner;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * /admin/cabinet-accounts/{account} (admins only): one partner cabinet registration. An admin
 * links it to a partner and approves it, blocks it, or sets a new password. What the account
 * may do in the cabinet follows the partner's "Продажа" and "Сервис" ticks.
 */
class Show extends Component
{
    use LogsActivity, RequiresStaff, RussianValidation;

    protected bool $adminOnly = true;

    #[Locked]
    public int $accountId;

    public string $partnerId = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(CabinetAccount $account): void
    {
        $this->accountId = $account->id;
        $this->partnerId = (string) ($account->partner_id ?? '');
    }

    private function account(): CabinetAccount
    {
        return CabinetAccount::with(['partner', 'decidedBy'])->findOrFail($this->accountId);
    }

    protected function validationAttributes(): array
    {
        return ['partnerId' => 'Партнёр', 'password' => 'Новый пароль'];
    }

    protected function messages(): array
    {
        return [...$this->russianMessages(), 'partnerId.required' => 'Выберите партнёра: без него кабинет не откроется.'];
    }

    /** Links the account to a partner and opens the cabinet. Also used to change the partner later. */
    public function approve(): void
    {
        $this->validate(['partnerId' => ['required', Rule::exists('partners', 'id')]]);

        $account = $this->account();
        $from = $account->status;
        $account->update([
            'partner_id' => (int) $this->partnerId,
            'status' => 'approved',
            'decided_at' => now(),
            'decided_by' => auth()->id(),
        ]);

        $this->logActivity($from === 'approved' ? 'updated' : 'status_changed', $account, ['status' => 'approved', 'from' => $from, 'partner_id' => $account->partner_id]);
        session()->flash('status', 'Кабинет открыт: '.$account->company.'.');
    }

    public function block(): void
    {
        $account = $this->account();
        if ($account->status === 'blocked') {
            return;
        }

        $from = $account->status;
        $account->forceFill(['status' => 'blocked', 'decided_at' => now(), 'decided_by' => auth()->id(), 'remember_token' => null])->save();

        $this->logActivity('status_changed', $account, ['status' => 'blocked', 'from' => $from]);
        session()->flash('status', 'Кабинет заблокирован.');
    }

    public function savePassword(): void
    {
        $this->validate(['password' => ['required', 'string', 'confirmed', Password::min(8), 'max:255']]);

        $account = $this->account();
        $account->forceFill(['password' => $this->password, 'remember_token' => null])->save();

        $this->logActivity('updated', $account, ['password_changed' => true]);
        $this->reset('password', 'password_confirmation');
        session()->flash('status', 'Новый пароль сохранён. Передайте его партнёру.');
    }

    public function delete()
    {
        $account = $this->account();
        $account->delete();

        $this->logDeleted($account);
        session()->flash('status', 'Кабинет удалён: '.$account->company.'.');

        return redirect()->route('admin.cabinet-accounts.index');
    }

    public function render(): View
    {
        $account = $this->account();
        $offers = fn (Partner $partner) => collect(['sales', 'service'])->filter(fn (string $offer) => in_array($offer, $partner->offers ?? [], true))->map(fn (string $offer) => Options::OFFERS[$offer])->implode(', ');

        return view('livewire.admin.cabinet-accounts.show', [
            'account' => $account,
            'partners' => Partner::orderBy('sort')->orderBy('id')->get()
                ->mapWithKeys(fn (Partner $p) => [$p->id => $p->tr('name', 'ru').' ('.($offers($p) ?: 'нет ни продажи, ни сервиса').')'])->all(),
            'chosen' => $this->partnerId !== '' ? Partner::find((int) $this->partnerId) : null,
        ])->layout('components.layouts.admin', ['title' => 'Кабинет: '.$account->company]);
    }
}
