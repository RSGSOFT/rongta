<?php

namespace Tests\Feature;

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Settings\Index as Settings;
use App\Livewire\Pages\Partner\Login as PartnerLogin;
use App\Models\Family;
use App\Models\PartnerUser;
use App\Models\Setting;
use App\Models\UiString;
use App\Services\Chatbot\KnowledgeBase;
use App\Support\RsgContacts;
use Database\Seeders\ChatbotStringSeeder;
use Database\Seeders\SiteBStringSeeder;
use Database\Seeders\SiteStringSeeder;
use Database\Seeders\UiStringSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

/** Regression tests for the defects found by the QA review of the finished build. */
class QaFixesTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    private const REMOVED_KEYS = [
        'buy.lead', 'pa.i.terms', 'ftr.note', 'call.note', 'file.proto', 'file.sample', 'file.samplenote',
        'dl.samplenote', 'ph.placeholder', 'pa.demo', 'pa.proto', 'pa.preview', 'pf.proto', 'pf.done.p',
        'kb.sample', 'chat.proto', 'chat.sub',
        'chat.a.choose', 'chat.a.dealers', 'chat.a.docs', 'chat.a.fallback', 'chat.a.human', 'chat.a.partner',
        'chat.a.pos', 'chat.a.reco', 'chat.a.reco.end', 'chat.a.warranty',
    ];

    protected function afterRefreshingDatabase(): void
    {
        $this->seed();
        $this->seed([SiteStringSeeder::class, SiteBStringSeeder::class, ChatbotStringSeeder::class]);
    }

    // Prototype and price strings

    public function test_prototype_and_price_strings_are_not_seeded(): void
    {
        foreach (self::REMOVED_KEYS as $key) {
            $this->assertTrue(UiStringSeeder::isSkipped($key), $key);
            $this->assertDatabaseMissing('ui_strings', ['key' => $key]);
        }
        $this->assertSame(0, UiString::where('key', 'like', 'chat.a.%')->count());

        // Their replacements, and keys that only look similar, are still there.
        foreach (['site.buy.lead', 'site.ftr.note', 'pa.i.terms.h', 'chat.chip.dealers', 'file.get'] as $key) {
            $this->assertDatabaseHas('ui_strings', ['key' => $key]);
        }
    }

    public function test_migration_removes_exactly_the_leftover_rows(): void
    {
        foreach (self::REMOVED_KEYS as $key) {
            UiString::create(['key' => $key, 'ru' => 'Прототип', 'uz' => 'Prototip', 'en' => 'Prototype']);
        }
        $before = UiString::count();

        (require database_path('migrations/2026_11_17_000001_remove_prototype_ui_strings.php'))->up();

        $this->assertSame($before - count(self::REMOVED_KEYS), UiString::count());
        $this->assertSame(0, UiString::whereIn('key', self::REMOVED_KEYS)->count());
        $this->assertSame('[pa.demo]', ui('pa.demo'), 'the string cache was flushed');
    }

    // RSG contacts: one rule everywhere

    public static function unconfirmedContacts(): array
    {
        return [
            'flag on' => [['phone' => '+998 71 555 11 22', 'telegram' => '@rsg_test', 'is_placeholder' => true]],
            'flag missing' => [['phone' => '+998 71 555 11 22', 'telegram' => '@rsg_test']],
            'no phone' => [['phone' => '', 'telegram' => '@rsg_test', 'is_placeholder' => false]],
            'phone missing' => [['telegram' => '@rsg_test', 'is_placeholder' => false]],
            'not an array' => ['+998 71 555 11 22'],
        ];
    }

    #[DataProvider('unconfirmedContacts')]
    public function test_unconfirmed_contacts_are_shown_nowhere(mixed $contacts): void
    {
        Setting::write('contacts', $contacts);

        $this->assertNull(RsgContacts::confirmed());
        $this->assertNull(app(KnowledgeBase::class)->rsgContacts());
        $this->assertArrayNotHasKey('rsg_contacts', app(KnowledgeBase::class)->dealers('all', 'any', 'en'));

        // Header, footer, phone bar and hero; the dealers page; the partner area.
        $this->actingAs(PartnerUser::create(['name' => 'A', 'login' => 'r1', 'password' => 'correct-horse', 'type' => 'reseller', 'is_active' => true]), 'partner');
        foreach (['/en', '/en/dealers', '/en/partner'] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('555 11 22')
                ->assertDontSee('rsg_test')
                ->assertDontSee('href="tel:', false);
        }
        $this->get('/en')->assertSee(ui('site.contacts.tbc', [], 'en'))->assertSee('id="callpop-dealers"', false);
        $this->get('/en/dealers')->assertSee(ui('dl.contact.tbc', [], 'en'));
    }

    public function test_confirmed_contacts_are_shown_everywhere(): void
    {
        Setting::write('contacts', ['phone' => '+998 71 555 11 22', 'telegram' => '@rsg_test', 'is_placeholder' => false]);

        $this->assertSame(['phone' => '+998 71 555 11 22', 'telegram' => 'rsg_test', 'tel' => '+998715551122'], RsgContacts::confirmed());
        $this->assertSame(['phone' => '+998 71 555 11 22', 'telegram' => '@rsg_test'], app(KnowledgeBase::class)->rsgContacts());

        $this->actingAs(PartnerUser::create(['name' => 'A', 'login' => 'r1', 'password' => 'correct-horse', 'type' => 'reseller', 'is_active' => true]), 'partner');
        foreach (['/en', '/en/dealers', '/en/partner'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('href="tel:+998715551122"', false)
                ->assertSee('https://t.me/rsg_test', false);
        }
        $this->get('/en')->assertSee('id="callpop-tel"', false)->assertDontSee('id="callpop-dealers"', false);
    }

    // Logo upload

    public function test_logo_extension_comes_from_the_content_not_the_client_name(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        // A PNG whose name claims to be a web page. (Under test Livewire takes the type from the
        // fake's declared MIME type; in production it is read from the file's bytes.)
        $disguised = UploadedFile::fake()->create('logo.html', 10, 'image/png');

        Livewire::test(Settings::class)->set('newSymbol', $disguised)->call('save')->assertHasNoErrors();

        $path = setting('logo.symbol');
        $this->assertMatchesRegularExpression('~^storage/logos/symbol-[a-z0-9]{10}\.png$~', $path);
        Storage::disk('public')->assertExists(substr($path, strlen('storage/')));
        $this->assertSame([], array_filter(Storage::disk('public')->allFiles(), fn (string $file) => str_ends_with($file, '.html')));
    }

    // Login throttling per address

    public function test_partner_login_is_throttled_per_address_across_logins(): void
    {
        PartnerUser::create(['name' => 'A', 'login' => 'reseller1', 'password' => 'correct-horse', 'type' => 'reseller', 'is_active' => true]);
        app()->setLocale('en');

        // A different login each time: the per-login limit never trips.
        for ($i = 0; $i < PartnerLogin::IP_MAX_ATTEMPTS; $i++) {
            Livewire::test(PartnerLogin::class)->set('login', 'guess'.$i)->set('password', 'wrong')
                ->call('authenticate')->assertSee(ui('pa.err.login'));
        }

        Livewire::test(PartnerLogin::class)->set('login', 'reseller1')->set('password', 'correct-horse')
            ->call('authenticate')
            ->assertHasErrors(['login'])
            ->assertDontSee(ui('pa.err.login'))
            ->assertNoRedirect();
        $this->assertGuest('partner');

        // Another address is not affected.
        $this->assertTrue(RateLimiter::tooManyAttempts('partner-login-ip:127.0.0.1', PartnerLogin::IP_MAX_ATTEMPTS));
        $this->assertFalse(RateLimiter::tooManyAttempts('partner-login-ip:10.0.0.9', PartnerLogin::IP_MAX_ATTEMPTS));
    }

    public function test_a_successful_partner_login_does_not_reset_the_address_count(): void
    {
        PartnerUser::create(['name' => 'A', 'login' => 'reseller1', 'password' => 'correct-horse', 'type' => 'reseller', 'is_active' => true]);

        for ($i = 0; $i < PartnerLogin::IP_MAX_ATTEMPTS - 1; $i++) {
            Livewire::test(PartnerLogin::class)->set('login', 'guess'.$i)->set('password', 'wrong')->call('authenticate');
        }
        Livewire::test(PartnerLogin::class)->set('login', 'reseller1')->set('password', 'correct-horse')
            ->call('authenticate')->assertHasNoErrors();
        auth('partner')->logout();

        Livewire::test(PartnerLogin::class)->set('login', 'guess-last')->set('password', 'wrong')->call('authenticate');
        Livewire::test(PartnerLogin::class)->set('login', 'reseller1')->set('password', 'correct-horse')
            ->call('authenticate')->assertHasErrors(['login']);
    }

    public function test_admin_login_is_throttled_per_address_across_emails(): void
    {
        $this->editor(['email' => 'editor@example.test', 'password' => 'secret-pass-1']);

        for ($i = 0; $i < AdminLogin::IP_MAX_ATTEMPTS; $i++) {
            Livewire::test(AdminLogin::class)->set('email', "guess$i@example.test")->set('password', 'wrong')
                ->call('login')->assertHasErrors('email');
        }

        $page = Livewire::test(AdminLogin::class)->set('email', 'editor@example.test')->set('password', 'secret-pass-1')->call('login');
        $this->assertStringContainsString('Слишком много попыток', $page->errors()->first('email'));
        $this->assertGuest();

        $this->assertTrue(RateLimiter::tooManyAttempts('admin-login-ip|127.0.0.1', AdminLogin::IP_MAX_ATTEMPTS));
        $this->assertFalse(RateLimiter::tooManyAttempts('admin-login-ip|10.0.0.9', AdminLogin::IP_MAX_ATTEMPTS));
    }

    // Dark AURORA styling follows the models, not the slug

    public function test_dark_family_styling_survives_a_slug_rename(): void
    {
        $ai = Family::where('slug', 'ai')->firstOrFail();
        $this->assertTrue($ai->isAurora());
        $this->assertFalse(Family::where('slug', 'label')->firstOrFail()->isAurora());
        $this->assertSame(['ai'], Family::withAuroraFlag()->get()->filter->isAurora()->pluck('slug')->all());

        $ai->update(['slug' => 'smart']);

        $catalog = $this->get('/en/catalog')->assertOk()->getContent();
        $this->assertSame(1, substr_count($catalog, 'class="fam-head night"'));
        $this->assertMatchesRegularExpression('~class="fam-head night">\s*<h2 id="fh-smart">~', $catalog);

        $knowledge = $this->get('/en/knowledge')->assertOk()->getContent();
        $this->assertSame(1, substr_count($knowledge, 'pb-guide-night'));
        $this->assertMatchesRegularExpression('~pb-guide-col pb-guide-night"[^>]*>\s*<h3>'.preg_quote(e($ai->tr('name', 'en')), '~').'~', $knowledge);
    }
}
