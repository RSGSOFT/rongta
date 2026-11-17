<?php

namespace App\Services\Chatbot;

use App\Models\ChooserOption;
use App\Models\Family;
use App\Models\FaqTopic;
use App\Models\GuideCheck;
use App\Models\GuideEntry;
use App\Models\KnowledgeArticle;
use App\Models\ModelSpec;
use App\Models\Partner;
use App\Models\Region;
use App\Models\ScaleModel;
use App\Support\RsgContacts;
use Illuminate\Support\Str;

/**
 * Everything the chatbot is allowed to know, read from the site's own tables.
 *
 * catalogText() is the stable part that goes into the cached system prompt (models,
 * specs, chooser, buying guide, FAQ). dealers() and searchArticles() back the two
 * lookup tools. Nothing here contains a price, and "to be confirmed" values are
 * written as UNKNOWN so the model cannot pass a placeholder off as a fact.
 */
class KnowledgeBase
{
    public const UNKNOWN = 'UNKNOWN (not confirmed)';

    private const CONN_NAMES = ['eth' => 'Ethernet', 'rs232' => 'RS232', 'usb' => 'USB', 'wifi' => 'Wi-Fi', 'bt' => 'Bluetooth'];

    private const ARTICLE_BODY_LIMIT = 4000;

    /** Catalog knowledge in one language. Deterministic: same data gives the same bytes (prompt cache). */
    public function catalogText(string $locale): string
    {
        $out = [];

        $out[] = '## Scale families';
        foreach (Family::query()->orderBy('sort')->orderBy('id')->get() as $family) {
            $out[] = '- '.$family->tr('name', $locale).': '.$family->tr('line', $locale);
        }

        $out[] = '';
        $out[] = '## Models (published catalog)';
        $models = ScaleModel::query()->published()->with(['family', 'features', 'specs', 'documents'])
            ->orderBy('sort')->orderBy('id')->get();
        foreach ($models as $model) {
            $out[] = '';
            $out[] = $this->modelText($model, $locale);
        }

        $out[] = '';
        $out[] = '## Which scale to choose (by place of use)';
        foreach (ChooserOption::query()->with(['family', 'scaleModels'])->orderBy('sort')->orderBy('id')->get() as $option) {
            $out[] = '- '.$option->key.': '.($option->family ? $option->family->tr('name', $locale).'. ' : '').$option->tr('why', $locale);
            foreach ($option->scaleModels as $model) {
                if ($model->is_published) {
                    $out[] = '  - '.$model->name.': '.trv($model->pivot->note, $locale);
                }
            }
        }

        $out[] = '';
        $out[] = '## Buying guide (per family)';
        foreach (GuideEntry::query()->with('family')->orderBy('sort')->orderBy('id')->get() as $entry) {
            $out[] = '- '.($entry->family?->tr('name', $locale) ?? '').': does: '.$entry->tr('does', $locale)
                .' | fits: '.$entry->tr('fits', $locale).' | limits: '.$entry->tr('limit', $locale)
                .' | busy hours: '.$entry->tr('peak', $locale);
        }
        foreach (GuideCheck::query()->orderBy('sort')->orderBy('id')->get() as $check) {
            $out[] = '- Before buying, check: '.$check->tr('text', $locale);
        }

        $out[] = '';
        $out[] = '## FAQ';
        foreach (FaqTopic::query()->with('items')->orderBy('sort')->orderBy('id')->get() as $topic) {
            foreach ($topic->items as $item) {
                $out[] = '- ['.$topic->tr('name', $locale).'] Q: '.$item->tr('question', $locale).' A: '.$item->tr('answer', $locale);
            }
        }

        $titles = KnowledgeArticle::query()->published()->orderBy('id')->limit(150)->get()
            ->map(fn (KnowledgeArticle $a) => '- '.$a->tr('title', $locale))->all();
        if ($titles !== []) {
            $out[] = '';
            $out[] = '## Staff knowledge articles (titles only; read them with the search_knowledge tool)';
            array_push($out, ...$titles);
        }

        // A line that carries a money amount never reaches the model.
        $out = array_filter($out, fn (string $line) => ! PriceGuard::hasAmount($line));

        return implode("\n", $out);
    }

