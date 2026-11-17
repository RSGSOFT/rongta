<?php

namespace App\Livewire\Pages;

use App\Models\ModelSpec;
use App\Models\ScaleModel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Compare page: up to 4 models side by side (3 on phones, enforced in the browser).
 * The list is kept in the session under "compare" as an array of model slugs.
 */
class Compare extends Component
{
    public const SESSION_KEY = 'compare';

    public const MAX = 4;

    public bool $onlyDiff = false;

    /** Slugs in the compare list: published models only, no duplicates, at most MAX, in the order added. */
    public static function slugs(): array
    {
        $raw = session(self::SESSION_KEY, []);
        if (! is_array($raw) || $raw === []) {
            return [];
        }
        $raw = array_values(array_unique(array_filter($raw, 'is_string')));
        $known = ScaleModel::published()->whereIn('slug', $raw)->pluck('slug')->all();

        return array_slice(array_values(array_intersect($raw, $known)), 0, self::MAX);
    }

    /** Store a new list (cleaned the same way) and return what was kept. */
    public static function put(array $slugs): array
    {
        session([self::SESSION_KEY => array_values($slugs)]);
        $clean = self::slugs();
        session([self::SESSION_KEY => $clean]);

        return $clean;
    }

    /** The compare tray changed the list (a tick, "remove" or "clear" anywhere on the page). */
    #[On('compare-updated')]
    public function listChanged(): void
    {
        // Re-render only; the list is read from the session in render().
    }

    public function render(): View
    {
        $slugs = self::slugs();
        $models = ScaleModel::published()
            ->whereIn('slug', $slugs)
            ->with(['family', 'specs'])
            ->get()
            ->sortBy(fn (ScaleModel $m) => array_search($m->slug, $slugs, true))
            ->values();

        return view('livewire.pages.compare', [
            'models' => $models,
            'groups' => $this->groups($models),
        ])->layout('components.layouts.site', ['title' => ui('cmp.h')]);
    }

    /**
     * Table body: [['label' => string, 'rows' => [['label', 'cells', 'diff'], ...]], ...].
     * A cell is ['text' => string] or ['value' => spec value array|null].
     * A row is "diff" when the models do not all carry the same value; with "only differences"
     * switched on (and more than one model) the equal rows are left out.
     */
    protected function groups(Collection $models): array
    {
        if ($models->isEmpty()) {
            return [];
        }

        $many = $models->count() > 1;
        $row = function (string $label, array $cells, array $keys) use ($many): ?array {
            $diff = $many && count(array_unique($keys)) > 1;
            if ($this->onlyDiff && $many && ! $diff) {
                return null;
            }

            return ['label' => $label, 'cells' => $cells, 'diff' => $diff];
        };

        $groups = [];

        $general = array_filter([
            $row(
                ui('fact.family'),
                $models->map(fn (ScaleModel $m) => ['text' => $m->family?->tr('name') ?? ''])->all(),
                $models->map(fn (ScaleModel $m) => (string) $m->family_id)->all(),
            ),
            $row(
                ui('fact.label'),
                $models->map(fn (ScaleModel $m) => ['text' => ui(match ($m->label_print) {
                    'yes' => 'val.yes', 'receipt' => 'val.receipt', default => 'val.no',
                })])->all(),
                $models->map(fn (ScaleModel $m) => (string) $m->label_print)->all(),
            ),
        ]);
        if ($general) {
            $groups[] = ['label' => ui('grp.general'), 'rows' => array_values($general)];
        }

        // Every spec key any of the models has, in catalog order, bucketed by group.
        $byGroup = [];
        foreach ($models as $model) {
            foreach ($model->specs as $spec) {
                $byGroup[$spec->group][$spec->key] = min($byGroup[$spec->group][$spec->key] ?? PHP_INT_MAX, (int) $spec->sort);
            }
        }
        $order = array_merge(ModelSpec::GROUPS, array_diff(array_keys($byGroup), ModelSpec::GROUPS));

        foreach ($order as $group) {
            if (empty($byGroup[$group])) {
                continue;
            }
            asort($byGroup[$group]);
            $rows = [];
            foreach (array_keys($byGroup[$group]) as $key) {
                $specs = $models->map(fn (ScaleModel $m) => $m->specs->firstWhere('key', $key));
                $rows[] = $row(
                    ui('spec.'.$key),
                    $specs->map(fn (?ModelSpec $s) => ['value' => $s?->value])->all(),
                    $specs->map(fn (?ModelSpec $s) => $s ? $s->text('en') : '-')->all(),
                );
            }
            $rows = array_values(array_filter($rows));
            if ($rows) {
                $groups[] = ['label' => ui('grp.'.$group), 'rows' => $rows];
            }
        }

        return $groups;
    }
}
