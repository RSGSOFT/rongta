<?php

namespace Tests\Feature\Site;

use App\Livewire\Pages\Compare;
use App\Livewire\Site\CompareTray;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;

class CompareTest extends SiteTestCase
{
    #[DataProvider('locales')]
    public function test_empty_state(string $locale, string $prefix): void
    {
        $response = $this->get($prefix.'/compare')->assertOk();

        $response->assertSee(ui('cmp.h', [], $locale))
            ->assertSee(ui('cmp.empty', [], $locale))
            ->assertSee('id="cmp-to-cat"', false)
            ->assertDontSee('class="cmp-t"', false);
        $this->assertNoMissingStrings($response->getContent());
    }

    #[DataProvider('locales')]
    public function test_models_from_the_session_are_shown_side_by_side(string $locale, string $prefix): void
    {
        $response = $this->withSession(['compare' => ['c1', 'rls1000a', 'aurora-d2-ai']])->get($prefix.'/compare')->assertOk();
        $html = $response->getContent();

        $response->assertSee('id="cmp-link-c1"', false)
            ->assertSee('id="cmp-link-rls1000a"', false)
            ->assertSee('id="cmp-link-aurora-d2-ai"', false)
            ->assertSee('id="cmp-x-c1"', false)
            ->assertSee(ui('grp.general', [], $locale))
            ->assertSee(ui('grp.weighing', [], $locale))
            ->assertSee(ui('cmp.onlydiff', [], $locale))
            ->assertSee('class="diff"', false)
            // the tray knows the list too
            ->assertSee('id="tray-x-c1"', false)
            ->assertSee(ui('cmp.go', ['n' => 3], $locale));

        // column order follows the order the models were added
        $this->assertLessThan(strpos($html, 'id="cmp-link-rls1000a"'), strpos($html, 'id="cmp-link-c1"'));
        $this->assertNoMissingStrings($html);
    }

    public function test_only_differences_hides_equal_rows(): void
    {
        app()->setLocale('en');
        session(['compare' => ['c1', 'c11']]);   // same family, so the "Family" row is equal

        $family = '<th scope="row">'.ui('fact.family', [], 'en').'</th>';

        Livewire::test(Compare::class)
            ->assertSee($family, false)
            ->set('onlyDiff', true)
            ->assertDontSee($family, false)
            ->assertSee('class="diff"', false)
            ->set('onlyDiff', false)
            ->assertSee($family, false);
    }

    public function test_a_single_model_has_no_highlighted_rows(): void
    {
        app()->setLocale('en');
        session(['compare' => ['c1']]);

        Livewire::test(Compare::class)
            ->assertSee('id="cmp-link-c1"', false)
            ->assertDontSee('class="diff"', false)
            ->set('onlyDiff', true)
            ->assertSee(ui('fact.family', [], 'en'));
    }

    public function test_tray_adds_removes_and_clears(): void
    {
        Livewire::test(CompareTray::class)
            ->assertDontSee('class="tray"', false)
            ->call('toggle', 'c1', true)
            ->assertDispatched('compare-updated', slugs: ['c1'])
            ->assertSee('id="tray-x-c1"', false)
            ->call('toggle', 'c11', true)
            ->call('toggle', 'c1', true)          // adding twice changes nothing
            ->assertSet('slugs', ['c1', 'c11'])
            ->call('remove', 'c1')
            ->assertSet('slugs', ['c11'])
            ->assertDispatched('compare-updated', slugs: ['c11'])
            ->call('clear')
            ->assertSet('slugs', [])
            ->assertDontSee('class="tray"', false);

        $this->assertSame([], session('compare'));
    }

    public function test_tray_keeps_at_most_four_known_models(): void
    {
        $tray = Livewire::test(CompareTray::class);

        foreach (['c1', 'c11', 'c3', 'no-such-model', 'rls1315'] as $slug) {
            $tray->call('toggle', $slug, true);
        }
        $tray->assertSet('slugs', ['c1', 'c11', 'c3', 'rls1315'])
            ->call('toggle', 'c2-ai', true)
            ->assertDispatched('toast')
            ->assertSet('slugs', ['c1', 'c11', 'c3', 'rls1315']);

        $this->assertSame(['c1', 'c11', 'c3', 'rls1315'], session('compare'));
    }

    public function test_session_list_is_cleaned_before_use(): void
    {
        session(['compare' => ['c1', 'c1', 'ghost', 42, 'c11', 'c3', 'rls1315', 'c2-ai']]);

        $this->assertSame(['c1', 'c11', 'c3', 'rls1315'], Compare::slugs());
    }

    public function test_compare_ticks_reflect_the_session(): void
    {
        $html = $this->withSession(['compare' => ['c1']])->get('/catalog')->getContent();

        $this->assertMatchesRegularExpression('/id="cmp-cat-c1" value="c1"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="cmp-cat-c11" value="c11"\s+checked/', $html);
    }
}
