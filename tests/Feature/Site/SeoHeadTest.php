<?php

namespace Tests\Feature\Site;

use App\Models\FaqItem;
use App\Models\ScaleModel;
use App\Models\Setting;
use PHPUnit\Framework\Attributes\DataProvider;

/** Canonical, hreflang, Open Graph, robots and JSON-LD in the <head> of public pages. */
class SeoHeadTest extends SiteTestCase
{
    /** Schema.org properties that carry a price or an offer. None may ever appear (owner decision). */
    public const PRICE_KEYS = ['offers', 'price', 'priceCurrency', 'priceRange', 'priceSpecification', 'lowPrice', 'highPrice', 'currenciesAccepted'];

    /** @return list<array<string, mixed>> the @graph nodes of the page's JSON-LD script */
    protected function graph(string $html): array
    {
        $this->assertSame(1, preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m), 'Exactly one JSON-LD script');
        $data = json_decode($m[1][0], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $data['@context']);

        return $data['@graph'];
    }

    protected function node(array $graph, string $type): ?array
    {
        foreach ($graph as $node) {
            if (($node['@type'] ?? null) === $type) {
                return $node;
            }
        }

        return null;
    }

    /** Every key at any depth, and every @type value. */
    protected function keysAndTypes(array $data): array
    {
        $found = [];
        foreach ($data as $key => $value) {
            $found[] = (string) $key;
            if ($key === '@type' && is_string($value)) {
                $found[] = $value;
            }
            if (is_array($value)) {
                $found = array_merge($found, $this->keysAndTypes($value));
            }
        }

        return array_values(array_unique($found));
    }

    #[DataProvider('locales')]
    public function test_canonical_and_hreflang_point_at_the_page_without_its_query(string $locale, string $prefix): void
    {
        $html = $this->get($prefix.'/catalog?family=label&cap=30')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.url($prefix.'/catalog').'">', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="ru" href="'.url('/catalog').'">', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="uz" href="'.url('/uz/catalog').'">', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="en" href="'.url('/en/catalog').'">', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="x-default" href="'.url('/catalog').'">', $html);
        $this->assertStringNotContainsString('hreflang="ru" href="'.url('/catalog?'), $html);

        $model = $this->get($prefix.'/model/c1')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.url($prefix.'/model/c1').'">', $model);
        $this->assertStringContainsString('<link rel="alternate" hreflang="x-default" href="'.url('/model/c1').'">', $model);

        $home = $this->get($prefix ?: '/')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.url($prefix ?: '/').'">', $home);
    }

    #[DataProvider('locales')]
    public function test_open_graph_and_twitter_tags(string $locale, string $prefix): void
    {
        $model = ScaleModel::where('slug', 'c1')->firstOrFail();
        $html = $this->get($prefix.'/model/c1')->assertOk()->getContent();
        $title = e('C1 · '.config('app.name'));

        $this->assertStringContainsString('<meta property="og:title" content="'.$title.'">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="'.e($model->tr('intro', $locale)).'">', $html);
        $this->assertStringContainsString('<meta property="og:url" content="'.url($prefix.'/model/c1').'">', $html);
        $this->assertStringContainsString('<meta property="og:site_name" content="'.e(config('app.name')).'">', $html);
        $this->assertStringContainsString('<meta property="og:locale" content="'.['ru' => 'ru_RU', 'uz' => 'uz_UZ', 'en' => 'en_US'][$locale].'">', $html);
        $this->assertSame(2, substr_count($html, 'property="og:locale:alternate"'));
        $this->assertStringContainsString('<meta name="twitter:title" content="'.$title.'">', $html);

        // No photo yet: the site logo, never an SVG.
        $this->assertStringContainsString('<meta property="og:image" content="'.asset('images/rongta-logo.png').'">', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary">', $html);
    }

    public function test_og_image_is_the_model_photo_when_it_is_a_raster_image(): void
    {
        ScaleModel::where('slug', 'c1')->update(['image' => 'models/c1-main.jpg']);
        $html = $this->get('/model/c1')->assertOk()->getContent();
        $this->assertStringContainsString('<meta property="og:image" content="'.asset('storage/models/c1-main.jpg').'">', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
        $this->assertSame([asset('storage/models/c1-main.jpg')], $this->node($this->graph($html), 'Product')['image']);

        ScaleModel::where('slug', 'c1')->update(['image' => 'models/c1-main.svg']);
        $html = $this->get('/model/c1')->assertOk()->getContent();
        $this->assertStringContainsString('<meta property="og:image" content="'.asset('images/rongta-logo.png').'">', $html);
        $this->assertArrayNotHasKey('image', $this->node($this->graph($html), 'Product'));
    }

    public function test_pages_without_their_own_description_use_the_site_description(): void
    {
        $this->get('/compare')->assertOk()->assertSee('<meta name="description" content="'.e(ui('ftr.about', [], 'ru')).'">', false);
        $this->get('/en/dealers')->assertOk()->assertSee('<meta name="description" content="'.e(ui('dl.lead', [], 'en')).'">', false);
        $this->get('/uz/knowledge')->assertOk()->assertSee('<meta name="description" content="'.e(ui('kb.lead', [], 'uz')).'">', false);
    }

    public function test_only_the_compare_page_is_noindex(): void
    {
        $this->get('/compare')->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->get('/en/compare')->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);

        foreach (['/', '/catalog', '/model/c1', '/dealers', '/knowledge', '/uz/catalog'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('name="robots"', false);
        }
    }

    public function test_admin_login_is_noindex(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    #[DataProvider('locales')]
    public function test_model_page_has_product_and_breadcrumbs_without_offers(string $locale, string $prefix): void
    {
        $model = ScaleModel::with('family')->where('slug', 'aurora-d2-ai')->firstOrFail();
        $graph = $this->graph($this->get($prefix.'/model/aurora-d2-ai')->assertOk()->getContent());

        $product = $this->node($graph, 'Product');
        $this->assertSame('AURORA D2-AI', $product['name']);
        $this->assertSame(url($prefix.'/model/aurora-d2-ai'), $product['url']);
        $this->assertSame($model->tr('intro', $locale), $product['description']);
        $this->assertSame($model->family->tr('name', $locale), $product['category']);
        $this->assertSame(['@id' => url('/').'/#rongta'], $product['brand']);
        $this->assertSame($model->source_url, $product['sameAs']);
        $this->assertArrayNotHasKey('image', $product, 'No photo yet, so no image');

        $crumbs = $this->node($graph, 'BreadcrumbList')['itemListElement'];
        $this->assertSame([1, 2, 3, 4], array_column($crumbs, 'position'));
        $this->assertSame([url($prefix ?: '/'), url($prefix.'/catalog'), url($prefix.'/catalog?family=ai'), url($prefix.'/model/aurora-d2-ai')], array_column($crumbs, 'item'));
        $this->assertSame([ui('nav.home', [], $locale), ui('nav.catalog', [], $locale), $model->family->tr('name', $locale), 'AURORA D2-AI'], array_column($crumbs, 'name'));

        $this->assertSame('Rongta', $this->node($graph, 'Brand')['name']);
        $this->assertSame('RSG', $this->node($graph, 'Organization')['name']);
        $this->assertSame(ui('ftr.dist', [], $locale), $this->node($graph, 'Organization')['description']);
    }

    public function test_catalog_has_breadcrumbs(): void
    {
        $crumbs = $this->node($this->graph($this->get('/en/catalog')->assertOk()->getContent()), 'BreadcrumbList')['itemListElement'];

        $this->assertSame([url('/en'), url('/en/catalog')], array_column($crumbs, 'item'));
    }

    public function test_knowledge_base_lists_the_faq_as_faq_page(): void
    {
        $faq = $this->node($this->graph($this->get('/en/knowledge')->assertOk()->getContent()), 'FAQPage');
        $this->assertCount(FaqItem::count(), $faq['mainEntity']);

        $first = FaqItem::query()->orderBy('faq_topic_id')->orderBy('sort')->first();
        $questions = array_column($faq['mainEntity'], 'name');
        $this->assertContains($first->tr('question', 'en'), $questions);
        $this->assertSame('Answer', $faq['mainEntity'][0]['acceptedAnswer']['@type']);

        // A search result is not the canonical FAQ page.
        $this->assertNull($this->node($this->graph($this->get('/en/knowledge?q=label')->assertOk()->getContent()), 'FAQPage'));
        $this->assertNull($this->node($this->graph($this->get('/en/catalog')->getContent()), 'FAQPage'));
    }

    #[DataProvider('locales')]
    public function test_structured_data_never_carries_a_price_or_an_offer(string $locale, string $prefix): void
    {
        $paths = ['', '/catalog', '/compare', '/dealers', '/knowledge'];
        foreach (ScaleModel::published()->pluck('slug') as $slug) {
            $paths[] = '/model/'.$slug;
        }

        foreach ($paths as $path) {
            $found = $this->keysAndTypes($this->graph($this->get(($prefix.$path) ?: '/')->assertOk()->getContent()));
            foreach ([...self::PRICE_KEYS, 'Offer', 'AggregateOffer'] as $banned) {
                $this->assertNotContains($banned, $found, ($prefix.$path ?: '/').' JSON-LD has "'.$banned.'"');
            }
        }
    }

    public function test_placeholder_contacts_stay_out_of_structured_data(): void
    {
        $this->assertTrue((bool) setting('contacts.is_placeholder'));
        $html = $this->get('/')->assertOk()->getContent();

        $organization = $this->node($this->graph($html), 'Organization');
        $this->assertArrayNotHasKey('contactPoint', $organization);
        $this->assertArrayNotHasKey('sameAs', $organization);
        $this->assertStringNotContainsString('000-00-00', $html);
        $this->assertStringNotContainsString('rongta_uz', $html);
    }

    public function test_confirmed_contacts_and_distributor_site_appear_in_structured_data(): void
    {
        Setting::write('contacts', ['phone' => '+998 71 123-45-67', 'telegram' => '@rsg_test', 'is_placeholder' => false, 'distributor_url' => 'https://rsg.example']);

        $organization = $this->node($this->graph($this->get('/en')->assertOk()->getContent()), 'Organization');

        $this->assertSame('+998711234567', $organization['contactPoint']['telephone']);
        $this->assertSame(['https://rsg.example', 'https://t.me/rsg_test'], $organization['sameAs']);
    }

    public function test_json_ld_cannot_be_broken_out_of(): void
    {
        $evil = '</script><script>alert(1)</script> & "quotes"';
        $model = ScaleModel::where('slug', 'c1')->firstOrFail();
        $model->update(['intro' => ['ru' => $evil, 'uz' => $evil, 'en' => $evil]]);

        $html = $this->get('/en/model/c1')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertSame($evil, $this->node($this->graph($html), 'Product')['description']);
    }
}
