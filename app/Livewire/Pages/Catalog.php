<?php

namespace App\Livewire\Pages;

use App\Models\Family;
use App\Models\ScaleModel;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Catalog with filters. The filters live in the query string, so links such as
 * lroute('catalog', ['family' => 'ai']) open the catalog already filtered.
 */
class Catalog extends Component
{
    public const CONN_KEYS = ['eth', 'rs232', 'usb', 'wifi', 'bt'];

    /** Family slug (label | ai | comm) or "all". */
    #[Url(except: 'all')]
    public string $family = 'all';

    /** "15", "30" or "all". Every model comes in both versions, so this never hides a model. */
    #[Url(except: 'all')]
    public string $cap = 'all';

    /** Label printing: "yes", "no" or "all". */
    #[Url(except: 'all')]
    public string $label = 'all';

    /** Connection code (eth, rs232, usb, wifi, bt) or "all". Standard and optional interfaces both count. */
    #[Url(except: 'all')]
    public string $conn = 'all';

    public function resetFilters(): void
    {
        $this->family = $this->cap = $this->label = $this->conn = 'all';
    }

    public function render(): View
    {
        $families = Family::query()->withAuroraFlag()->orderBy('sort')->get();

        // Anything unexpected in the query string falls back to "all".
        if (! $families->contains('slug', $this->family)) {
            $this->family = 'all';
        }
        if (! in_array($this->cap, ['15', '30'], true)) {
            $this->cap = 'all';
        }
        if (! in_array($this->label, ['yes', 'no'], true)) {
            $this->label = 'all';
        }
        if (! in_array($this->conn, self::CONN_KEYS, true)) {
            $this->conn = 'all';
        }

        $all = ScaleModel::published()->with('family')->orderBy('sort')->get();

        $list = $all->filter(function (ScaleModel $m) {
            if ($this->family !== 'all' && $m->family?->slug !== $this->family) {
                return false;
            }
            if ($this->label === 'yes' && $m->label_print !== 'yes') {
                return false;
            }
            if ($this->label === 'no' && $m->label_print === 'yes') {
                return false;
            }
            if ($this->conn !== 'all' && empty($m->conn[$this->conn])) {
                return false;
            }

            return true;
        });

        $names = (array) setting('connection_names', []);

        return view('livewire.pages.catalog', [
            'families' => $families,
            'total' => $all->count(),
            'count' => $list->count(),
            'groups' => $families
                ->map(fn (Family $f) => ['family' => $f, 'models' => $list->where('family_id', $f->id)->values()])
                ->filter(fn (array $g) => $g['models']->isNotEmpty())
                ->values(),
            'filtered' => $this->family !== 'all' || $this->cap !== 'all' || $this->label !== 'all' || $this->conn !== 'all',
            'connNames' => collect(self::CONN_KEYS)->mapWithKeys(fn (string $k) => [$k => $names[$k] ?? strtoupper($k)])->all(),
        ])->layout('components.layouts.site', [
            'title' => ui('cat.h'),
            'description' => ui('cat.lead'),
            'schema' => [Seo::breadcrumbs([[ui('nav.home'), lroute('home')], [ui('nav.catalog'), lroute('catalog')]])],
        ]);
    }
}
