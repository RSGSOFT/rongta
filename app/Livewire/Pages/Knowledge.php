<?php

namespace App\Livewire\Pages;

use App\Models\Document;
use App\Models\FaqTopic;
use App\Models\GuideCheck;
use App\Models\GuideEntry;
use App\Models\ScaleModel;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Knowledge base: search and model picker first, then manuals, drivers and PC software,
 * SDK, videos, FAQ and the buying guide. The search looks at documents, videos and FAQ
 * in the language being viewed. State lives in the URL: /knowledge?q=label&model=c1#kb-sdk.
 */
class Knowledge extends Component
{
    /** Section id, heading key, description key, per document type. */
    public const DOC_SECTIONS = [
        'manual' => ['kb-manuals', 'kb.manuals', 'kb.manuals.d'],
        'software' => ['kb-software', 'kb.software', 'kb.software.d'],
        'sdk' => ['kb-sdk', 'kb.sdk', 'kb.sdk.d'],
    ];

    #[Url]
    public string $q = '';

    #[Url]
    public string $model = '';

    public function mount(): void
    {
        $this->q = mb_substr($this->q, 0, 80);
        if ($this->model !== '' && ! ScaleModel::published()->where('slug', $this->model)->exists()) {
            $this->model = '';
        }
    }

    public function updatedQ(): void
    {
        $this->q = mb_substr($this->q, 0, 80);
    }

    public function resetSearch(): void
    {
        $this->q = '';
        $this->model = '';
    }

    /** Lower case, apostrophe variants removed, so "ogirlik" finds "ogʻirlik". */
    public static function fold(string $text): string
    {
        return str_replace(['ʻ', 'ʼ', "'", '`', '’', '‘'], '', mb_strtolower($text));
    }

    public function render(): View
    {
        $needle = self::fold(trim($this->q));
        $matches = fn (string $text): bool => $needle === '' || str_contains(self::fold($text), $needle);

        $models = ScaleModel::published()->orderBy('sort')->get(['id', 'slug', 'name', 'family_id']);
        $picked = $this->model !== '' ? $models->firstWhere('slug', $this->model) : null;

        $documents = Document::with('scaleModel')
            ->whereIn('scale_model_id', $picked ? [$picked->id] : $models->pluck('id'))
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->filter(fn (Document $d) => $matches($d->tr('title').' '.$d->scaleModel->name.' '.$d->format))
            ->groupBy('type');

        $videos = Video::with('family')
            ->when($picked, fn ($query) => $query->where('family_id', $picked->family_id))
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->filter(fn (Video $v) => $matches($v->tr('title')))
            ->values();

        $faq = FaqTopic::with('items')->orderBy('sort')->get()
            ->map(function (FaqTopic $topic) use ($matches) {
                $topic->setRelation('items', $topic->items
                    ->filter(fn ($item) => $matches($item->tr('question').' '.$item->tr('answer')))
                    ->values());

                return $topic;
            })
            ->filter(fn (FaqTopic $topic) => $topic->items->isNotEmpty())
            ->values();

        $sections = collect(self::DOC_SECTIONS)->map(fn (array $s, string $type) => [
            'id' => $s[0],
            'title' => $s[1],
            'description' => $s[2],
            'documents' => $documents->get($type, new Collection),
        ]);

        $faqCount = $faq->sum(fn (FaqTopic $topic) => $topic->items->count());

        return view('livewire.pages.knowledge', [
            'models' => $models,
            'sections' => $sections,
            'videos' => $videos,
            'faq' => $faq,
            'faqCount' => $faqCount,
            'total' => $sections->sum(fn (array $s) => $s['documents']->count()) + $videos->count() + $faqCount,
            'filtering' => $needle !== '' || $picked !== null,
            'searching' => $needle !== '',
            // The buying guide is reference reading, not a search result.
            'guide' => $needle === '' ? GuideEntry::with(['family' => fn ($family) => $family->withAuroraFlag()])->orderBy('sort')->get() : new Collection,
            'checks' => $needle === '' ? GuideCheck::orderBy('sort')->get() : new Collection,
        ])->layout('components.layouts.site', ['title' => ui('kb.h')]);
    }
}
