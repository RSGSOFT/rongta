<?php

namespace Tests\Feature\Admin\Core;

use App\Livewire\Admin\Activity\Index as ActivityIndex;
use App\Livewire\Admin\Items\Families\Index as FamiliesIndex;
use App\Livewire\Admin\Staff\Form as StaffForm;
use App\Livewire\Admin\Staff\Index as StaffIndex;
use App\Models\Document;
use App\Models\Lead;
use App\Models\PartnerRequest;
use App\Models\UiString;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    public const ITEM_PAGES = [
        '/admin', '/admin/families', '/admin/families/create', '/admin/models', '/admin/models/create',
        '/admin/documents', '/admin/documents/create', '/admin/videos', '/admin/videos/create',
    ];

    public const ADMIN_PAGES = ['/admin/staff', '/admin/staff/create', '/admin/activity'];

    public function test_editor_can_open_every_items_page(): void
    {
        $this->actingAs($this->editor());

        foreach (self::ITEM_PAGES as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_editor_is_forbidden_from_staff_and_activity(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor);

        foreach ([...self::ADMIN_PAGES, "/admin/staff/{$editor->id}/edit"] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_admin_can_open_everything(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        foreach ([...self::ITEM_PAGES, ...self::ADMIN_PAGES, "/admin/staff/{$admin->id}/edit"] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_deactivated_staff_is_logged_out_on_the_next_page(): void
    {
        $user = $this->editor();
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_components_recheck_the_account_on_livewire_requests(): void
    {
        // Livewire updates do not pass the route middleware, so the component checks again.
        $user = $this->editor();
        $this->actingAs($user);
        $page = Livewire::test(FamiliesIndex::class)->assertOk();

        $user->update(['is_active' => false]);
        $page->call('sort', 'slug')->assertForbidden();
    }

    public function test_admin_only_components_refuse_an_editor_even_without_the_route(): void
    {
        $this->actingAs($this->editor());

        Livewire::test(StaffIndex::class)->assertForbidden();
        Livewire::test(StaffForm::class)->assertForbidden();
        Livewire::test(ActivityIndex::class)->assertForbidden();
    }

    public function test_sidebar_lists_existing_sections_and_hides_admin_links_from_editors(): void
    {
        $this->actingAs($this->editor())->get('/admin')
            ->assertOk()
            ->assertSee('Семейства')
            ->assertSee('Модели весов')
            ->assertSee('Документы и файлы')
            ->assertSee(route('admin.videos.index'))
            ->assertDontSee('Сотрудники')
            ->assertDontSee('Журнал действий');

        $this->actingAs($this->admin())->get('/admin')
            ->assertSee(route('admin.staff.index'))
            ->assertSee(route('admin.activity'));
    }

    public function test_sidebar_marks_the_current_section(): void
    {
        $html = $this->actingAs($this->editor())->get('/admin/models/create')->getContent();

        $this->assertMatchesRegularExpression('~href="'.preg_quote(route('admin.models.index'), '~').'"\s+aria-current="page"~', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function test_dashboard_counts_what_needs_attention(): void
    {
        $model = $this->scaleModel();
        $this->scaleModel($model->family, ['slug' => 'c3', 'name' => 'C3', 'is_published' => false]);
        Document::create(['scale_model_id' => $model->id, 'type' => 'manual', 'file_path' => null]);
        Document::create(['scale_model_id' => $model->id, 'type' => 'sdk', 'file_path' => 'documents/sdk.zip']);
        PartnerRequest::create(['company' => 'Shop', 'contact_name' => 'A', 'phone' => '+998901234567', 'type' => 'reseller', 'status' => 'new']);
        Lead::create(['first_name' => 'A', 'last_name' => 'B', 'phone' => '+998901234567']);
        UiString::create(['key' => 'x.full', 'ru' => 'a', 'uz' => 'b', 'en' => 'c']);
        UiString::create(['key' => 'x.gap', 'ru' => 'a', 'uz' => '', 'en' => null]);

        $this->actingAs($this->editor())->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['Модели весов', '2', 'скрыто с сайта: 1'])
            ->assertSeeInOrder(['Новые заявки на партнёрство', '1'])
            ->assertSeeInOrder(['Лиды из чат-бота', '1', 'всего 1'])
            ->assertSeeInOrder(['Документы без файла', '1'])
            ->assertSeeInOrder(['Тексты без перевода', '1'])
            ->assertSee(route('admin.documents.index', ['file' => 'missing']))
            ->assertDontSee('сум')
            ->assertDontSee('soʻm');
    }
}
