<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Partners;
use App\Livewire\Admin\PartnerUsers;
use App\Livewire\Admin\Settings;
use App\Models\ChooserOption;
use App\Models\FaqItem;
use App\Models\FaqTopic;
use App\Models\GuideCheck;
use App\Models\GuideEntry;
use App\Models\Lead;
use App\Models\PartnerRequest;
use App\Models\PartnerUser;
use App\Models\UiString;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use MakesContent, MakesStaff, RefreshDatabase;

    /** Screens every staff member may open, and the admin-only ones. */
    protected function pages(): array
    {
        $region = $this->region();
        $city = $this->city($region);
        $partner = $this->partner($region);
        $family = $this->family();
        $topic = FaqTopic::create(['slug' => 'setup', 'name' => $this->tr('Настройка'), 'sort' => 1]);
        $item = FaqItem::create(['faq_topic_id' => $topic->id, 'question' => $this->tr('Вопрос'), 'answer' => $this->tr('Ответ'), 'sort' => 1]);
        $entry = GuideEntry::create(['family_id' => $family->id, 'does' => $this->tr('a'), 'fits' => $this->tr('b'), 'limit' => $this->tr('c'), 'cost' => $this->tr('низкие'), 'peak' => $this->tr('d'), 'sort' => 1]);
        $check = GuideCheck::create(['text' => $this->tr('Проверьте'), 'sort' => 1]);
        $option = ChooserOption::create(['key' => 'shop', 'family_id' => $family->id, 'why' => $this->tr('Потому что'), 'sort' => 1]);
        $string = UiString::create(['key' => 'hero.h1', 'ru' => 'Заголовок', 'uz' => 'Sarlavha', 'en' => 'Title']);
        $request = PartnerRequest::create(['company' => 'Shop', 'contact_name' => 'Ali', 'phone' => '+998901234567', 'type' => 'reseller', 'status' => 'new']);
        $lead = Lead::create(['first_name' => 'Ali', 'last_name' => 'Valiyev', 'phone' => '+998901234567']);
        $login = PartnerUser::create(['name' => 'Ali', 'login' => 'ali', 'password' => 'secret-pass-1', 'type' => 'reseller']);

        return [
            'staff' => [
                '/admin/regions', '/admin/regions/create', "/admin/regions/{$region->id}/edit",
                '/admin/cities', '/admin/cities/create', "/admin/cities/{$city->id}/edit",
                '/admin/partners', '/admin/partners/create', "/admin/partners/{$partner->id}/edit",
                '/admin/partner-requests', "/admin/partner-requests/{$request->id}",
                '/admin/leads', "/admin/leads/{$lead->id}",
                '/admin/strings', "/admin/strings/{$string->id}/edit",
                '/admin/faq', '/admin/faq/topics/create', "/admin/faq/topics/{$topic->id}/edit",
                '/admin/faq/items/create', "/admin/faq/items/{$item->id}/edit",
                '/admin/guide', '/admin/guide/entries/create', "/admin/guide/entries/{$entry->id}/edit",
                '/admin/guide/checks/create', "/admin/guide/checks/{$check->id}/edit",
                '/admin/chooser', '/admin/chooser/create', "/admin/chooser/{$option->id}/edit",
            ],
            'admin' => [
                '/admin/partner-users', '/admin/partner-users/create', "/admin/partner-users/{$login->id}/edit",
                '/admin/settings',
            ],
        ];
    }

    public function test_guest_is_sent_to_the_login_page(): void
    {
        foreach (['/admin/partners', '/admin/leads', '/admin/strings', '/admin/settings', '/admin/partner-users'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
    }

    public function test_editor_opens_content_screens_but_not_settings_or_partner_logins(): void
    {
        $pages = $this->pages();
        $this->actingAs($this->editor());

        foreach ($pages['staff'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach ($pages['admin'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_admin_opens_everything(): void
    {
        $pages = $this->pages();
        $this->actingAs($this->admin());

        foreach ([...$pages['staff'], ...$pages['admin']] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_only_components_refuse_an_editor_even_without_the_route(): void
    {
        $this->actingAs($this->editor());

        Livewire::test(Settings\Index::class)->assertForbidden();
        Livewire::test(PartnerUsers\Index::class)->assertForbidden();
        Livewire::test(PartnerUsers\Form::class)->assertForbidden();
    }

    public function test_components_recheck_the_account_on_livewire_requests(): void
    {
        $user = $this->editor();
        $this->actingAs($user);
        $page = Livewire::test(Partners\Index::class)->assertOk();

        $user->update(['is_active' => false]);
        $page->call('sort', 'kind')->assertForbidden();
    }

    public function test_sidebar_shows_the_content_sections_and_hides_settings_from_editors(): void
    {
        $this->actingAs($this->editor())->get('/admin/partners')
            ->assertOk()
            ->assertSee(route('admin.regions.index'))
            ->assertSee(route('admin.cities.index'))
            ->assertSee(route('admin.partner-requests.index'))
            ->assertSee(route('admin.leads.index'))
            ->assertSee(route('admin.strings.index'))
            ->assertSee(route('admin.faq.index'))
            ->assertSee(route('admin.guide.index'))
            ->assertSee(route('admin.chooser.index'))
            ->assertDontSee('Настройки сайта');

        $this->actingAs($this->admin())->get('/admin/partners')
            ->assertSee(route('admin.settings'))
            ->assertSee(route('admin.partner-users.index'));
    }

    public function test_no_content_screen_shows_a_price(): void
    {
        $pages = $this->pages();
        $this->actingAs($this->admin());

        foreach ([...$pages['staff'], ...$pages['admin']] as $url) {
            $html = mb_strtolower($this->get($url)->getContent());
            foreach (['сум', 'soʻm', 'цена', 'цены', 'стоимость', '10 + 1'] as $word) {
                $this->assertStringNotContainsString($word, $html, "{$url} mentions «{$word}»");
            }
        }
    }
}
