<?php

namespace Tests\Feature\Admin\Core;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\ActivityLog;
use App\Models\Family;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

/** The component kit other admin screens are built on: names, props and slots of the contract. */
class KitTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    public function test_page_renders_title_actions_flash_and_content(): void
    {
        session()->flash('status', 'Сохранено.');

        $html = Blade::render('<x-admin.page title="Партнёры" subtitle="Список">BODY<x-slot:actions><x-admin.btn variant="primary" href="/admin/x">Добавить</x-admin.btn></x-slot:actions></x-admin.page>');

        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('Партнёры', $html);
        $this->assertStringContainsString('Список', $html);
        $this->assertStringContainsString('href="/admin/x"', $html);
        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('Сохранено.', $html);
        $this->assertStringContainsString('BODY', $html);
    }

    public function test_flash_shows_status_and_error_and_nothing_when_empty(): void
    {
        $this->assertSame('', trim(Blade::render('<x-admin.flash />')));

        session()->flash('error', 'Нельзя.');
        $html = Blade::render('<x-admin.flash />');
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('Нельзя.', $html);
    }

    public function test_field_shows_label_hint_and_error(): void
    {
        $html = Blade::render('<x-admin.field label="Телефон" for="phone" error="Заполните поле." hint="Формат +998" required><x-admin.input id="phone" wire:model="form.phone" /></x-admin.field>');

        $this->assertStringContainsString('for="phone"', $html);
        $this->assertStringContainsString('Телефон', $html);
        $this->assertStringContainsString('Формат +998', $html);
        $this->assertStringContainsString('Заполните поле.', $html);
        $this->assertStringContainsString('wire:model="form.phone"', $html);
    }

    public function test_controls_forward_attributes(): void
    {
        $html = Blade::render(
            '<x-admin.input type="email" wire:model.live="a" placeholder="p" />'
            .'<x-admin.textarea wire:model="b" rows="5" />'
            .'<x-admin.select wire:model="c" placeholder="Все" :options="[\'x\' => \'Икс\']"><option value="y">Игрек</option></x-admin.select>'
            .'<x-admin.toggle wire:model="d" label="Опубликован" />'
        );

        $this->assertStringContainsString('<input type="email"', $html);
        $this->assertStringContainsString('wire:model.live="a"', $html);
        $this->assertStringContainsString('<textarea rows="5"', $html);
        $this->assertStringContainsString('wire:model="b"', $html);
        $this->assertStringContainsString('<option value="">Все</option>', $html);
        $this->assertStringContainsString('<option value="x">Икс</option>', $html);
        $this->assertStringContainsString('<option value="y">Игрек</option>', $html);
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('wire:model="d"', $html);
        $this->assertStringContainsString('Опубликован', $html);
    }

    public function test_trans_input_binds_three_languages_and_flags_missing_ones(): void
    {
        $html = Blade::render('<x-admin.trans-input label="Название" model="form.name" required />');

        foreach (['ru', 'uz', 'en'] as $locale) {
            $this->assertStringContainsString('wire:model="form.name.'.$locale.'"', $html);
            $this->assertStringContainsString('lang="'.$locale.'"', $html);
        }
        $this->assertSame(3, substr_count($html, '<input type="text"'));
        $this->assertStringContainsString('нет перевода', $html);
        $this->assertStringContainsString('is-missing', $html);
        $this->assertStringContainsString('Название', $html);
    }

    public function test_trans_textarea_binds_three_textareas(): void
    {
        $html = Blade::render('<x-admin.trans-textarea label="Описание" model="form.text" rows="4" live />');

        $this->assertSame(3, substr_count($html, '<textarea'));
        $this->assertStringContainsString('wire:model.live.debounce.400ms="form.text.uz"', $html);
        $this->assertStringContainsString('rows="4"', $html);
    }

    public function test_table_renders_slots_and_empty_state(): void
    {
        $html = Blade::render('<x-admin.table :count="0" empty="Пусто."> <x-slot:head><x-admin.th sort="name" by="name" dir="desc">Имя</x-admin.th><x-admin.th>Код</x-admin.th></x-slot:head></x-admin.table>');

        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString("wire:click=\"sort('name')\"", $html);
        $this->assertStringContainsString('aria-sort="descending"', $html);
        $this->assertStringContainsString('Пусто.', $html);

        $html = Blade::render('<x-admin.table><tr><td>ROW</td></tr></x-admin.table>');
        $this->assertStringContainsString('ROW', $html);
        $this->assertStringNotContainsString('Ничего не найдено', $html);
    }

    public function test_btn_variants_links_and_confirmation(): void
    {
        $html = Blade::render('<x-admin.btn variant="danger" wire:click="delete(3)" confirm="Удалить?">Удалить</x-admin.btn>');
        $this->assertStringContainsString('<button type="button"', $html);
        $this->assertStringContainsString('wire:confirm="Удалить?"', $html);
        $this->assertStringContainsString('wire:click="delete(3)"', $html);
        $this->assertStringContainsString('text-danger', $html);

        $this->assertStringContainsString('bg-accent', Blade::render('<x-admin.btn variant="primary" type="submit">OK</x-admin.btn>'));
        $this->assertStringContainsString('<a href="/x"', Blade::render('<x-admin.btn href="/x">Link</x-admin.btn>'));
    }

    public function test_with_table_searches_json_columns_sorts_and_paginates(): void
    {
        $this->actingAs($this->editor());
        foreach (['alpha', 'beta', 'gamma'] as $i => $slug) {
            Family::create(['slug' => $slug, 'name' => $this->tr(ucfirst($slug).' scales'), 'line' => $this->tr('l'), 'sort' => 3 - $i]);
        }

        $page = Livewire::test(KitTableProbe::class);
        $this->assertSame(['gamma', 'beta'], $page->viewData('rows')->pluck('slug')->all()); // default: sort asc, 2 per page
        $this->assertSame(3, $page->viewData('rows')->total());

        $page->call('nextPage');
        $this->assertSame(['alpha'], $page->viewData('rows')->pluck('slug')->all());

        // Searching goes back to page 1 and looks inside the JSON name, ignoring case.
        $page->set('search', 'BETA SC');
        $this->assertSame(['beta'], $page->viewData('rows')->pluck('slug')->all());

        $page->set('search', '')->call('sort', 'slug');
        $this->assertSame(['alpha', 'beta'], $page->viewData('rows')->pluck('slug')->all());
        $page->call('sort', 'slug')->assertSet('sortDir', 'desc');
        $this->assertSame(['gamma', 'beta'], $page->viewData('rows')->pluck('slug')->all());

        // Columns outside $sortable are ignored.
        $page->call('sort', 'name')->assertSet('sortBy', 'slug');
    }

    public function test_search_treats_like_wildcards_as_text(): void
    {
        $this->actingAs($this->editor());
        Family::create(['slug' => 'alpha', 'name' => $this->tr('Alpha'), 'line' => $this->tr('l'), 'sort' => 1]);

        $page = Livewire::test(KitTableProbe::class)->set('search', '%');
        $this->assertSame(0, $page->viewData('rows')->total());
    }

    public function test_logs_activity_trait_writes_rows_without_secrets(): void
    {
        $user = $this->admin();
        $this->actingAs($user);
        $probe = new KitLogProbe;

        $family = $this->family();
        $probe->saved($family);
        $family = Family::findOrFail($family->id);
        $family->update(['sort' => 9]);
        $probe->saved($family);
        $family = Family::findOrFail($family->id);
        $family->update(['sort' => 9]); // nothing changed: no row
        $probe->saved($family);
        $probe->custom('updated', $user, ['password' => 'hash', 'name' => 'New', 'remember_token' => 'x']);
        $probe->deleted($family);

        $logs = ActivityLog::orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'updated', 'deleted'], $logs->pluck('action')->all());
        $this->assertTrue($logs->every(fn ($log) => $log->user_id === $user->id));
        $this->assertSame(Family::class, $logs[0]->subject_type);
        $this->assertSame($family->id, $logs[0]->subject_id);
        $this->assertSame(['sort' => 9], $logs[1]->changes);
        $this->assertSame(['name' => 'New'], $logs[2]->changes);
        $this->assertSame('label', $logs[3]->changes['slug']);
    }

    public function test_translation_helpers_normalise_values(): void
    {
        $probe = new KitLogProbe;

        $this->assertSame(['ru' => 'a', 'uz' => '', 'en' => ''], $probe->fill('a'));
        $this->assertSame(['ru' => '', 'uz' => 'b', 'en' => ''], $probe->fill(['uz' => 'b', 'xx' => 'c']));
        $this->assertSame(['ru' => 'a', 'uz' => '', 'en' => 'c'], $probe->clean(['ru' => ' a ', 'en' => "c\n"]));
        $this->assertSame(['required', 'string', 'max:50'], $probe->rules('form.name')['form.name.ru']);
        $this->assertSame(['nullable', 'string', 'max:50'], $probe->rules('form.name')['form.name.uz']);
    }
}

class KitTableProbe extends Component
{
    use WithTable;

    protected array $searchable = ['name', 'slug'];

    protected array $sortable = ['slug', 'sort'];

    protected string $defaultSort = 'sort';

    public function mount(): void
    {
        $this->perPage = 2;
    }

    public function render()
    {
        return view('livewire.admin.items.families-index', ['rows' => $this->tableRows(Family::query()->withCount('scaleModels'))]);
    }
}

class KitLogProbe
{
    use HandlesTranslations, LogsActivity;

    public function saved($model)
    {
        return $this->logSaved($model);
    }

    public function deleted($model)
    {
        return $this->logDeleted($model);
    }

    public function custom(string $action, $model, array $changes)
    {
        return $this->logActivity($action, $model, $changes);
    }

    public function fill($value): array
    {
        return $this->transFill($value);
    }

    public function clean($value): array
    {
        return $this->transClean($value);
    }

    public function rules(string $field): array
    {
        return $this->transRules($field, max: 50);
    }
}
