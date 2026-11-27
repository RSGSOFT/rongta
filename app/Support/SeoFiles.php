<?php

namespace App\Support;

use App\Models\Family;
use App\Models\FaqTopic;
use App\Models\ModelSpec;
use App\Models\ScaleModel;
use Illuminate\Support\Collection;
use XMLWriter;

/**
 * Machine-read files built from the database: sitemap.xml, robots.txt, llms.txt and llms-full.txt.
 * Nothing here carries a price, an offer or a figure that is not stored in the database.
 */
class SeoFiles
{
    /** Indexable public pages without parameters. Compare is left out: it is noindex. */
    public const PAGES = ['home', 'catalog', 'dealers', 'partners', 'knowledge', 'serial'];

    /** Language names used in llms.txt, which is written in English. */
    public const LANGUAGE_NAMES = ['ru' => 'Russian', 'uz' => 'Uzbek', 'en' => 'English'];

    public static function sitemap(): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');

        $entries = array_map(fn (string $page) => [$page, [], null], self::PAGES);
        foreach (self::models() as $model) {
            $entries[] = ['model', ['slug' => $model->slug], $model->updated_at?->toAtomString()];
        }

        foreach ($entries as [$page, $parameters, $lastmod]) {
            $urls = [];
            foreach (Locales::ALL as $locale) {
                $urls[$locale] = lroute($page, $parameters, $locale);
            }
            foreach ($urls as $url) {
                $xml->startElement('url');
                $xml->writeElement('loc', $url);
                if ($lastmod) {
                    $xml->writeElement('lastmod', $lastmod);
                }
                foreach ($urls + ['x-default' => $urls[Locales::DEFAULT]] as $hreflang => $href) {
                    $xml->startElement('xhtml:link');
                    $xml->writeAttribute('rel', 'alternate');
                    $xml->writeAttribute('hreflang', $hreflang);
                    $xml->writeAttribute('href', $href);
                    $xml->endElement();
                }
                $xml->endElement();
            }
        }

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    public static function robots(): string
    {
        return implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ]);
    }

    /** Short site summary for AI assistants (llmstxt.org format), in English with links in all three languages. */
    public static function llms(): string
    {
        return implode("\n", self::llmsLines(full: false))."\n";
    }

    /** llms.txt plus each model's features and specifications, and the FAQ. */
    public static function llmsFull(): string
    {
        return implode("\n", self::llmsLines(full: true))."\n";
    }

    /** @return list<string> */
    protected static function llmsLines(bool $full): array
    {
        $en = 'en';
        $lines = [
            '# '.config('app.name'),
            '',
            '> '.self::line(ui('ftr.about', [], $en)).' '.self::line(ui('ftr.dist', [], $en)).'. Rongta is the manufacturer (https://www.rongtatech.com).',
            '',
            'The site is in Russian (default), Uzbek (Latin script) and English:',
        ];
        foreach (Locales::ALL as $locale) {
            $lines[] = '- '.self::LANGUAGE_NAMES[$locale].': '.lroute('home', [], $locale);
        }

        $models = self::models();
        $families = Family::orderBy('sort')->get()
            ->map(fn (Family $family) => [$family, $models->where('family_id', $family->id)->values()])
            ->filter(fn (array $group) => $group[1]->isNotEmpty());

        $lines[] = '';
        $lines[] = '## Scales';
        foreach ($families as [$family, $familyModels]) {
            $lines[] = '';
            $lines[] = '### '.self::line($family->tr('name', $en));
            if (($about = self::line($family->tr('line', $en))) !== '') {
                $lines[] = '';
                $lines[] = $about;
            }
            $lines[] = '';
            foreach ($familyModels as $model) {
                $lines[] = self::modelLine($model);
            }
        }

        $lines[] = '';
        $lines[] = '## Where to buy and get help';
        $lines[] = '';
        $lines[] = self::pageLine(ui('cta.dealer', [], $en), 'dealers', ui('dl.lead', [], $en));
        $lines[] = self::pageLine(ui('nav.partner', [], $en), 'partners', ui('ps.lead', [], $en));
        $lines[] = self::pageLine(ui('nav.knowledge', [], $en), 'knowledge', ui('kb.lead', [], $en));
        $lines[] = self::pageLine(ui('sn.h', [], $en), 'serial', ui('sn.lead', [], $en));
        $lines[] = self::pageLine(ui('nav.catalog', [], $en), 'catalog', 'All '.$models->count().' models, with filters and a side-by-side comparison.');

        if ($contacts = RsgContacts::confirmed()) {
            $lines[] = '';
            $lines[] = '## Contact RSG';
            $lines[] = '';
            $lines[] = '- Phone: '.$contacts['phone'];
            if ($contacts['telegram'] !== '') {
                $lines[] = '- Telegram: https://t.me/'.$contacts['telegram'];
            }
        }

        if ($full) {
            foreach ($models as $model) {
                array_push($lines, '', ...self::modelDetails($model));
            }
            array_push($lines, ...self::faqLines());
        } else {
            $lines[] = '';
            $lines[] = '## Optional';
            $lines[] = '';
            $lines[] = '- [Full details]('.url('/llms-full.txt').'): every model\'s features and specifications, and the FAQ';
            $lines[] = '- [Manufacturer](https://www.rongtatech.com): Rongta\'s own site';
        }

        return $lines;
    }

    /** Published models in catalog order, with what the files need. */
    protected static function models(): Collection
    {
        return ScaleModel::published()->with('family')->orderBy('sort')->get();
    }

    protected static function modelLine(ScaleModel $model): string
    {
        $about = trim(self::line($model->tr('type', 'en')).'. '.self::line($model->tr('intro', 'en')), ' .');
        $others = collect(Locales::ALL)
            ->reject(fn (string $locale) => $locale === 'en')
            ->map(fn (string $locale) => self::LANGUAGE_NAMES[$locale].': '.lroute('model', ['slug' => $model->slug], $locale))
            ->implode('; ');

        return '- ['.$model->name.']('.lroute('model', ['slug' => $model->slug], 'en').')'
            .($about !== '' ? ': '.$about.'.' : '')
            .' ('.$others.')';
    }

    protected static function pageLine(string $name, string $page, string $about): string
    {
        $others = collect(Locales::ALL)
            ->reject(fn (string $locale) => $locale === 'en')
            ->map(fn (string $locale) => self::LANGUAGE_NAMES[$locale].': '.lroute($page, [], $locale))
            ->implode('; ');

        return '- ['.self::line($name).']('.lroute($page, [], 'en').'): '.self::line($about).' ('.$others.')';
    }

    /** @return list<string> */
    protected static function modelDetails(ScaleModel $model): array
    {
        $model->loadMissing(['features', 'specs']);
        $lines = ['## '.$model->name, '', 'Page: '.lroute('model', ['slug' => $model->slug], 'en')];
        foreach (['type', 'headline', 'intro'] as $field) {
            if (($text = self::line($model->tr($field, 'en'))) !== '') {
                $lines[] = '';
                $lines[] = $text;
            }
        }

        if ($model->features->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '### Features';
            $lines[] = '';
            foreach ($model->features as $feature) {
                $lines[] = '- '.self::line($feature->tr('title', 'en')).': '.self::line($feature->tr('text', 'en'));
            }
        }

        $byGroup = $model->specs->groupBy('group');
        $order = array_merge(ModelSpec::GROUPS, array_diff($byGroup->keys()->all(), ModelSpec::GROUPS));
        if ($byGroup->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '### Specifications';
        }
        foreach ($order as $group) {
            if (! $byGroup->has($group)) {
                continue;
            }
            $lines[] = '';
            $lines[] = '#### '.self::line(ui('grp.'.$group, [], 'en'));
            $lines[] = '';
            foreach ($byGroup[$group] as $spec) {
                $note = $spec->note('en');
                $lines[] = '- '.self::line(ui('spec.'.$spec->key, [], 'en')).': '.self::line($spec->text('en'))
                    .($note ? ' ('.self::line($note).')' : '');
            }
        }

        return $lines;
    }

    /** @return list<string> */
    protected static function faqLines(): array
    {
        $topics = FaqTopic::with('items')->orderBy('sort')->get()->filter(fn (FaqTopic $topic) => $topic->items->isNotEmpty());
        if ($topics->isEmpty()) {
            return [];
        }

        $lines = ['', '## FAQ'];
        foreach ($topics as $topic) {
            $lines[] = '';
            $lines[] = '### '.self::line($topic->tr('name', 'en'));
            foreach ($topic->items as $item) {
                $lines[] = '';
                $lines[] = '**'.self::line($item->tr('question', 'en')).'**';
                $lines[] = self::line($item->tr('answer', 'en'));
            }
        }

        return $lines;
    }

    /** One line of plain text: tags stripped, line breaks and runs of spaces collapsed (non-breaking spaces kept). */
    protected static function line(string $text): string
    {
        return trim((string) preg_replace('/[ \t\r\n\f\v]+/', ' ', strip_tags($text)));
    }
}
