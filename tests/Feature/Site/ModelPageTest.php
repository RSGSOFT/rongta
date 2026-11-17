<?php

namespace Tests\Feature\Site;

use App\Models\Document;
use App\Models\ModelSpec;
use App\Models\ScaleModel;
use PHPUnit\Framework\Attributes\DataProvider;

class ModelPageTest extends SiteTestCase
{
    #[DataProvider('locales')]
    public function test_every_model_page_renders(string $locale, string $prefix): void
    {
        foreach (ScaleModel::published()->get() as $model) {
            $response = $this->get($prefix.'/model/'.$model->slug)->assertOk();

            $response->assertSee($model->tr('headline', $locale))
                ->assertSee('id="m-cta"', false)
                ->assertSee('id="m-cta2"', false)
                ->assertSee('id="cmp-model-'.$model->slug.'"', false)
                ->assertSee('class="gal-thumbs"', false)
                ->assertSee(ui('m.specs', [], $locale))
                ->assertSee(ui('m.downloads', [], $locale))
                // No trust material and no POS logos yet: neither block is rendered.
                ->assertDontSee(ui('m.works', [], $locale))
                ->assertDontSee(ui('tr.h.compact', [], $locale))
                ->assertSee(ui('m.buy.h', ['name' => $model->name], $locale));

            foreach ($model->features as $feature) {
                $response->assertSee($feature->tr('title', $locale));
            }
            $this->assertNoMissingStrings($response->getContent());
        }
    }

    public function test_aurora_models_use_the_dark_treatment(): void
    {
        $this->get('/model/aurora-s2l-ai')->assertSee('class="m-hero night"', false);
        $this->get('/model/c1')->assertSee('class="m-hero"', false)->assertDontSee('class="m-hero night"', false);
    }

    public function test_specs_are_grouped_and_all_three_shapes_render(): void
    {
        $model = ScaleModel::where('slug', 'c1')->firstOrFail();
        $model->specs()->delete();
        $tri = fn (string $s) => ['ru' => $s, 'uz' => $s, 'en' => $s];

        ModelSpec::create(['scale_model_id' => $model->id, 'group' => 'weighing', 'key' => 'maxCap', 'sort' => 1,
            'value' => ['parts' => [[$tri('Part label A'), $tri('Part value A')], [$tri('Part label B'), $tri('Part value B')]]]]);
        ModelSpec::create(['scale_model_id' => $model->id, 'group' => 'power', 'key' => 'input', 'sort' => 2,
            'value' => ['v' => $tri('Noted value'), 'note' => $tri('The caveat')]]);
        ModelSpec::create(['scale_model_id' => $model->id, 'group' => 'weighing', 'key' => 'accClass', 'sort' => 3,
            'value' => $tri('Plain value')]);
        ModelSpec::create(['scale_model_id' => $model->id, 'group' => 'weighing', 'key' => 'platter', 'sort' => 4,
            'value' => ['ru' => 'уточняется', 'uz' => 'aniqlanmoqda', 'en' => 'to be confirmed']]);

        $response = $this->get('/en/model/c1')->assertOk();
        $html = $response->getContent();

        $response->assertSee('id="sp-c1-weighing"', false)
            ->assertSee('id="sp-c1-power"', false)
            ->assertDontSee('id="sp-c1-printer"', false)
            ->assertSee('Part label A')->assertSee('Part value B')
            ->assertSee('Noted value')->assertSee('<span class="vnote">The caveat</span>', false)
            ->assertSee('Plain value')
            ->assertSee('<span class="tbc">to be confirmed</span>', false);

        // the first group is open, later ones are folded
        $this->assertMatchesRegularExpression('/id="sp-c1-weighing"\s+open/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="sp-c1-power"\s+open/', $html);
        $this->assertLessThan(strpos($html, 'id="sp-c1-power"'), strpos($html, 'id="sp-c1-weighing"'));
    }

    public function test_placeholder_documents_show_no_sample_values_and_no_download(): void
    {
        $doc = Document::where('slug', 'doc-rls1315-manual')->firstOrFail();
        $this->assertTrue($doc->is_placeholder);

        $this->get('/en/model/rls1315')
            ->assertSee('id="doc-rls1315-manual"', false)
            ->assertSee(ui('site.file.pending', [], 'en'))
            ->assertDontSee('03.02.2026')
            ->assertDontSee('3.1 MB')
            ->assertDontSee(' download ', false);
    }

    public function test_real_documents_get_a_download_link(): void
    {
        $doc = Document::where('slug', 'doc-rls1315-manual')->firstOrFail();
        $doc->update(['is_placeholder' => false, 'file_path' => 'files/rls1315-manual.pdf']);

        $this->get('/en/model/rls1315')
            ->assertSee('href="'.asset('storage/files/rls1315-manual.pdf').'" download', false)
            ->assertSee(ui('file.meta', ['ver' => $doc->version, 'date' => '03.02.2026', 'size' => $doc->tr('size_label', 'en')], 'en'));
    }

    public function test_similar_models_come_from_the_same_family(): void
    {
        $html = $this->get('/model/c1')->getContent();

        $this->assertStringContainsString('id="card-sim-c11"', $html);
        $this->assertStringContainsString('id="card-sim-c3"', $html);
        $this->assertStringNotContainsString('id="card-sim-c1"', $html);
        $this->assertStringNotContainsString('id="card-sim-rls1315"', $html);
    }

    public function test_real_photos_replace_the_schematic_gallery(): void
    {
        ScaleModel::where('slug', 'c1')->update(['image' => 'images/rongta-logo.png', 'gallery' => json_encode(['images/rongta-symbol.png'])]);

        $this->get('/en/model/c1')
            ->assertSee('src="'.asset('images/rongta-logo.png').'"', false)
            ->assertSee('src="'.asset('images/rongta-symbol.png').'"', false)
            ->assertDontSee(ui('view.note', [], 'en'));
    }

    public function test_unpublished_model_is_404(): void
    {
        ScaleModel::where('slug', 'c1')->update(['is_published' => false]);

        $this->get('/model/c1')->assertNotFound();
    }
}
