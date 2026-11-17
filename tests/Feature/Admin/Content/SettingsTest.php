<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Settings\Index as Settings;
use App\Livewire\Pages\Home;
use App\Models\ActivityLog;
use App\Models\Setting;
use Database\Seeders\SiteStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

/** Runs on the seeded site, so every change is checked on the real public home page. */
class SettingsTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed();
        $this->seed(SiteStringSeeder::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin());
    }

    public function test_form_loads_the_stored_settings(): void
    {
        Livewire::test(Settings::class)
            ->assertSet('contacts.is_placeholder', true)
            ->assertSet('colors.accent', '#FF6400')
            ->assertSet('colors.accent_ink', '')
            ->assertSet('logo.symbol', 'images/rongta-symbol.png')
            ->assertSet('sections.0.key', 'hero')
            ->assertSet('sections.6.key', 'partners')
            ->assertSet('hero.title_key', 'hero.h1')
            ->assertSee('Сейчас на сайте вместо контактов показано');
    }

    public function test_contacts_appear_on_the_home_page_once_confirmed(): void
    {
        $this->get('/')->assertOk()->assertSee('data-contact="pending"', false)->assertDontSee('+998 71 200 11 22');

        Livewire::test(Settings::class)
            ->set('contacts.phone', '+998 71 200 11 22')
            ->set('contacts.telegram', 'rongta_uz')
            ->set('contacts.is_placeholder', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Настройки сохранены');

        $this->assertSame(['phone' => '+998 71 200 11 22', 'telegram' => '@rongta_uz', 'is_placeholder' => false], Setting::where('key', 'contacts')->firstOrFail()->value);

        $this->get('/')->assertOk()
            ->assertSee('+998 71 200 11 22')
            ->assertSee('href="tel:+998712001122"', false)
            ->assertSee('https://t.me/rongta_uz', false)
            ->assertDontSee('data-contact="pending"', false);

        $this->assertDatabaseHas('activity_logs', ['action' => 'updated', 'subject_type' => Setting::class]);

        // Switching the flag back hides them again.
        Livewire::test(Settings::class)->set('contacts.is_placeholder', true)->call('save')->assertHasNoErrors();
        $this->get('/')->assertSee('data-contact="pending"', false)->assertDontSee('+998 71 200 11 22');
    }

    public function test_contact_validation(): void
    {
        Livewire::test(Settings::class)
            ->set('contacts.phone', '')
            ->set('contacts.is_placeholder', false)
            ->call('save')
            ->assertHasErrors(['contacts.phone' => 'required']);

        Livewire::test(Settings::class)
            ->set('contacts.phone', 'позвоните нам')
            ->set('contacts.telegram', 't.me/rongta uz')
            ->call('save')
            ->assertHasErrors(['contacts.phone' => 'regex', 'contacts.telegram' => 'regex']);

        $this->assertTrue(setting('contacts.is_placeholder'));
    }

    public function test_colours_are_validated_as_hex_and_written_into_the_page(): void
    {
        foreach (['FF6400', '#FF640', '#GG6400', 'red', '#FF6400;}body{display:none'] as $bad) {
            Livewire::test(Settings::class)->set('colors.accent', $bad)->call('save')->assertHasErrors(['colors.accent' => 'regex']);
        }
        Livewire::test(Settings::class)->set('colors.accent', '')->call('save')->assertHasErrors(['colors.accent' => 'required']);
        Livewire::test(Settings::class)->set('colors.accent_ink', 'url(x)')->set('colors.accent_soft', '#12')->call('save')
            ->assertHasErrors(['colors.accent_ink' => 'regex', 'colors.accent_soft' => 'regex']);
        $this->assertSame('#FF6400', setting('accent'));

        Livewire::test(Settings::class)
            ->set('colors.accent', '#0a7d5a')
            ->set('colors.accent_ink', '#075C42')
            ->set('colors.accent_soft', '#E6F5EF')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('#0A7D5A', setting('accent'));
        $this->assertSame('#075C42', setting('accent_ink'));
        $this->get('/')->assertOk()->assertSee('--accent:#0A7D5A', false)->assertSee('--accent-ink:#075C42', false)->assertSee('--accent-soft:#E6F5EF', false);

        // Emptying a companion colour returns it to the stylesheet default.
        Livewire::test(Settings::class)->set('colors.accent_ink', '')->call('save')->assertHasNoErrors();
        $this->assertNull(setting('accent_ink'));
        $this->get('/')->assertDontSee('--accent-ink:', false);
    }

    public function test_contrast_warning(): void
    {
        $this->assertEqualsWithDelta(21.0, Settings::contrast('#000000', '#FFFFFF'), 0.01);
        $this->assertEqualsWithDelta(1.0, Settings::contrast('#FF6400', '#FF6400'), 0.001);

        Livewire::test(Settings::class)
            ->assertDontSee('плохо читается')
            ->set('colors.accent', '#1A1A2E')            // dark button text on a dark accent
            ->assertSee('Тёмный текст на кнопке фирменного цвета: плохо читается')
            ->assertSee('Предупреждение')
            ->set('colors.accent', '#FF6400')
            ->set('colors.accent_ink', '#FFD9A0')        // pale links on white
            ->assertSee('Ссылки тёмным оттенком на белом фоне: плохо читается')
            ->call('save')
            ->assertHasNoErrors();                       // a warning, not a block
    }

    public function test_home_sections_order_and_visibility(): void
    {
        Livewire::test(Settings::class)
            ->call('moveSection', 2, -1)                 // families above chooser
            ->assertSet('sections.1.key', 'families')
            ->assertSet('sections.2.key', 'chooser')
            ->call('moveSection', 0, -1)                 // nothing above the first one
            ->assertSet('sections.0.key', 'hero')
            ->set('sections.3.visible', false)           // aurora
            ->call('save')
            ->assertHasNoErrors();

        $stored = setting('home.sections');
        $this->assertSame(['hero', 'families', 'chooser', 'aurora', 'trust', 'knowledge', 'partners'], array_column($stored, 'key'));
        $this->assertSame([true, true, true, false, true, true, true], array_column($stored, 'visible'));
        $this->assertSame(['hero', 'families', 'chooser', 'trust', 'knowledge', 'partners'], Home::sections());

        $html = $this->get('/en')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, ui('ch.h', [], 'en')), strpos($html, 'class="fam-rows"'));
        $this->assertStringNotContainsString(ui('au.h', [], 'en'), $html);

        // Tampered or empty lists are refused.
        Livewire::test(Settings::class)->set('sections', [['key' => 'hero', 'visible' => true]])->call('save')->assertHasErrors('sections');
        Livewire::test(Settings::class)->set('sections.0.key', 'evil')->call('save')->assertHasErrors('sections.0.key');
        $page = Livewire::test(Settings::class);
        foreach (range(0, 6) as $i) {
            $page->set("sections.{$i}.visible", false);
        }
        $page->call('save')->assertHasErrors('sections');
        $this->assertCount(7, setting('home.sections'));
    }

    public function test_a_section_left_out_of_the_stored_list_stays_hidden(): void
    {
        Setting::write('home.sections', [['key' => 'families', 'visible' => true], ['key' => 'hero', 'visible' => true]]);

        Livewire::test(Settings::class)
            ->assertSet('sections.0.key', 'families')
            ->assertSet('sections.2.visible', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['families', 'hero'], Home::sections());
    }

    public function test_hero_text_keys(): void
    {
        Livewire::test(Settings::class)->set('hero.title_key', 'no.such.key')->call('save')->assertHasErrors(['hero.title_key' => 'exists']);
        Livewire::test(Settings::class)->set('hero.lead_key', '')->call('save')->assertHasErrors(['hero.lead_key' => 'required']);

        Livewire::test(Settings::class)->set('hero.title_key', 'fam.h')->call('save')->assertHasNoErrors();

        $this->assertSame(['title_key' => 'fam.h', 'lead_key' => 'hero.lead', 'contact_key' => 'hero.contact', 'readout_alt_key' => 'hero.readout.alt'], setting('hero'));
        $this->get('/en')->assertOk()->assertSee('<h1>'.e(ui('fam.h', [], 'en')).'</h1>', false);
    }

    public function test_logo_upload_replace_and_reset(): void
    {
        Storage::fake('public');

        Livewire::test(Settings::class)
            ->set('newSymbol', UploadedFile::fake()->image('mark.png', 200, 150))
            ->assertSee('Новый файл: mark.png')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('newSymbol', null);

        $path = setting('logo.symbol');
        $this->assertMatchesRegularExpression('~^storage/logos/symbol-[a-z0-9]{10}\.png$~', $path);
        Storage::disk('public')->assertExists(substr($path, strlen('storage/')));
        $this->assertSame('images/rongta-wordmark.png', setting('logo.wordmark'));
        $this->get('/')->assertOk()->assertSee(asset($path), false);

        // A second upload replaces the first file on disk.
        Livewire::test(Settings::class)->set('newSymbol', UploadedFile::fake()->image('mark2.webp'))->call('save')->assertHasNoErrors();
        $second = setting('logo.symbol');
        $this->assertNotSame($path, $second);
        Storage::disk('public')->assertMissing(substr($path, strlen('storage/')));
        Storage::disk('public')->assertExists(substr($second, strlen('storage/')));

        // Back to the bundled file; the upload is removed.
        Livewire::test(Settings::class)->call('useDefaultLogo', 'symbol')->call('save')->assertHasNoErrors();
        $this->assertSame('images/rongta-symbol.png', setting('logo.symbol'));
        Storage::disk('public')->assertMissing(substr($second, strlen('storage/')));
        $this->get('/')->assertSee(asset('images/rongta-symbol.png'), false);
    }

    public function test_logo_upload_validation_and_tampered_path(): void
    {
        Storage::fake('public');

        Livewire::test(Settings::class)
            ->set('newLogo', UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'))
            ->assertHasErrors('newLogo')
            ->assertSet('newLogo', null);

        Livewire::test(Settings::class)
            ->set('newWordmark', UploadedFile::fake()->create('shell.php', 10, 'application/x-php'))
            ->assertHasErrors('newWordmark');

        Livewire::test(Settings::class)
            ->set('newLogo', UploadedFile::fake()->image('big.png')->size(3000))
            ->assertHasErrors(['newLogo' => 'max']);

        // The path itself cannot be pointed at another file from the browser.
        Livewire::test(Settings::class)->set('logo.symbol', '../.env')->call('save')->assertHasNoErrors();
        $this->assertSame('images/rongta-symbol.png', setting('logo.symbol'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_saving_without_changes_writes_nothing(): void
    {
        $before = ActivityLog::count();

        Livewire::test(Settings::class)->call('save')->assertHasNoErrors()->assertSee('Изменений нет');

        $this->assertSame($before, ActivityLog::count());
    }
}
