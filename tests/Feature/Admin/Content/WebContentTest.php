<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Chooser;
use App\Livewire\Admin\Faq;
use App\Livewire\Admin\Guide;
use App\Livewire\Admin\Strings;
use App\Models\ActivityLog;
use App\Models\ChooserOption;
use App\Models\FaqItem;
use App\Models\FaqTopic;
use App\Models\GuideCheck;
use App\Models\GuideEntry;
use App\Models\UiString;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\TestCase;

class WebContentTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->editor());
    }

    // Interface strings

    public function test_strings_search_and_missing_translation_filter(): void
    {
        UiString::create(['key' => 'hero.h1', 'ru' => 'Весы для магазина', 'uz' => 'Doʻkon uchun tarozi', 'en' => 'Scales for your shop']);
        UiString::create(['key' => 'nav.catalog', 'ru' => 'Каталог', 'uz' => '', 'en' => 'Catalog']);
        UiString::create(['key' => 'nav.dealers', 'ru' => 'Дилеры', 'uz' => 'Dilerlar', 'en' => null]);

        // Migrations add interface strings of their own (cabinet, serial check): show them all on one page.
        Livewire::test(Strings\Index::class)
            ->set('perPage', 500)
            ->assertSee('hero.h1')->assertSee('nav.catalog')->assertSee('Без перевода: 2')
            ->set('search', 'hero.')->assertSee('hero.h1')->assertDontSee('nav.catalog')
            ->set('search', 'doʻkon')->assertSee('hero.h1')->assertDontSee('nav.catalog')
            ->set('search', '')->set('filterMissing', 'any')->assertSee('nav.catalog')->assertSee('nav.dealers')->assertDontSee('hero.h1')
            ->set('filterMissing', 'uz')->assertSee('nav.catalog')->assertDontSee('nav.dealers')
            ->set('filterMissing', 'en')->assertSee('nav.dealers')->assertDontSee('nav.catalog')
            ->set('filterMissing', '')->set('filterGroup', 'nav')->assertSee('nav.dealers')->assertDontSee('hero.h1');
    }

    public function test_string_edit_is_reflected_by_ui_at_once(): void
    {
        $string = UiString::create(['key' => 'hero.h1', 'ru' => 'Старый заголовок', 'uz' => '', 'en' => 'Old title']);
        $this->assertSame('Старый заголовок', ui('hero.h1', [], 'ru')); // warms the cache

        Livewire::test(Strings\Form::class, ['string' => $string])
            ->assertSet('form.text.ru', 'Старый заголовок')
            ->set('form.text.ru', ' Новый заголовок ')
            ->set('form.text.uz', 'Yangi sarlavha')
            ->set('form.text.en', 'New title')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.strings.index'));

        $this->assertSame('Новый заголовок', ui('hero.h1', [], 'ru'));
        $this->assertSame('Yangi sarlavha', ui('hero.h1', [], 'uz'));
        $this->assertSame('New title', ui('hero.h1', [], 'en'));

        $log = ActivityLog::where('action', 'updated')->where('subject_type', UiString::class)->firstOrFail();
        $this->assertEqualsCanonicalizing(['ru', 'uz', 'en'], array_keys($log->changes));
    }

    public function test_string_validation_keeps_placeholders_and_russian(): void
    {
        $string = UiString::create(['key' => 'cat.count', 'ru' => 'Показано {n} из {all}', 'uz' => '{all} dan {n} ta', 'en' => '{n} of {all}']);

        Livewire::test(Strings\Form::class, ['string' => $string])
            ->assertSee('{all}')
            ->set('form.text.ru', '')
            ->call('save')
            ->assertHasErrors(['form.text.ru' => 'required']);

        Livewire::test(Strings\Form::class, ['string' => $string])
            ->set('form.text.en', '{n} of {total}')
            ->call('save')
            ->assertHasErrors('form.text.en')
            ->assertHasNoErrors('form.text.uz');

        // An empty translation is allowed and falls back to Russian on the site.
        Livewire::test(Strings\Form::class, ['string' => $string])->set('form.text.uz', '')->call('save')->assertHasNoErrors();
        $this->assertNull($string->fresh()->uz);
        $this->assertSame('Показано 3 из 11', ui('cat.count', ['n' => 3, 'all' => 11], 'uz'));
        $this->assertSame('{n} of {all}', $string->fresh()->en);
    }

    // FAQ

    public function test_faq_topic_and_item_crud_with_delete_guard(): void
    {
        Livewire::test(Faq\TopicForm::class)
            ->set('form.slug', 'setup')
            ->set('form.name', $this->tr('Настройка'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.faq.index'));
        $topic = FaqTopic::firstOrFail();

        Livewire::test(Faq\TopicForm::class)->set('form.slug', 'setup')->set('form.name', ['ru' => '', 'uz' => '', 'en' => ''])->call('save')
            ->assertHasErrors(['form.slug' => 'unique', 'form.name.ru' => 'required']);

        Livewire::withQueryParams(['topic' => $topic->id])->test(Faq\ItemForm::class)
            ->assertSet('form.faq_topic_id', (string) $topic->id)
            ->set('form.question', $this->tr('Как заправить этикетки?'))
            ->set('form.answer', ['ru' => 'Откройте крышку.', 'uz' => '', 'en' => 'Open the cover.'])
            ->call('save')
            ->assertHasNoErrors();
        $item = FaqItem::firstOrFail();
        $this->assertSame($topic->id, $item->faq_topic_id);
        $this->assertSame('Open the cover.', $item->answer['en']);

        Livewire::test(Faq\ItemForm::class)->set('form.faq_topic_id', 999)->call('save')
            ->assertHasErrors(['form.faq_topic_id' => 'exists', 'form.question.ru' => 'required', 'form.answer.ru' => 'required']);

        Livewire::test(Faq\ItemForm::class, ['item' => $item])->set('form.answer.uz', 'Qopqoqni oching.')->call('save')->assertHasNoErrors();
        $this->assertSame('Qopqoqni oching.', $item->fresh()->answer['uz']);

        Livewire::test(Faq\Index::class)
            ->assertSee('Настройка')->assertSee('Как заправить этикетки?')
            ->set('search', 'крышку')->assertSee('Как заправить этикетки?')
            ->set('search', 'нет такого')->assertDontSee('Как заправить этикетки?')
            ->set('search', '')
            ->call('deleteTopic', $topic->id)
            ->assertSee('Нельзя удалить тему');
        $this->assertNotNull($topic->fresh());

        // The question is on the public knowledge base.
        $this->get('/knowledge')->assertOk()->assertSee('Как заправить этикетки?');

        Livewire::test(Faq\Index::class)->call('deleteItem', $item->id)->call('deleteTopic', $topic->id);
        $this->assertSame(0, FaqItem::count());
        $this->assertSame(0, FaqTopic::count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'subject_type' => FaqTopic::class]);
    }

    // Buying guide

    public function test_guide_entry_crud_and_one_entry_per_family(): void
    {
        $family = $this->family();
        $fields = ['does' => 'Печатают этикетки', 'fits' => 'Магазинам', 'limit' => 'Нет ИИ', 'peak' => 'Справляются'];

        $page = Livewire::test(Guide\EntryForm::class)->set('form.family_id', $family->id);
        foreach ($fields as $field => $text) {
            $page->set('form.'.$field, $this->tr($text));
        }
        $page->assertDontSee('Расходы')->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.guide.index'));

        $entry = GuideEntry::firstOrFail();
        $this->assertArrayNotHasKey('cost', $entry->getAttributes());
        $this->assertSame('Магазинам uz', $entry->fits['uz']);

        Livewire::test(Guide\EntryForm::class)->set('form.family_id', $family->id)->call('save')
            ->assertHasErrors(['form.family_id' => 'unique', 'form.does.ru' => 'required']);

        Livewire::test(Guide\EntryForm::class, ['entry' => $entry])->set('form.peak.en', 'Copes well')->call('save')->assertHasNoErrors();
        $this->assertSame('Copes well', $entry->fresh()->peak['en']);

        Livewire::test(Guide\Index::class)->assertSee('Label scales')->assertDontSee('Расходы')->call('deleteEntry', $entry->id);
        $this->assertSame(0, GuideEntry::count());
    }

    public function test_guide_checklist_crud(): void
    {
        Livewire::test(Guide\CheckForm::class)->call('save')->assertHasErrors(['form.text.ru' => 'required']);

        Livewire::test(Guide\CheckForm::class)->set('form.text', $this->tr('Уточните наибольший вес'))->call('save')
            ->assertHasNoErrors()->assertRedirect(route('admin.guide.index'));
        $check = GuideCheck::firstOrFail();

        Livewire::test(Guide\CheckForm::class, ['check' => $check])->set('form.text.ru', 'Уточните вес товара')->set('form.sort', 5)->call('save')->assertHasNoErrors();
        $this->assertSame('Уточните вес товара', $check->fresh()->text['ru']);
        $this->assertSame(5, $check->fresh()->sort);

        Livewire::test(Guide\Index::class)->assertSee('Уточните вес товара')->call('deleteCheck', $check->id);
        $this->assertSame(0, GuideCheck::count());
    }

    // Chooser

    public function test_chooser_option_with_models_notes_and_label(): void
    {
        $family = $this->family();
        $c1 = $this->scaleModel($family);
        $c3 = $this->scaleModel($family, ['slug' => 'c3', 'name' => 'C3', 'sort' => 2]);

        Livewire::test(Chooser\Form::class)
            ->set('form.key', 'shop')
            ->set('form.label', ['ru' => 'Магазин у дома', 'uz' => 'Uy yonidagi doʻkon', 'en' => 'Corner shop'])
            ->set('form.family_id', $family->id)
            ->set('form.why', $this->tr('Просто и надёжно'))
            ->call('addModel')->call('addModel')
            ->set('models.0.id', (string) $c3->id)
            ->set('models.0.note', ['ru' => 'Для влажных помещений', 'uz' => '', 'en' => ''])
            ->set('models.1.id', (string) $c1->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.chooser.index'));

        $option = ChooserOption::firstOrFail();
        $this->assertSame('shop', $option->key);
        $this->assertSame(['c3', 'c1'], $option->scaleModels->pluck('slug')->all());
        $this->assertSame('Для влажных помещений', $option->scaleModels[0]->pivot->note['ru']);
        $this->assertNull($option->scaleModels[1]->pivot->note);
        $this->assertSame('Corner shop', ui('ch.shop', [], 'en'));

        // Reorder, drop a model, change the label; the key cannot be changed.
        Livewire::test(Chooser\Form::class, ['option' => $option])
            ->assertSet('form.label.uz', 'Uy yonidagi doʻkon')
            ->assertSet('models.0.id', (string) $c3->id)
            ->call('moveModel', 0, 1)
            ->assertSet('models.0.id', (string) $c1->id)
            ->call('removeModel', 1)
            ->set('form.key', 'hacked')
            ->set('form.label.ru', 'Небольшой магазин')
            ->call('save')
            ->assertHasNoErrors();

        $option = $option->fresh();
        $this->assertSame('shop', $option->key);
        $this->assertSame(['c1'], $option->scaleModels->pluck('slug')->all());
        $this->assertSame('Небольшой магазин', ui('ch.shop', [], 'ru'));
        $this->assertArrayHasKey('models', ActivityLog::where('action', 'updated')->where('subject_type', ChooserOption::class)->latest('id')->firstOrFail()->changes);

        // The home page shows the option.
        $this->get('/')->assertOk()->assertSee('Небольшой магазин')->assertSee('Просто и надёжно');
    }

    public function test_chooser_validation_and_delete_guard(): void
    {
        $family = $this->family();
        $model = $this->scaleModel($family);
        $only = ChooserOption::create(['key' => 'shop', 'family_id' => $family->id, 'why' => $this->tr('x'), 'sort' => 1]);

        Livewire::test(Chooser\Form::class)
            ->set('form.key', 'shop')
            ->call('addModel')->call('addModel')->call('addModel')
            ->set('models.0.id', (string) $model->id)
            ->set('models.1.id', (string) $model->id)
            ->call('save')
            ->assertHasErrors(['form.key' => 'unique', 'form.label.ru' => 'required', 'form.family_id' => 'required', 'form.why.ru' => 'required', 'models.1.id' => 'distinct', 'models.2.id' => 'required']);

        Livewire::test(Chooser\Form::class)->set('form.key', 'Bad Key')->call('save')->assertHasErrors(['form.key' => 'regex']);

        Livewire::test(Chooser\Index::class)->call('delete', $only->id)->assertSee('Нельзя удалить последний вариант');
        $this->assertNotNull($only->fresh());

        $second = ChooserOption::create(['key' => 'market', 'family_id' => $family->id, 'why' => $this->tr('y'), 'sort' => 2]);
        $second->scaleModels()->attach($model->id, ['sort' => 1]);
        Livewire::test(Chooser\Index::class)->call('delete', $second->id);
        $this->assertNull($second->fresh());
        $this->assertDatabaseCount('chooser_option_scale_model', 0);
    }
}