    public function modelText(ScaleModel $model, string $locale): string
    {
        $out = [];
        $out[] = '### '.$model->name.' (id: '.$model->slug.')';
        $out[] = 'Family: '.($model->family?->tr('name', $locale) ?? '').'. Type: '.$model->tr('type', $locale).'.';
        $out[] = 'Summary: '.rtrim($model->tr('headline', $locale), '. ').'. '.$model->tr('intro', $locale);
        $out[] = 'Label printing: '.match ($model->label_print) {
            'yes' => 'yes', 'receipt' => 'receipts only, no labels', default => 'no',
        };

        $conn = [];
        foreach (self::CONN_NAMES as $key => $name) {
            if (isset($model->conn[$key])) {
                $conn[] = $name.' ('.($model->conn[$key] === 'opt' ? 'optional' : 'standard').')';
            }
        }
        $out[] = 'Connections: '.($conn === [] ? self::UNKNOWN : implode(', ', $conn));
        $out[] = 'Page: '.lroute('model', ['slug' => $model->slug], $locale, false);

        foreach ($model->features as $feature) {
            $out[] = 'Feature: '.$feature->tr('title', $locale).': '.$feature->tr('text', $locale);
        }

        $groups = array_flip(ModelSpec::GROUPS);
        $specs = $model->specs->sortBy(fn (ModelSpec $s) => [$groups[$s->group] ?? 99, $s->sort, $s->id]);
        foreach ($specs as $spec) {
            $value = $this->known($spec->text($locale), $locale);
            $note = $spec->note($locale);
            $out[] = 'Spec ['.ui('grp.'.$spec->group, [], $locale).'] '.ui('spec.'.$spec->key, [], $locale).': '.$value
                .($note ? ' (note: '.$note.')' : '');
        }

        $files = $model->documents->where('is_placeholder', false)->whereNotNull('file_path');
        foreach ($files as $document) {
            $out[] = 'Download on the knowledge base page: '.$document->tr('title', $locale).' ('.$document->type.')';
        }

        return implode("\n", $out);
    }

    /**
     * Confirmed dealers for the find_dealers tool. Placeholder (sample) partners are never
     * returned, so the bot cannot present a made-up dealer as real.
     *
     * @return array<string, mixed>
     */
    public function dealers(string $region, string $kind, string $locale): array
    {
        $query = Partner::query()->published()->where('is_placeholder', false)
            ->with(['region', 'city'])->orderBy('sort')->orderBy('id');
        if (in_array($kind, Partner::KINDS, true)) {
            $query->where('kind', $kind);
        }

        $matched = $this->matchRegion($region);
        if ($matched) {
            $query->where('region_id', $matched->id);
        }

        $dealers = $query->limit(25)->get()->map(fn (Partner $p) => array_filter([
            'name' => $p->tr('name', $locale),
            'kind' => $p->kind,
            'region' => $p->region?->tr('name', $locale),
            'city' => $p->city?->tr('name', $locale),
            'offers' => $p->offers,
            'phone' => $p->phone,
            'telegram' => $p->telegram,
            'address' => $p->tr('address', $locale),
        ]))->values()->all();

        $result = [
            'region' => $matched ? $matched->tr('name', $locale) : 'all regions',
            'dealers' => $dealers,
            'dealers_page' => lroute('dealers', [], $locale, false),
        ];
        if (! $matched && $this->isSpecificRegion($region)) {
            $result['note'] = 'Region not recognised; these are dealers from all regions. Known regions: '
                .Region::query()->orderBy('sort')->get()->map(fn (Region $r) => $r->tr('name', $locale))->implode(', ');
        }
        if ($dealers === []) {
            $result['note'] = 'No confirmed dealer is listed here yet. Do not name any dealer. '
                .'Send the visitor to the Dealers page'.($this->rsgContacts() ? ' or to RSG.' : '.');
        }
        if ($contacts = $this->rsgContacts()) {
            $result['rsg_contacts'] = $contacts;
        }

        return $result;
    }

