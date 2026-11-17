<?php

namespace App\Livewire\Pages;

use App\Models\ChooserOption;
use App\Models\Family;
use App\Models\ScaleModel;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Home page. Section order and visibility come from setting('home.sections'):
 * hero, chooser, families, aurora, trust, knowledge, partners.
 */
class Home extends Component
{
    public const SECTIONS = ['hero', 'chooser', 'families', 'aurora', 'trust', 'knowledge', 'partners'];

    /** Selected answer of "Which scale do you need?" (chooser_options.key). */
    public string $chooser = 'shop';

    public function pick(string $key): void
    {
        $this->chooser = $key;
    }

    /** Keys of the visible sections, in display order. Unknown keys are ignored. */
    public static function sections(): array
    {
        $stored = setting('home.sections');
        if (! is_array($stored) || $stored === []) {
            return self::SECTIONS;
        }

        $keys = [];
        foreach ($stored as $section) {
            $key = $section['key'] ?? null;
            if (in_array($key, self::SECTIONS, true) && ($section['visible'] ?? true) && ! in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public function render(): View
    {
        $sections = self::sections();
        $data = ['sections' => $sections];

        if (in_array('chooser', $sections, true)) {
            $options = ChooserOption::query()
                ->with(['family', 'scaleModels' => fn ($q) => $q->where('is_published', true)])
                ->orderBy('sort')
                ->get();
            $data['options'] = $options;
            $data['option'] = $options->firstWhere('key', $this->chooser) ?? $options->first();
        }

        if (in_array('families', $sections, true)) {
            $data['families'] = Family::query()
                ->with(['scaleModels' => fn ($q) => $q->where('is_published', true)])
                ->orderBy('sort')
                ->get()
                ->filter(fn (Family $family) => $family->scaleModels->isNotEmpty())
                ->values();
        }

        if (in_array('aurora', $sections, true)) {
            $aurora = ScaleModel::published()->where('is_aurora', true)->orderBy('sort')->get();
            $data['aurora'] = $aurora;
            // The picture shows an AURORA model with a camera when there is one.
            $data['auroraPic'] = $aurora->first(fn (ScaleModel $m) => $m->kind === 'aipc' && ! empty($m->illustration['cam']))
                ?? $aurora->first();
            $data['auroraFamily'] = $aurora->first()?->family;
        }

        return view('livewire.pages.home', $data)
            ->layout('components.layouts.site', ['title' => '', 'description' => ui('hero.lead')]);
    }
}
