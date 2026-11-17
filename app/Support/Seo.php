<?php

namespace App\Support;

use App\Livewire\Pages\ModelPage;
use App\Models\FaqItem;
use App\Models\ScaleModel;
use Illuminate\Support\HtmlString;

/**
 * Search engine and link preview data for the public pages: canonical address, hreflang
 * alternates, Open Graph image and the schema.org JSON-LD graph.
 *
 * Project rules apply here too: no prices, offers or currency in any node, and only facts
 * that exist in the database or in confirmed settings (RSG's phone and Telegram appear only
 * once RsgContacts confirms them).
 */
class Seo
{
    /** Open Graph locale per site language. */
    public const OG_LOCALES = ['ru' => 'ru_RU', 'uz' => 'uz_UZ', 'en' => 'en_US'];

    /** Image types link previews can show. SVG is left out on purpose. */
    public const RASTER = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public const DEFAULT_LOGO = 'images/rongta-logo.png';

    public const MANUFACTURER_URL = 'https://www.rongtatech.com';

    public const COUNTRY = ['ru' => 'Узбекистан', 'uz' => 'Oʻzbekiston', 'en' => 'Uzbekistan'];

    public const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE;

    /**
     * Canonical address of the page being viewed: same page, same language, no query string.
     * Every query parameter on this site (catalog filters, knowledge search and model picker,
     * dealer region and tab) only narrows the content of the same page, so it is never canonical.
     */
    public static function canonical(): string
    {
        return Locales::currentUrlIn(app()->getLocale(), false);
    }

    /**
     * hreflang => URL for every language, plus x-default (the Russian page).
     *
     * @return array<string, string>
     */
    public static function alternates(): array
    {
        $urls = [];
        foreach (Locales::ALL as $locale) {
            $urls[$locale] = Locales::currentUrlIn($locale, false);
        }
        $urls['x-default'] = $urls[Locales::DEFAULT];

        return $urls;
    }

    public static function isRaster(?string $path): bool
    {
        if (! is_string($path) || trim($path) === '') {
            return false;
        }
        $extension = strtolower(pathinfo((string) parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));

        return in_array($extension, self::RASTER, true);
    }

    /** Make a root-relative or protocol-relative URL absolute. */
    public static function absolute(string $url): string
    {
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        return str_starts_with($url, '/') ? url($url) : $url;
    }

    /** The full site logo from the settings (a path under public/, as the logo component reads it), or the bundled one. */
    public static function logoUrl(): string
    {
        $path = (string) setting('logo.logo', '');
        if (! self::isRaster($path)) {
            return asset(self::DEFAULT_LOGO);
        }

        return preg_match('#^(https?:)?//#', $path) || str_starts_with($path, '/') ? self::absolute($path) : asset($path);
    }

    /**
     * Raster photos of a model (main image first), as absolute URLs.
     *
     * @return list<string>
     */
    public static function modelImages(ScaleModel $model): array
    {
        if (! filled($model->image)) {
            return [];
        }
        $paths = array_merge([$model->image], array_values(array_filter((array) $model->gallery, 'is_string')));

        return array_values(array_map(
            fn (string $path) => self::absolute(ModelPage::fileUrl($path)),
            array_filter($paths, self::isRaster(...)),
        ));
    }

    /** "#rsg" => "https://rongta.uz/#rsg": stable node ids shared by every page and language. */
    public static function nodeId(string $fragment): string
    {
        return rtrim(url('/'), '/').'/#'.$fragment;
    }

    /** RSG, the official distributor in Uzbekistan. Contacts only when the owner has confirmed them. */
    public static function organization(): array
    {
        $locale = app()->getLocale();
        $node = [
            '@type' => 'Organization',
            '@id' => self::nodeId('rsg'),
            'name' => 'RSG',
            'description' => ui('ftr.dist'),
            'url' => lroute('home'),
            'brand' => ['@id' => self::nodeId('rongta')],
            'areaServed' => ['@type' => 'Country', 'name' => self::COUNTRY[$locale] ?? self::COUNTRY['en']],
        ];

        $sameAs = [];
        $website = trim((string) setting('contacts.distributor_url', ''));
        if (preg_match('#^https?://#i', $website)) {
            $sameAs[] = $website;
        }

        $contacts = RsgContacts::confirmed();
        if ($contacts) {
            $node['contactPoint'] = [
                '@type' => 'ContactPoint',
                'telephone' => $contacts['tel'],
                'contactType' => 'customer service',
                'areaServed' => 'UZ',
                'availableLanguage' => ['ru', 'uz', 'en'],
            ];
            if ($contacts['telegram'] !== '') {
                $sameAs[] = 'https://t.me/'.$contacts['telegram'];
            }
        }

        if ($sameAs) {
            $node['sameAs'] = $sameAs;
        }

        return $node;
    }

    /** Rongta, the manufacturer's brand. */
    public static function brand(): array
    {
        return [
            '@type' => 'Brand',
            '@id' => self::nodeId('rongta'),
            'name' => 'Rongta',
            'url' => self::MANUFACTURER_URL,
            'logo' => self::logoUrl(),
        ];
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::nodeId('website'),
            'name' => config('app.name'),
            'url' => lroute('home'),
            'inLanguage' => app()->getLocale(),
            'publisher' => ['@id' => self::nodeId('rsg')],
            'about' => ['@id' => self::nodeId('rongta')],
        ];
    }

    /** One scale model. No offers: the site never shows a price (owner decision). */
    public static function product(ScaleModel $model): array
    {
        $url = lroute('model', ['slug' => $model->slug]);
        $node = [
            '@type' => 'Product',
            '@id' => $url.'#product',
            'name' => $model->name,
            'model' => $model->name,
            'url' => $url,
            'brand' => ['@id' => self::nodeId('rongta')],
        ];

        $description = $model->tr('intro') ?: $model->tr('headline');
        if ($description !== '') {
            $node['description'] = $description;
        }
        $category = $model->family?->tr('name') ?? '';
        if ($category !== '') {
            $node['category'] = $category;
        }
        if ($images = self::modelImages($model)) {
            $node['image'] = $images;
        }
        if (preg_match('#^https?://#i', (string) $model->source_url)) {
            $node['sameAs'] = $model->source_url;
        }

        return $node;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $items  [name, url] from the home page down
     */
    public static function breadcrumbs(array $items): array
    {
        $list = [];
        foreach (array_values($items) as $i => [$name, $url]) {
            $list[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => $url];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $list];
    }

    /**
     * FAQ shown on the page, in the current language. Null when there is nothing to list.
     *
     * @param  iterable<FaqItem>  $items
     */
    public static function faqPage(iterable $items): ?array
    {
        $questions = [];
        foreach ($items as $item) {
            $question = trim(strip_tags($item->tr('question')));
            $answer = trim(strip_tags($item->tr('answer')));
            if ($question !== '' && $answer !== '') {
                $questions[] = [
                    '@type' => 'Question',
                    'name' => $question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
                ];
            }
        }

        return $questions ? ['@type' => 'FAQPage', 'mainEntity' => $questions] : null;
    }

    /**
     * The page's JSON-LD script: the site-wide nodes (RSG, Rongta, the website) plus the page's own.
     *
     * @param  list<array|null>  $nodes
     */
    public static function script(array $nodes = []): HtmlString
    {
        $graph = array_values(array_filter([self::organization(), self::brand(), self::website(), ...$nodes]));
        $json = json_encode(['@context' => 'https://schema.org', '@graph' => $graph], self::JSON_FLAGS);

        return new HtmlString('<script type="application/ld+json">'.$json.'</script>');
    }
}
