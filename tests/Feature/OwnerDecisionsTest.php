<?php

namespace Tests\Feature;

use App\Livewire\Admin\Chatbot\Articles\Import;
use App\Livewire\Admin\Items\Catalog;
use App\Livewire\Admin\Settings\Maker as MakerScreen;
use App\Livewire\Admin\Settings\Trust as TrustScreen;
use App\Models\Partner;
use App\Models\Region;
use App\Models\Setting;
use App\Models\UiString;
use App\Services\Chatbot\KnowledgeBase;
use App\Services\Chatbot\PromptBuilder;
use App\Support\Maker;
use App\Support\Trust;
use Database\Seeders\ChatbotStringSeeder;
use Database\Seeders\SiteBStringSeeder;
use Database\Seeders\SiteStringSeeder;
use Database\Seeders\UiStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

/** The owner's decisions of 17.11.2026: what was removed, hidden or made editable. */
class OwnerDecisionsTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed();
        $this->seed([SiteStringSeeder::class, SiteBStringSeeder::class, ChatbotStringSeeder::class]);
    }

    private function migration(string $name): object
    {
        return require database_path('migrations/'.$name.'.php');
    }

    // 1. Sample partners

    public function test_no_sample_partners_are_seeded_and_dealers_lists_only_rsg(): void
    {
        $this->assertSame(0, Partner::count());
        $this->assertSame(14, Region::count());

        foreach (['', '?region=samarkand', '?tab=integrator'] as $query) {
            $html = $this->get('/en/dealers'.$query)->assertOk()
                ->assertSee(ui('dl.official', [], 'en'))
                ->assertSee(ui('dl.empty', [], 'en'))
                ->assertDontSee(ui('dl.sample', [], 'en'))
                ->getContent();
            $this->assertSame(1, substr_count($html, 'class="pb-dealer '), 'only the RSG card');
        }

        $html = $this->get('/en/dealers')->getContent();
        $this->assertSame(14, substr_count($html, 'pb-tile pb-tile-zero'));
        $this->assertSame(14, substr_count($html, '(0)</option>'));
    }

    public function test_migration_deletes_only_sample_partners(): void
    {
        $region = Region::firstOrFail();
        $base = ['kind' => 'reseller', 'region_id' => $region->id, 'is_published' => true];
        Partner::create($base + ['name' => ['ru' => 'Образец'], 'is_placeholder' => true]);
        $real = Partner::create($base + ['name' => ['ru' => 'Настоящий'], 'is_placeholder' => false]);

        $this->migration('2026_11_17_200001_remove_sample_partners')->up();

        $this->assertSame([$real->id], Partner::pluck('id')->all());
    }

    // 2. Buying guide without the cost row

    public function test_buying_guide_has_no_cost_row(): void
    {
        $this->assertFalse(Schema::hasColumn('guide_entries', 'cost'));
        $this->assertTrue(UiStringSeeder::isSkipped('g.cost'));
        $this->assertDatabaseMissing('ui_strings', ['key' => 'g.cost']);

        $html = $this->get('/en/knowledge')->assertOk()->assertSee(ui('g.does', [], 'en'))->getContent();
        $this->assertStringNotContainsString('Upfront cost', $html);
        $this->assertStringNotContainsString('[g.cost]', $html);

        $text = app(KnowledgeBase::class)->catalogText('en');
        $this->assertStringContainsString('## Buying guide', $text);
        $this->assertStringNotContainsString('running cost', $text);
    }

    // 3. Leads and conversations: see Admin\Content\InboxTest and Chatbot\AdminTest.

    // 4. The partner area removed on 2026-11-17 stays removed. The partner cabinet the owner asked
    //    for on 2026-11-25 (self-registration, admin approval) is separate: see SiteB\CabinetTest.

    public function test_partner_area_is_gone_and_links_point_at_the_partners_page(): void
    {
        $this->assertNull(config('auth.guards.partner'));
        $this->assertNull(config('auth.providers.partner_users'));
        $this->assertFalse(Schema::hasTable('partner_users'));
        $this->assertFalse(class_exists('App\Models\PartnerUser'));
        $this->assertFalse(class_exists('App\Http\Middleware\EnsurePartnerActive'));
        $this->assertFalse(Route::has('admin.partner-users.index'));
        $this->get('/admin/partner-users')->assertNotFound();

        $form = lroute('partners', [], 'en');
        $home = $this->get('/en')->assertOk()
            ->assertSee('href="'.$form.'" id="nav-partner"', false)
            ->assertSee('id="ps-form"', false)
            ->assertDontSee('id="ps-login"', false)
            ->getContent();
        $this->assertStringNotContainsString('/partner"', $home);
        $this->assertStringNotContainsString('/partner/', $home);
        $this->assertStringContainsString('<li><a href="'.$form.'">'.e(ui('nav.partner', [], 'en')).'</a></li>', $home);

        $dealers = $this->get('/uz/dealers')->assertOk()->assertDontSee('id="pf-login"', false)->getContent();
        $this->assertStringNotContainsString('/partner"', $dealers);
        $this->assertStringNotContainsString('/partner/', $dealers);

        foreach (['ru', 'uz', 'en'] as $locale) {
            $this->assertDoesNotMatchRegularExpression('#/partner(?!s\b)#', app(PromptBuilder::class)->system($locale));
        }
    }

    public function test_partner_area_strings_are_gone(): void
    {
        $this->assertSame(0, UiString::where('key', 'like', 'pa.%')->count());
        $this->assertDatabaseMissing('ui_strings', ['key' => 'ps.login']);
        $this->assertTrue(UiStringSeeder::isSkipped('pa.h'));
        $this->assertTrue(UiStringSeeder::isSkipped('ps.login'));
        $this->assertFalse(UiStringSeeder::isSkipped('ps.form'));
        $this->assertDatabaseHas('ui_strings', ['key' => 'ps.form']);

        UiString::create(['key' => 'pa.h', 'ru' => 'Кабинет']);
        UiString::create(['key' => 'ps.login', 'ru' => 'Вход']);
        $before = UiString::count();
        $this->migration('2026_11_17_200003_remove_partner_area')->up();
        $this->assertSame($before - 2, UiString::count());
    }

    // 5 and 6. Trust block and manufacturer figures

    public function test_trust_block_shows_nothing_without_material(): void
    {
        $this->assertSame(Trust::all(), Trust::normalise([]));
        $this->assertTrue(Trust::isEmpty(Trust::all()));

        // Model page: no trust block and no "works with" logo slots.
        $this->get('/en/model/c1')->assertOk()
            ->assertDontSee(ui('tr.h.compact', [], 'en'))
            ->assertDontSee(ui('m.works', [], 'en'))
            ->assertDontSee(ui('ph.poslogo', [], 'en'))
            ->assertDontSee('class="ph"', false);

        // Home: only the manufacturer card, no placeholder frames.
        $this->get('/en')->assertOk()
            ->assertSee(ui('tr.h', [], 'en'))
            ->assertSee('id="trust-maker"', false)
            ->assertDontSee('class="trust-grid"', false)
            ->assertDontSee(ui('ph.photo', [], 'en'))
            ->assertDontSee(ui('val.tbc', [], 'en').'</span></div>', false)
            ->assertDontSee('class="ph"', false);

        // Every manufacturer row hidden: the whole section goes.
        $maker = Maker::all();
        $maker['rows'] = array_map(fn ($row) => ['visible' => false] + $row, $maker['rows']);
        Setting::write('maker', $maker);
        $this->get('/en')->assertOk()->assertDontSee(ui('tr.h', [], 'en'))->assertDontSee('id="trust-maker"', false);
    }

    public function test_manufacturer_figures_are_seeded_with_the_stock_row_hidden(): void
    {
        $this->assertSame([
            ['Founded', '2010'],
            ['Location', 'Xiamen, China'],
            ['Registered patents', '210+'],
            ['Customers', '2,000+'],
            ['R&D personnel', '100+'],
        ], Maker::visibleRows('en'));
        $this->assertSame('Figures from rongtatech.com.', Maker::source('en'));
        $this->assertSame('Hong Kong, 9881.HK', Maker::all()['rows'][2]['value']['en']);
        $this->assertFalse(Maker::all()['rows'][2]['visible']);

        $this->get('/en')->assertSee('R&amp;D personnel', false)->assertSee('Xiamen, China')->assertDontSee('9881.HK')->assertSee('Figures from rongtatech.com.');
        $this->get('/uz')->assertSee('Syamen, Xitoy')->assertSee('Tashkil etilgan yili');
    }

    public function test_supplied_material_is_shown_item_by_item(): void
    {
        Setting::write('trust', [
            'service' => ['text' => ['ru' => 'Гарантия по договору', 'uz' => 'Shartnoma boʻyicha kafolat', 'en' => 'Warranty under contract'], 'address' => ['en' => 'Tashkent, Example st. 1']],
            'pos' => [['path' => 'storage/trust/pos-abc.png', 'name' => 'Test POS']],
            'photos' => [['path' => 'storage/trust/photo-abc.jpg', 'caption' => ['en' => 'A shop in Samarkand']]],
        ]);

        $this->get('/en/model/c1')->assertOk()
            ->assertSee(ui('tr.h.compact', [], 'en'))
            ->assertSee(ui('m.works', [], 'en'))
            ->assertSee('id="m-works"', false)
            ->assertSee('storage/trust/pos-abc.png', false)
            ->assertSee('alt="Test POS"', false)
            ->assertSee('Warranty under contract')
            ->assertDontSee('A shop in Samarkand')   // photos are on the full block only
            ->assertDontSee('id="trust-cert"', false)
            ->assertDontSee('id="trust-clients"', false)
            ->assertDontSee('id="trust-metro"', false);

        $this->get('/en')->assertOk()
            ->assertSee('id="trust-service"', false)
            ->assertSee('Tashkent, Example st. 1')
            ->assertSee('id="trust-pos"', false)
            ->assertSee('A shop in Samarkand')
            ->assertSee('id="trust-maker"', false)
            ->assertDontSee('id="trust-cert"', false);
    }

    public function test_admin_supplies_trust_material_with_content_derived_names(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $page = Livewire::test(TrustScreen::class)
            ->set('newCertificate', UploadedFile::fake()->create('certificate.html', 200, 'application/pdf'))
            ->set('newPos', [UploadedFile::fake()->create('pos.html', 10, 'image/png'), UploadedFile::fake()->create('second.png', 10, 'image/png')])
            ->assertCount('pending.pos', 2)
            ->set('pending.pos.0.name', 'Kassa One')
            ->call('save')
            ->assertHasErrors(['pending.pos.1.name' => 'required']);

        $page->set('pending.pos.1.name', 'Kassa Two')
            ->set('service.text.ru', 'Гарантия 12 месяцев')
            ->set('newMetrology', [UploadedFile::fake()->create('cert.pdf', 300, 'application/pdf')])
            ->set('pending.metrology.0.title', ['ru' => 'Свидетельство', 'uz' => 'Guvohnoma', 'en' => 'Certificate'])
            ->call('save')
            ->assertHasNoErrors();

        $trust = Trust::all();
        $this->assertMatchesRegularExpression('~^storage/trust/certificate-[a-z0-9]{10}\.pdf$~', $trust['certificate']['path']);
        $this->assertSame('pdf', $trust['certificate']['kind']);
        $this->assertSame(['Kassa One', 'Kassa Two'], array_column($trust['pos'], 'name'));
        $this->assertMatchesRegularExpression('~^storage/trust/pos-[a-z0-9]{10}\.png$~', $trust['pos'][0]['path']);
        $this->assertSame('pdf', $trust['metrology'][0]['ext']);
        $this->assertSame('Гарантия 12 месяцев', $trust['service']['text']['ru']);
        $this->assertCount(4, Storage::disk('public')->allFiles('trust'));
        $this->assertSame([], array_filter(Storage::disk('public')->allFiles(), fn (string $f) => str_ends_with($f, '.html')));
        $this->assertDatabaseHas('activity_logs', ['action' => 'created', 'subject_type' => Setting::class]);

        // Removing an entry deletes its file; the other entries stay.
        $removed = substr($trust['pos'][0]['path'], strlen('storage/'));
        Livewire::test(TrustScreen::class)->call('remove', 'pos', 0)->call('removeCertificate')->call('save')->assertHasNoErrors();
        $trust = Trust::all();
        $this->assertSame(['Kassa Two'], array_column($trust['pos'], 'name'));
        $this->assertNull($trust['certificate']);
        Storage::disk('public')->assertMissing($removed);
        $this->assertCount(2, Storage::disk('public')->allFiles('trust'));

        // A path typed into the form is never stored.
        Livewire::test(TrustScreen::class)->set('items.pos.0.path', '../../.env')->call('save');
        $this->assertSame([], Trust::all()['pos']);
    }

    public function test_trust_uploads_are_checked(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        Livewire::test(TrustScreen::class)
            ->set('newClients', [UploadedFile::fake()->create('big.png', TrustScreen::LOGO_MAX_KB + 1, 'image/png')])
            ->assertHasErrors('newClients')
            ->assertCount('pending.clients', 0)
            ->set('newMetrology', [UploadedFile::fake()->create('setup.exe', 10, 'application/x-msdownload')])
            ->assertHasErrors('newMetrology')
            ->set('newCertificate', UploadedFile::fake()->create('scan.png', TrustScreen::IMAGE_MAX_KB + 1, 'image/png'))
            ->assertHasErrors('newCertificate')
            ->assertSet('newCertificate', null);

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_admin_edits_manufacturer_figures(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(MakerScreen::class)
            ->assertSet('rows.2.visible', false)
            ->set('rows.2.visible', true)
            ->set('rows.0.value.en', '2011')
            ->call('addRow')
            ->call('save')
            ->assertHasErrors(['rows.6.label.ru' => 'required', 'rows.6.value.ru' => 'required'])
            ->call('removeRow', 6)
            ->call('moveRow', 1, -1)
            ->set('source', ['ru' => '', 'uz' => '', 'en' => ''])
            ->call('save')
            ->assertHasNoErrors();

        $rows = Maker::visibleRows('en');
        $this->assertSame(['Location', 'Xiamen, China'], $rows[0]);
        $this->assertSame(['Founded', '2011'], $rows[1]);
        $this->assertContains(['Stock exchange', 'Hong Kong, 9881.HK'], $rows);
        $this->assertSame('', Maker::source('en'));
        $this->get('/en')->assertSee('9881.HK')->assertDontSee('Figures from rongtatech.com.');
    }

    public function test_settings_screens_are_tabs_for_admins(): void
    {
        $this->actingAs($this->admin());
        foreach (['/admin/settings', '/admin/settings/trust', '/admin/settings/maker'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee(route('admin.settings.trust'))
                ->assertSee(route('admin.settings.maker'))
                ->assertSee('aria-current="page"', false);
        }
    }

    // 7. Hero motif without currency

    public function test_hero_motif_has_no_currency(): void
    {
        $this->assertDatabaseMissing('ui_strings', ['key' => 'lbl.cur']);
        $this->assertSame('Price per kg', ui('ro.unit', [], 'en'));
        $this->assertSame('Total', ui('ro.total', [], 'en'));

        foreach (['ru' => '/', 'uz' => '/uz', 'en' => '/en'] as $locale => $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $start = strpos($html, '<section class="hero"');
            $hero = substr($html, $start, strpos($html, '</section>', $start) - $start);
            $this->assertStringContainsString('data-readout', $hero);
            $this->assertMatchesRegularExpression('#<div class="sum num">\d[\d\x{00A0}]*</div>#u', $hero);
            $this->assertDoesNotMatchRegularExpression("/сум(?!\\p{L})|so[ʻ'’]m|UZS|\\[lbl\\./ui", $hero, $locale);
        }
    }

    public function test_hero_migration_rewords_existing_rows(): void
    {
        UiString::where('key', 'ro.total')->update(['ru' => 'Сумма, сум', 'en' => 'Total, soʻm']);
        UiString::create(['key' => 'lbl.cur', 'ru' => 'сум', 'uz' => 'soʻm', 'en' => 'soʻm']);

        $this->migration('2026_11_17_200004_reword_hero_readout_strings')->up();

        $this->assertSame('Сумма', ui('ro.total', [], 'ru'));
        $this->assertSame('Total', ui('ro.total', [], 'en'));
        $this->assertDatabaseMissing('ui_strings', ['key' => 'lbl.cur']);
        $this->assertStringNotContainsString('soʻm', ui('hero.readout.alt', [], 'uz'));
    }

    // 9. Upload limits

    public function test_upload_limits(): void
    {
        $this->assertContains('max:51200', config('livewire.temporary_file_upload.rules'));
        $this->assertSame(51200, Catalog::FILE_MAX_KB);
        $this->assertSame(5120, Catalog::IMAGE_MAX_KB);
        $this->assertSame(20 * 1024, Import::MAX_KB);

        $this->actingAs($this->admin());
        $this->get(route('admin.chatbot.articles.import'))->assertOk()->assertSee('До 20 МБ')->assertSee('32 МБ');
        $this->get(route('admin.documents.create'))->assertOk()->assertSee('До 50 МБ');
    }
}
