<?php

namespace Tests\Feature\SiteB;

use App\Livewire\Pages\Knowledge;
use App\Models\Document;
use App\Models\FaqItem;
use App\Models\GuideCheck;
use App\Models\ScaleModel;
use App\Models\Video;
use Database\Seeders\SiteBStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KnowledgeTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed();
        $this->seed(SiteBStringSeeder::class);
    }

    public static function locales(): array
    {
        return ['ru' => ['ru', ''], 'uz' => ['uz', '/uz'], 'en' => ['en', '/en']];
    }

    #[DataProvider('locales')]
    public function test_page_renders_all_sections_in_each_locale(string $locale, string $prefix): void
    {
        $response = $this->get($prefix.'/knowledge')->assertOk()->assertDontSee('[kb.', false)->assertDontSee('[g.', false);

        foreach (['kb.h', 'kb.search', 'kb.pick', 'kb.manuals', 'kb.software', 'kb.sdk', 'kb.videos', 'kb.faq', 'kb.guide', 'g.check'] as $key) {
            $response->assertSee(ui($key, [], $locale));
        }
        foreach (['kb-manuals', 'kb-software', 'kb-sdk', 'kb-videos', 'kb-faq', 'kb-guide'] as $id) {
            $response->assertSee('id="'.$id.'"', false)->assertSee('href="#'.$id.'"', false);
        }
        $response->assertSee(GuideCheck::orderBy('sort')->first()->tr('text', $locale));
        $response->assertSee(FaqItem::first()->tr('question', $locale));
    }

    public function test_search_and_picker_come_before_the_materials(): void
    {
        $html = $this->get('/en/knowledge')->getContent();

        $this->assertLessThan(strpos($html, 'id="kb-model"'), strpos($html, 'id="kb-search"'));
        $this->assertLessThan(strpos($html, 'id="kb-manuals"'), strpos($html, 'id="kb-model"'));
    }

    public function test_documents_without_a_file_are_not_download_links(): void
    {
        $html = $this->get('/en/knowledge')->getContent();

        $this->assertSame(0, Document::whereNotNull('file_path')->count());
        $this->assertStringNotContainsString(' download', $html);
        $this->assertStringNotContainsString('>'.ui('file.get', [], 'en').'<', $html);
    }

    public function test_document_with_a_file_gets_a_download_link(): void
    {
        $document = Document::where('type', 'manual')->first();
        $document->update(['file_path' => 'files/manual-test.pdf', 'is_placeholder' => false]);

        $this->get('/en/knowledge')
            ->assertOk()
            ->assertSee('files/manual-test.pdf', false)
            ->assertSee(ui('file.get', [], 'en'));
    }

    public function test_videos_without_a_url_show_coming_soon_and_real_ones_link_out(): void
    {
        $this->get('/en/knowledge')->assertSee(ui('kb.soon', [], 'en'))->assertDontSee(ui('kb.video.watch', [], 'en'));

        Video::first()->update(['url' => 'https://example.com/watch/1']);

        $this->get('/en/knowledge')->assertSee('href="https://example.com/watch/1"', false);
    }

    public function test_model_picker_filters_documents_and_videos_and_counts(): void
    {
        app()->setLocale('en');
        $model = ScaleModel::published()->whereHas('documents')->first();
        $other = ScaleModel::published()->whereHas('documents')->where('family_id', '!=', $model->family_id)->first();

        $expected = Document::where('scale_model_id', $model->id)->count()
            + Video::where('family_id', $model->family_id)->count()
            + FaqItem::count();

        Livewire::test(Knowledge::class)
            ->set('model', $model->slug)
            ->assertSee(ui('kb.found', ['n' => $expected]))
            ->assertSee(': '.$model->name)
            ->assertDontSee(': '.$other->name)
            ->assertSee(ui('kb.guide'))
            ->call('resetSearch')
            ->assertSet('model', '')
            ->assertSee(': '.$other->name);

        $this->get('/en/knowledge?model='.$model->slug)->assertOk()->assertDontSee(': '.$other->name);
        $this->get('/en/knowledge?model=no-such-model')->assertOk()->assertSee(': '.$other->name);
    }

    public function test_search_covers_documents_videos_and_faq_in_the_current_language(): void
    {
        $item = FaqItem::first();
        $item->update([
            'question' => ['ru' => 'Вопрос про зебру', 'uz' => 'Zebra haqida savol', 'en' => 'Question about quokka'],
        ]);
        $video = Video::first();
        $video->update(['title' => ['ru' => 'Ролик про зебру', 'uz' => 'Zebra haqida video', 'en' => 'Quokka video']]);
        $document = Document::first();
        $document->update(['title' => ['ru' => 'Зебра: памятка', 'uz' => 'Zebra eslatmasi', 'en' => 'Quokka leaflet']]);

        app()->setLocale('en');
        Livewire::test(Knowledge::class)
            ->set('q', 'QUOKKA')
            ->assertSee(ui('kb.found', ['n' => 3]))
            ->assertSee('Question about quokka')
            ->assertSee('Quokka video')
            ->assertSee('Quokka leaflet')
            ->assertSeeHtml('<details class="pb-acc" id="faq-'.$item->id.'"')
            ->assertSeeHtml('wire:key="faq-'.$item->id.'-o"  open')
            // The buying guide is not a search result.
            ->assertDontSee(ui('kb.guide'))
            // Russian text is not searched while English is shown.
            ->set('q', 'зебр')
            ->assertSee(ui('kb.found', ['n' => 0]))
            ->assertSee(ui('kb.none'));

        app()->setLocale('ru');
        Livewire::test(Knowledge::class)
            ->set('q', 'зебр')
            ->assertSee(ui('kb.found', ['n' => 3]))
            ->assertSee('Вопрос про зебру');

        $this->get('/uz/knowledge?q=zebra')->assertOk()->assertSee('Zebra haqida savol')->assertSee(ui('kb.found', ['n' => 3], 'uz'));
    }

    public function test_search_ignores_uzbek_apostrophes_and_finds_model_names(): void
    {
        FaqItem::first()->update([
            'question' => ['ru' => 'Вопрос', 'uz' => 'Tarozi ogʻirligi qancha?', 'en' => 'Question'],
        ]);
        $model = ScaleModel::published()->whereHas('documents')->first();

        app()->setLocale('uz');
        Livewire::test(Knowledge::class)->set('q', 'ogirligi')->assertSee('Tarozi ogʻirligi qancha?');

        Livewire::test(Knowledge::class)
            ->set('q', mb_strtolower($model->name))
            ->assertSee(': '.$model->name);
    }

    public function test_no_prices_on_the_page(): void
    {
        foreach (['/knowledge', '/uz/knowledge', '/en/knowledge'] as $url) {
            $html = $this->get($url)->getContent();
            foreach (['4 625 000', '5 500 000', '10 + 1'] as $banned) {
                $this->assertStringNotContainsString($banned, $html, $url.' shows '.$banned);
            }
        }
    }
}
