<?php

namespace App\Livewire\Cabinet;

use App\Livewire\Cabinet\Concerns\RequiresCabinet;
use App\Models\SerialMovement;
use App\Models\SerialNumber;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Dealer section of the cabinet: the scales RSG handed over to this partner, with the handover
 * date and the warranty. Never another partner's scales.
 */
class Scales extends Component
{
    use RequiresCabinet, WithPagination;

    public const PER_PAGE = 25;

    protected string $needs = 'sales';

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $partnerId = $this->partner()->id;
        $handedHere = fn ($query) => $query->where('type', SerialMovement::DEALER)->where('partner_id', $partnerId);
        $search = SerialNumber::normalize(mb_substr($this->search, 0, 64));

        $scales = SerialNumber::query()
            ->whereHas('movements', $handedHere)
            ->when($search !== '', fn ($query) => $query->where('serial', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->with(['scaleModel', 'movements'])
            ->orderByDesc('id')
            ->simplePaginate(self::PER_PAGE);

        return view('livewire.cabinet.scales', [
            'scales' => $scales,
            'total' => SerialNumber::whereHas('movements', $handedHere)->count(),
            'searching' => $search !== '',
        ]);
    }
}
