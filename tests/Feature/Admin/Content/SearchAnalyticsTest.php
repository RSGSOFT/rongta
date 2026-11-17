<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Settings\SearchAnalytics;
use App\Models\ScaleModel;
use App\Models\Setting;
use App\Support\SearchTools;
use Database\Seeders\SiteStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

/** Search engine verification, analytics ids and IndexNow, set in admin settings. */
class SearchAnalyticsTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    private const GOOGLE = 'aBcD1234_efGH5678-ijKL9012mnOP';

    private const YANDEX = '0123456789abcdef';

    protected function afterRefreshingDatabase(): void
    {
        $this->seed();
        $this->seed(SiteStringSeeder::class);
    }

    private function saveSettings(array $form): void
    {
        $this->actingAs($this->admin());
        $component = Livewire::test(SearchAnalytics::class);
        foreach ($form as $field => $value) {
            $component->set("form.$field", $value);
        }
        $component->call('save')->assertHasNoErrors();
    }

    public function test_editors_cannot_open_it(): void
    {
        $this->actingAs($this->editor())->get(route('admin.settings.search'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.settings.search'))->assertOk()->assertSee('Поиск и аналитика');
    }

    public function test_pasted_meta_tags_become_verification_tags_on_public_pages(): void
    {
        $this->saveSettings([
            'google_verification' => '<meta name="google-site-verification" content="'.self::GOOGLE.'" />',
            'yandex_verification' => '<meta name="yandex-verification" content="'.self::YANDEX.'" />',
        ]);

        $this->assertSame(self::GOOGLE, SearchTools::all()['google_verification']);
        $this->get('/en')->assertOk()
            ->assertSee('<meta name="google-site-verification" content="'.self::GOOGLE.'">', false)
            ->assertSee('<meta name="yandex-verification" content="'.self::YANDEX.'">', false);
    }

    public function test_wrong_ids_are_rejected(): void
    {
        $this->actingAs($this->admin());
        Livewire::test(SearchAnalytics::class)
            ->set('form.ga4_id', 'UA-12345-1')
            ->set('form.metrica_id', '12ab')
            ->set('form.yandex_verification', '"><script>')
            ->call('save')
            ->assertHasErrors(['form.ga4_id', 'form.metrica_id', 'form.yandex_verification']);

        $this->assertNull(Setting::where('key', SearchTools::KEY)->first());
    }

    public function test_analytics_scripts_appear_only_in_production(): void
    {
        $this->saveSettings(['ga4_id' => 'g-abc123def4', 'metrica_id' => '98765432']);

        $this->get('/')->assertOk()->assertDontSee('googletagmanager.com', false)->assertDontSee('mc.yandex.ru', false);

        $this->app['env'] = 'production';
        $this->get('/')->assertOk()
            ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-ABC123DEF4', false)
            ->assertSee("ym(98765432,'init'", false);
        $this->get('/admin/login')->assertDontSee('googletagmanager.com', false);
    }

    public function test_indexnow_key_file_and_ping_when_a_model_changes(): void
    {
        $this->get('/indexnow.txt')->assertNotFound();

        $this->saveSettings(['indexnow' => true]);
        $key = SearchTools::all()['indexnow_key'];
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key);
        $this->assertSame($key, $this->get('/indexnow.txt')->assertOk()->getContent());

        Http::fake();
        $model = ScaleModel::published()->firstOrFail();

        $model->update(['sort' => $model->sort + 1]);
        $this->app->terminate();
        Http::assertNothingSent();

        $this->app['env'] = 'production';
        $model->update(['sort' => $model->sort + 1]);
        $this->app->terminate();
        Http::assertSent(fn (Request $request) => $request->url() === SearchTools::INDEXNOW_ENDPOINT
            && $request['key'] === $key
            && $request['urlList'] === [
                lroute('model', ['slug' => $model->slug], 'ru'),
                lroute('model', ['slug' => $model->slug], 'uz'),
                lroute('model', ['slug' => $model->slug], 'en'),
            ]);
    }
}
