<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Leads;
use App\Livewire\Admin\PartnerRequests;
use App\Models\ChatConversation;
use App\Models\Lead;
use App\Models\PartnerRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class InboxTest extends TestCase
{
    use MakesContent, MakesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->editor());
    }

    protected function request(array $attributes = []): PartnerRequest
    {
        return PartnerRequest::create($attributes + [
            'company' => 'Mega Market',
            'contact_name' => 'Ali Valiyev',
            'phone' => '+998901234567',
            'type' => 'reseller',
            'message' => 'Хотим продавать весы',
            'status' => 'new',
        ]);
    }

    public function test_partner_requests_list_filters_and_quick_status(): void
    {
        $region = $this->region();
        $new = $this->request(['region_id' => $region->id]);
        $this->request(['company' => 'Soft House', 'type' => 'integrator', 'status' => 'done']);

        Livewire::test(PartnerRequests\Index::class)
            ->assertSee('Mega Market')->assertSee('Soft House')->assertSee('Новых: 1')
            ->set('filterStatus', 'done')->assertSee('Soft House')->assertDontSee('Mega Market')
            ->set('filterStatus', '')->set('filterType', 'reseller')->assertSee('Mega Market')->assertDontSee('Soft House')
            ->set('filterType', '')->set('search', 'soft')->assertSee('Soft House')->assertDontSee('Mega Market')
            ->set('search', '')
            ->call('setStatus', $new->id, 'in_progress')
            ->call('setStatus', $new->id, 'nonsense');

        $this->assertSame('in_progress', $new->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'status_changed', 'subject_type' => PartnerRequest::class, 'subject_id' => $new->id]);
    }

    public function test_partner_request_detail_and_status(): void
    {
        $region = $this->region();
        $request = $this->request(['region_id' => $region->id]);

        Livewire::test(PartnerRequests\Show::class, ['partnerRequest' => $request])
            ->assertSee('Mega Market')->assertSee('Ali Valiyev')->assertSee('+998901234567')->assertSee('Хотим продавать весы')->assertSee('Ташкент')
            ->set('status', 'archived')->call('save')->assertHasErrors(['status' => 'in'])
            ->set('status', 'done')->call('save')->assertHasNoErrors()->assertSee('Статус заявки сохранён');

        $this->assertSame('done', $request->fresh()->status);

        Livewire::test(PartnerRequests\Show::class, ['partnerRequest' => $request])->call('delete')->assertRedirect(route('admin.partner-requests.index'));
        $this->assertSame(0, PartnerRequest::count());
    }

    public function test_request_text_is_escaped(): void
    {
        $request = $this->request(['company' => '<script>alert(1)</script>', 'message' => '<img src=x onerror=alert(1)>']);

        $html = $this->get("/admin/partner-requests/{$request->id}")->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
    }

    public function test_leads_list_search_and_detail_with_conversations(): void
    {
        $lead = Lead::create(['first_name' => 'Ali', 'last_name' => 'Valiyev', 'phone' => '+998901234567', 'locale' => 'uz', 'source' => 'chatbot', 'page_url' => 'https://rongta.uz/uz/catalog']);
        Lead::create(['first_name' => 'Olga', 'last_name' => 'Kim', 'phone' => '+998935550011', 'locale' => 'ru', 'source' => 'chatbot']);
        $conversation = ChatConversation::create(['lead_id' => $lead->id, 'locale' => 'uz', 'unanswered_count' => 2]);
        $this->actingAs($this->admin());

        Livewire::test(Leads\Index::class)
            ->assertSee('Valiyev')->assertSee('Kim')
            ->set('search', 'kim')->assertSee('Kim')->assertDontSee('Valiyev')
            ->set('search', '5550011')->assertSee('Kim')->assertDontSee('Valiyev')
            ->set('search', '')->set('filterLocale', 'uz')->assertSee('Valiyev')->assertDontSee('Kim')
            ->set('filterLocale', '')->set('filterFrom', now()->addDay()->format('Y-m-d'))->assertDontSee('Valiyev')
            ->set('filterFrom', 'not-a-date')->assertSee('Valiyev');

        $this->get("/admin/leads/{$lead->id}")
            ->assertOk()
            ->assertSee('Valiyev')
            ->assertSee('+998901234567')
            ->assertSee('https://rongta.uz/uz/catalog')
            ->assertSee('Диалоги с чат-ботом: 1')
            ->assertSee(route('admin.chatbot.conversations.show', $conversation));

        $this->assertSame(1, $lead->conversations()->count());
    }

    public function test_leads_are_for_admins_only(): void
    {
        $lead = Lead::create(['first_name' => 'Ali', 'last_name' => 'Valiyev', 'phone' => '+998901234567']);

        // setUp signed in an editor.
        $this->get('/admin/leads')->assertForbidden();
        $this->get("/admin/leads/{$lead->id}")->assertForbidden();
        Livewire::test(Leads\Index::class)->assertForbidden();
        Livewire::test(Leads\Show::class, ['lead' => $lead])->assertForbidden();

        $this->get('/admin')->assertOk()->assertDontSee('Лиды из чат-бота')->assertDontSee(route('admin.leads.index'));

        $this->actingAs($this->admin());
        $this->get('/admin')->assertOk()->assertSee('Лиды из чат-бота')->assertSee(route('admin.leads.index'));
        $this->get('/admin/leads')->assertOk()->assertSee('Valiyev');
    }

    public function test_leads_csv_export_follows_the_filter(): void
    {
        $lead = Lead::create(['first_name' => '=HYPERLINK("http://evil")', 'last_name' => 'Valiyev', 'phone' => '+998901234567', 'locale' => 'uz', 'source' => 'chatbot']);
        Lead::create(['first_name' => 'Ольга', 'last_name' => 'Ким; Ли', 'phone' => '+998935550011', 'locale' => 'ru', 'source' => 'chatbot']);
        ChatConversation::create(['lead_id' => $lead->id, 'locale' => 'uz']);
        $this->actingAs($this->admin());

        $all = Livewire::test(Leads\Index::class)->call('export')->assertFileDownloaded();
        $csv = base64_decode($all->effects['download']['content']);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringEndsWith('.csv', $all->effects['download']['name']);
        $lines = array_values(array_filter(explode("\n", substr($csv, 3))));
        $this->assertCount(3, $lines);
        $this->assertSame('ID;Дата;Имя;Фамилия;Телефон;Язык;Источник;Страница;Диалогов', $lines[0]);

        $first = str_getcsv($lines[1], ';', '"', '');
        $this->assertSame("'=HYPERLINK(\"http://evil\")", $first[2]); // formula neutralised
        $this->assertSame('+998901234567', $first[4]);                // phone kept as typed
        $this->assertSame('1', $first[8]);

        $second = str_getcsv($lines[2], ';', '"', '');
        $this->assertSame(['Ольга', 'Ким; Ли'], [$second[2], $second[3]]);

        $filtered = Livewire::test(Leads\Index::class)->set('filterLocale', 'ru')->call('export')->assertFileDownloaded();
        $rows = array_values(array_filter(explode("\n", substr(base64_decode($filtered->effects['download']['content']), 3))));
        $this->assertCount(2, $rows);
        $this->assertStringContainsString('Ольга', $rows[1]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'exported']);
    }
}
