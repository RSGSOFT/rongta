<?php

namespace Tests\Feature\Site;

use App\Models\Family;
use App\Models\FaqItem;
use App\Models\ModelSpec;
use App\Models\ScaleModel;
use App\Models\Setting;
use App\Support\Locales;

/** /robots.txt, /sitemap.xml, /llms.txt and /llms-full.txt. */
class SeoFilesTest extends SiteTestCase
{
    /** Pricing words and amounts that must never reach a file read by search engines or AI assistants. */
    protected function assertNoPricing(string $text, string $where): void
    {
        foreach (['Цена по запросу', 'Narxi soʻrov', 'Price on request', 'priceCurrency', '10 + 1', 'UZS'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $text, $where.' contains "'.$needle.'"');
        }
        $this->assertDoesNotMatchRegularExpression('/\d[\d\s\x{00A0}.,]*(сум|soʻm|so\'m|sum)(?![\p{L}])/ui', $text, $where.' shows an amount in soʻm');
    }

    public function test_robots_blocks_the_admin_panel_and_names_the_sitemap(): void
    {
        $response = $this->get('/robots.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $this->assertStringContainsString("User-agent: *\nDisallow: /admin\n", $response->getContent());
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $response->getContent());
        $this->assertFileDoesNotExist(public_path('robots.txt'), 'A static robots.txt would be served instead of the route');
    }

    public function test_sitemap_lists_every_public_page_in_three_languages(): void
    {
        ScaleModel::where('slug', 'c3')->update(['is_published' => false]);

        $response = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->registerXPathNamespace('x', 'http://www.w3.org/1999/xhtml');
        $locs = array_map('strval', $xml->xpath('//s:url/s:loc'));

        $expected = [];
        $slugs = ScaleModel::published()->pluck('slug')->all();
        foreach (['', '/uz', '/en'] as $prefix) {
            foreach (['', '/catalog', '/dealers', '/knowledge'] as $path) {
                $expected[] = url(($prefix.$path) ?: '/');
            }
            foreach ($slugs as $slug) {
                $expected[] = url($prefix.'/model/'.$slug);
            }
        }
        $this->assertEqualsCanonicalizing($expected, $locs);
        $this->assertCount(10, $slugs);

        foreach ($locs as $loc) {
            $this->assertStringNotContainsString('/compare', $loc);
            $this->assertStringNotContainsString('/model/c3', $loc);
            $this->assertStringNotContainsString('/admin', $loc);
        }

        // Every entry names all three languages and the Russian x-default.
        foreach ($xml->xpath('//s:url') as $url) {
            $url->registerXPathNamespace('x', 'http://www.w3.org/1999/xhtml');
            $links = [];
            foreach ($url->xpath('x:link') as $link) {
                $links[(string) $link['hreflang']] = (string) $link['href'];
            }
            $this->assertSame([...Locales::ALL, 'x-default'], array_keys($links));
            $this->assertSame($links['ru'], $links['x-default']);
            $this->assertContains((string) $url->loc, $links);
        }

        $model = ScaleModel::where('slug', 'c1')->firstOrFail();
        $lastmod = $xml->xpath('//s:url[s:loc="'.url('/model/c1').'"]/s:lastmod');
        $this->assertSame($model->updated_at->toAtomString(), (string) $lastmod[0]);
    }

    public function test_llms_txt_summarises_the_site_from_the_database(): void
    {
        ScaleModel::where('slug', 'c3')->update(['is_published' => false]);

        $response = $this->get('/llms.txt')->assertOk();
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $text = $response->getContent();

        $this->assertStringStartsWith('# '.config('app.name')."\n", $text);
        $this->assertStringContainsString(ui('ftr.dist', [], 'en'), $text);
        $this->assertStringContainsString('Rongta is the manufacturer', $text);

        foreach (Family::all() as $family) {
            $this->assertStringContainsString('### '.$family->tr('name', 'en'), $text);
        }
        foreach (ScaleModel::published()->get() as $model) {
            $this->assertStringContainsString('['.$model->name.']('.url('/en/model/'.$model->slug).')', $text);
            $this->assertStringContainsString('Russian: '.url('/model/'.$model->slug), $text);
            $this->assertStringContainsString('Uzbek: '.url('/uz/model/'.$model->slug), $text);
        }
        $this->assertStringNotContainsString('/model/c3', $text);

        $this->assertStringContainsString('['.ui('cta.dealer', [], 'en').']('.url('/en/dealers').')', $text);
        $this->assertStringContainsString('('.url('/en/knowledge').')', $text);
        $this->assertStringContainsString(url('/llms-full.txt'), $text);
        $this->assertStringNotContainsString('/compare', $text);
        $this->assertNoPricing($text, 'llms.txt');
    }

    public function test_llms_txt_shows_rsg_contacts_only_once_confirmed(): void
    {
        $this->assertTrue((bool) setting('contacts.is_placeholder'));
        $text = $this->get('/llms.txt')->assertOk()->getContent();
        $this->assertStringNotContainsString('## Contact RSG', $text);
        $this->assertStringNotContainsString('000-00-00', $text);
        $this->assertStringNotContainsString('rongta_uz', $text);

        Setting::write('contacts', ['phone' => '+998 71 123-45-67', 'telegram' => '@rsg_test', 'is_placeholder' => false]);

        $text = $this->get('/llms.txt')->assertOk()->getContent();
        $this->assertStringContainsString("## Contact RSG\n\n- Phone: +998 71 123-45-67\n- Telegram: https://t.me/rsg_test", $text);
    }

    public function test_llms_full_txt_adds_specifications_and_the_faq(): void
    {
        $text = $this->get('/llms-full.txt')->assertOk()->getContent();

        $model = ScaleModel::with('specs')->where('slug', 'c1')->firstOrFail();
        /** @var ModelSpec $spec */
        $spec = $model->specs->first();
        $this->assertStringContainsString('## C1', $text);
        $this->assertStringContainsString('- '.ui('spec.'.$spec->key, [], 'en').': '.$spec->text('en'), $text);

        $faq = FaqItem::first();
        $this->assertStringContainsString('**'.$faq->tr('question', 'en').'**', $text);
        $this->assertNoPricing($text, 'llms-full.txt');
    }
}
