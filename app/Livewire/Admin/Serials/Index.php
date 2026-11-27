<?php

namespace App\Livewire\Admin\Serials;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\SerialBatch;
use App\Models\SerialNumber;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** /admin/serials (admins only): find a serial number, the warranty length, and the list of uploaded batches. */
class Index extends Component
{
    use LogsActivity, RequiresStaff, RussianValidation, WithPagination;

    protected bool $adminOnly = true;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** Warranty length in months, one for every model. */
    public string $warrantyMonths = '';

    public function mount(): void
    {
        $this->warrantyMonths = (string) SerialNumber::warrantyMonths();
    }

    public function saveWarranty(): void
    {
        $this->validate(
            ['warrantyMonths' => ['required', 'integer', 'min:1', 'max:120']],
            $this->russianMessages(),
            ['warrantyMonths' => 'Срок гарантии'],
        );

        $this->logSaved(Setting::write('warranty_months', (int) $this->warrantyMonths));
        session()->flash('status', 'Срок гарантии сохранён.');
    }

    public function updatedSearch(): void
    {
        $this->search = mb_substr($this->search, 0, 64);
    }

    public function render(): View
    {
        $needle = SerialNumber::normalize($this->search);

        return view('livewire.admin.serials.index', [
            'needle' => $needle,
            'found' => $needle === '' ? collect() : SerialNumber::with(['scaleModel', 'batch', 'movements'])
                ->where('serial', 'like', '%'.addcslashes($needle, '%_\\').'%')
                ->orderBy('serial')
                ->limit(50)
                ->get(),
            'batches' => SerialBatch::with('user')->latest('id')->paginate(25),
            'total' => SerialNumber::count(),
        ])->layout('components.layouts.admin', ['title' => 'Серийные номера']);
    }
}
