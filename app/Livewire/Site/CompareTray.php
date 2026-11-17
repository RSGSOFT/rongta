<?php

namespace App\Livewire\Site;

use App\Livewire\Pages\Compare;
use App\Models\ScaleModel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Compare tray, rendered by the site layout on every page. It is the single writer of the
 * session compare list: the "Compare" ticks, the tray buttons and the compare page all send
 * their changes here, and it answers with a "compare-updated" event (slugs) for the browser
 * and for the compare page.
 */
class CompareTray extends Component
{
    /** @var list<string> */
    public array $slugs = [];

    public function mount(): void
    {
        $this->slugs = Compare::slugs();
    }

    #[On('compare-toggle')]
    public function toggle(string $slug, bool $on = true): void
    {
        $list = Compare::slugs();

        if (! $on) {
            $list = array_values(array_diff($list, [$slug]));
        } elseif (! in_array($slug, $list, true)) {
            if (count($list) >= Compare::MAX) {
                $this->dispatch('toast', message: ui('cmp.limit', ['n' => Compare::MAX]));
            } else {
                $list[] = $slug;
            }
        }

        $this->store($list);
    }

    public function remove(string $slug): void
    {
        $this->toggle($slug, false);
    }

    #[On('compare-clear')]
    public function clear(): void
    {
        $this->store([]);
    }

    protected function store(array $list): void
    {
        $this->slugs = Compare::put($list);
        $this->dispatch('compare-updated', slugs: $this->slugs);
    }

    public function render(): View
    {
        $names = $this->slugs
            ? ScaleModel::published()->whereIn('slug', $this->slugs)->pluck('name', 'slug')
            : collect();

        return view('livewire.site.compare-tray', [
            'items' => collect($this->slugs)
                ->filter(fn (string $slug) => $names->has($slug))
                ->mapWithKeys(fn (string $slug) => [$slug => $names[$slug]]),
        ]);
    }
}
