<?php

namespace App\Livewire\Pages;

use App\Models\Partner;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Dealers page: region map and list of published partners. The "Become a partner" form is on the For partners page.
 * The region (slug) and the tab are kept in the query string: /dealers?tab=integrator&region=samarkand.
 */
class Dealers extends Component
{
    #[Url]
    public string $region = '';

    #[Url]
    public string $tab = 'reseller';

    public function mount(): void
    {
        $this->normalise();
    }

    public function updated(string $name): void
    {
        if ($name === 'region' || $name === 'tab') {
            $this->normalise();
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->normalise();
    }

    public function setRegion(string $slug): void
    {
        $this->region = $slug;
        $this->normalise();
    }

    /** Unknown values from the URL fall back to "all regions" / "resellers". */
    protected function normalise(): void
    {
        if (! in_array($this->tab, Partner::KINDS, true)) {
            $this->tab = 'reseller';
        }
        if ($this->region !== '' && ! Region::where('slug', $this->region)->exists()) {
            $this->region = '';
        }
    }

    public function render(): View
    {
        $regions = Region::orderBy('sort')->get();
        $current = $this->region !== '' ? $regions->firstWhere('slug', $this->region) : null;

        $all = Partner::published()
            ->where('kind', $this->tab)
            ->with(['region', 'city'])
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return view('livewire.pages.dealers', [
            'regions' => $regions,
            'current' => $current,
            'counts' => $all->countBy('region_id'),
            'partners' => $current ? $all->where('region_id', $current->id)->values() : $all,
        ])->layout('components.layouts.site', ['title' => ui('dl.h'), 'description' => ui('dl.lead')]);
    }
}
