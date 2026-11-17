<?php

namespace Tests\Feature\Site;

use App\Models\ScaleModel;
use PHPUnit\Framework\Attributes\DataProvider;

/** Project rule: no product pricing on the catalog, model and compare pages. */
class NoPricesTest extends SiteTestCase
{
    protected function assertNoPricing(string $html, string $where): void
    {
        $needles = [
            '5 500 000', "5\u{00A0}500\u{00A0}000", '5500000', '4 625 000', "4\u{00A0}625\u{00A0}000", '4625000', '10 + 1',
            'priceline', 'class="price', 'pnote', 'm-price',
            'Цена по запросу', 'Итоговую цену', 'итоговую цену',
            'Narxi soʻrov', 'Yakuniy narx', 'yakuniy narx',
            'Price on request', 'final price',
            '[cur]', '[price.', '[fact.price]',
        ];
        foreach ($needles as $needle) {
            $this->assertStringNotContainsString($needle, $html, $where.' contains "'.$needle.'"');
        }

        // An amount followed by the currency word, in any of the three languages.
        $this->assertDoesNotMatchRegularExpression('/\d[\d\s\x{00A0}.,]*(сум|soʻm|so\'m|UZS)(?![\p{L}])/ui', $html, $where.' shows an amount in soʻm');
        // The "Price" fact row that the prototype had on cards and in the compare table.
        $this->assertDoesNotMatchRegularExpression('#<(dt|th)[^>]*>\s*(Цена|Narx|Price)\s*</(dt|th)>#u', $html, $where.' has a price row');
    }

    #[DataProvider('locales')]
    public function test_catalog_shows_no_prices(string $locale, string $prefix): void
    {
        $this->assertNoPricing($this->get($prefix.'/catalog')->assertOk()->getContent(), $prefix.'/catalog');
        $this->assertNoPricing($this->get($prefix.'/catalog?family=label&cap=30')->assertOk()->getContent(), $prefix.'/catalog?family=label');
    }

    #[DataProvider('locales')]
    public function test_model_pages_show_no_prices(string $locale, string $prefix): void
    {
        foreach (ScaleModel::published()->pluck('slug') as $slug) {
            $this->assertNoPricing($this->get($prefix.'/model/'.$slug)->assertOk()->getContent(), $prefix.'/model/'.$slug);
        }
    }

    #[DataProvider('locales')]
    public function test_compare_shows_no_prices(string $locale, string $prefix): void
    {
        $this->assertNoPricing($this->get($prefix.'/compare')->assertOk()->getContent(), $prefix.'/compare (empty)');

        $html = $this->withSession(['compare' => ['rls1000a', 'rls1000b', 'aurora-s2l-ai', 'c11']])
            ->get($prefix.'/compare')->assertOk()->getContent();
        $this->assertStringContainsString('id="cmp-link-rls1000a"', $html);
        $this->assertNoPricing($html, $prefix.'/compare');
    }
}
