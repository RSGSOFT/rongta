<?php

namespace Tests\Feature\Site;

use App\Livewire\Pages\Catalog;
use App\Models\ScaleModel;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;

class CatalogTest extends SiteTestCase
{
    /** Slugs of the cards on the page. */
    protected function cards(string $html): array
    {
        preg_match_all('/id="card-cat-([a-z0-9-]+)"/', $html, $m);

        return $m[1];
    }

    #[DataProvider('locales')]
    public function test_catalog_lists_all_models_grouped_by_family(string $locale, string $prefix): void
    {
        $response = $this->get($prefix.'/catalog')->assertOk();
        $html = $response->getContent();

        $this->assertCount(11, $this->cards($html));
        $response->assertSee(ui('cat.h', [], $locale))
            ->assertSee(ui('cat.count', ['n' => 11, 'all' => 11], $locale))
            ->assertSee('id="fh-label"', false)
            ->assertSee('id="fh-ai"', false)
            ->assertSee('id="fh-comm"', false)
            ->assertSee('id="cmp-cat-c1"', false)
            ->assertSee(ui('fact.max', [], $locale))
            ->assertSee(ui('fact.label', [], $locale))
            ->assertSee(ui('fact.conn', [], $locale))
            ->assertDontSee('id="f-reset"', false);

        $this->assertNoMissingStrings($html);
    }

    public function test_family_filter_from_the_query_string(): void
    {
        $response = $this->get('/en/catalog?family=ai')->assertOk();

        $this->assertEqualsCanonicalizing(
            ['c2-ai', 'aurora-d2-ai', 'aurora-s2l-ai', 'aurora-y2l-ai'],
            $this->cards($response->getContent()),
        );
        $response->assertSee(ui('cat.count', ['n' => 4, 'all' => 11], 'en'))
            ->assertSee('id="f-reset"', false)
            ->assertDontSee('id="fh-label"', false);
    }

    public function test_label_printing_filter(): void
    {
        $yes = $this->cards($this->get('/catalog?label=yes')->getContent());
        $this->assertEqualsCanonicalizing(['rls1315', 'rls1515', 'rls1000a', 'rls1000b', 'aurora-s2l-ai', 'aurora-y2l-ai'], $yes);

        // "No" keeps everything that does not print labels, including the receipt-only model.
        $no = $this->cards($this->get('/catalog?label=no')->getContent());
        $this->assertEqualsCanonicalizing(['c2-ai', 'aurora-d2-ai', 'c1', 'c11', 'c3'], $no);
    }

    public function test_connection_filter_counts_standard_and_optional(): void
    {
        $this->assertEqualsCanonicalizing(
            ['c2-ai', 'aurora-d2-ai', 'c1'],
            $this->cards($this->get('/catalog?conn=bt')->getContent()),
        );
    }

    public function test_capacity_filter_hides_nothing_and_changes_the_card_fact(): void
    {
        $response = $this->get('/en/catalog?cap=15')->assertOk();

        $this->assertCount(11, $this->cards($response->getContent()));
        $response->assertSee(ui('cat.capnote', [], 'en'))
            ->assertDontSee(ui('val.cap.both', [], 'en'));
    }

    public function test_filters_combine_and_show_the_empty_state(): void
    {
        $response = $this->get('/en/catalog?family=comm&label=yes')->assertOk();

        $this->assertSame([], $this->cards($response->getContent()));
        $response->assertSee(ui('cat.empty', [], 'en'))->assertSee('id="f-reset2"', false);
    }

    public function test_unknown_filter_values_fall_back_to_all(): void
    {
        $this->assertCount(11, $this->cards($this->get('/catalog?family=nope&conn=x&label=maybe&cap=99')->getContent()));
    }

    public function test_live_filters_and_reset(): void
    {
        app()->setLocale('en');

        Livewire::test(Catalog::class)
            ->assertSee('id="card-cat-c1"', false)
            ->set('family', 'label')
            ->assertDontSee('id="card-cat-c1"', false)
            ->assertSee('id="card-cat-rls1315"', false)
            ->set('conn', 'usb')
            ->assertDontSee('id="card-cat-rls1315"', false)
            ->assertSee('id="card-cat-rls1515"', false)
            ->assertSee(ui('cat.count', ['n' => 2, 'all' => 11], 'en'))
            ->call('resetFilters')
            ->assertSet('family', 'all')
            ->assertSet('conn', 'all')
            ->assertSee('id="card-cat-c1"', false);
    }

    public function test_unpublished_models_are_left_out(): void
    {
        ScaleModel::where('slug', 'c3')->update(['is_published' => false]);

        $html = $this->get('/catalog')->getContent();
        $this->assertCount(10, $this->cards($html));
        $this->assertNotContains('c3', $this->cards($html));
    }

    public function test_card_shows_a_real_photo_when_the_model_has_one(): void
    {
        ScaleModel::where('slug', 'c1')->update(['image' => 'images/rongta-logo.png']);

        $this->get('/catalog')->assertSee('src="'.asset('images/rongta-logo.png').'" alt="C1"', false);
    }
}