    /**
     * Published staff articles matching the words of a query, best match first.
     *
     * @return array<string, mixed>
     */
    public function searchArticles(string $query, string $modelRef, string $locale): array
    {
        $terms = collect(preg_split('/[^\p{L}\p{N}]+/u', Str::lower($query)) ?: [])
            ->filter(fn (string $t) => mb_strlen($t) >= 2)->unique()->values();
        $model = $this->matchModel($modelRef);

        $scored = KnowledgeArticle::query()->published()->with('scaleModel')->orderBy('id')->get()
            ->map(function (KnowledgeArticle $article) use ($terms, $model) {
                $title = Str::lower(implode(' ', (array) $article->title).' '.implode(' ', (array) $article->tags));
                $body = Str::lower(implode(' ', (array) $article->body));
                $score = 0;
                foreach ($terms as $term) {
                    // Word stems: Russian and Uzbek endings change, so match on the first letters.
                    $stem = mb_strlen($term) > 5 ? mb_substr($term, 0, mb_strlen($term) - 2) : $term;
                    $score += (str_contains($title, $stem) ? 3 : 0) + (str_contains($body, $stem) ? 1 : 0);
                }
                if ($model && $article->scale_model_id === $model->id) {
                    $score += 2;
                }

                return ['article' => $article, 'score' => $score];
            })
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')->take(4);

        $articles = $scored->map(fn (array $row) => [
            'title' => $row['article']->tr('title', $locale),
            'model' => $row['article']->scaleModel?->name,
            'body' => Str::limit($row['article']->tr('body', $locale), self::ARTICLE_BODY_LIMIT),
        ])->filter(fn (array $a) => ! PriceGuard::hasAmount($a['title'].' '.$a['body']))->values()->all();

        return $articles === []
            ? ['articles' => [], 'note' => 'Nothing found in the staff articles. If the catalog knowledge does not answer the question either, it is unanswered.']
            : ['articles' => $articles];
    }

    /** RSG phone and Telegram, or null while they are not confirmed (see RsgContacts). */
    public function rsgContacts(): ?array
    {
        $contacts = RsgContacts::confirmed();
        if ($contacts === null) {
            return null;
        }

        return ['phone' => $contacts['phone']] + ($contacts['telegram'] !== '' ? ['telegram' => '@'.$contacts['telegram']] : []);
    }

    /** "to be confirmed" in any site language becomes an explicit UNKNOWN. */
    private function known(string $value, string $locale): string
    {
        $plain = Str::lower(trim($value));
        if ($plain === '') {
            return self::UNKNOWN;
        }
        foreach (['ru', 'uz', 'en'] as $lang) {
            $tbc = Str::lower(ui('val.tbc', [], $lang));
            if ($tbc !== '' && str_contains($plain, $tbc)) {
                return $plain === $tbc ? self::UNKNOWN : $value.' ['.self::UNKNOWN.' where it says "'.ui('val.tbc', [], $lang).'"]';
            }
        }

        return $value;
    }

    private function isSpecificRegion(string $region): bool
    {
        return ! in_array(Str::lower(trim($region)), ['', 'all', 'any'], true);
    }

    private function matchRegion(string $region): ?Region
    {
        if (! $this->isSpecificRegion($region)) {
            return null;
        }
        $needle = $this->fold($region);
        if (mb_strlen($needle) < 3) {
            return null;
        }

        // Exact names first ("Tashkent" the city before "Tashkent region"), then partial matches.
        $regions = Region::query()->with('cities')->orderBy('sort')->get();
        foreach ([true, false] as $exact) {
            foreach ($regions as $candidate) {
                $names = array_merge([$candidate->slug], (array) $candidate->name, (array) $candidate->short);
                foreach ($candidate->cities as $city) {
                    $names = array_merge($names, [$city->slug], (array) $city->name);
                }
                foreach ($names as $name) {
                    $name = $this->fold((string) $name);
                    if ($name === '') {
                        continue;
                    }
                    if ($exact ? $name === $needle : (str_contains($needle, $name) || str_contains($name, $needle))) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    private function matchModel(string $ref): ?ScaleModel
    {
        $needle = $this->fold($ref);
        if ($needle === '') {
            return null;
        }

        return ScaleModel::query()->published()->get()
            ->first(fn (ScaleModel $m) => $this->fold($m->slug) === $needle || $this->fold($m->name) === $needle);
    }

    /** Lower case, no apostrophes, spaces or dashes: "Toshkent sh." and "toshkent" compare equal enough. */
    private function fold(string $text): string
    {
        return preg_replace('/[\sʻʼ\'’`\-\/.]+/u', '', Str::lower($text)) ?? '';
    }
}
