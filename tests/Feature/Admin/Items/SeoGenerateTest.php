<?php

namespace Tests\Feature\Admin\Items;

use App\Livewire\Admin\Items\Models\Form;
use App\Models\ActivityLog;
use App\Models\ModelSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Admin\Core\MakesStaff;
use Tests\Feature\Chatbot\FakeClaude;
use Tests\TestCase;

/** SEO title and description of model pages: the "Generate SEO" button and the page head. */
class SeoGenerateTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->editor());
    }

    private function reply(array $overrides = []): string
    {
        return json_encode(array_replace_recursive([
            'title' => ['ru' => 'C1: весы для магазина', 'uz' => "C1: do'kon uchun tarozi", 'en' => 'C1: shop scale'],
            'description' => [
                'ru' => 'Весы C1 подключаются к кассе по RS232. Купить у дилеров в Узбекистане.',
                'uz' => "C1 tarozisi kassaga RS232 orqali ulanadi. O'zbekistondagi dilerlardan oling.",
                'en' => 'The C1 scale connects to the till over RS232. Available from dealers in Uzbekistan.',
            ],
        ], $overrides));
    }

    public function test_generate_fills_the_form_from_the_model_facts_and_save_keeps_it(): void
    {
        $model = $this->scaleModel(attributes: ['conn' => ['rs232' => 'std', 'wifi' => 'opt']]);
        ModelSpec::create(['scale_model_id' => $model->id, 'group' => 'weighing', 'key' => 'maxCap', 'value' => ['ru' => '30 кг', 'uz' => '30 kg', 'en' => '30 kg'], 'sort' => 1]);
        $fake = FakeClaude::bind()->text($this->reply());

        Livewire::test(Form::class, ['model' => $model])
            ->call('generateSeo')
            ->assertSet('seoProblem', null)
            ->assertSet('form.seo_title.uz', 'C1: doʻkon uchun tarozi')
            ->assertSet('form.seo_description.uz', 'C1 tarozisi kassaga RS232 orqali ulanadi. Oʻzbekistondagi dilerlardan oling.')
            ->call('save')
            ->assertHasNoErrors();

        $facts = $fake->requests[0]['messages'][0]['content'];
        $this->assertStringContainsString('Model name: C1', $facts);
        $this->assertStringContainsString('Connections: RS232, Wi-Fi (optional)', $facts);
        $this->assertStringContainsString(': 30 kg', $facts);
        $this->assertSame(['type' => 'adaptive'], $fake->requests[0]['thinking']);

        $model->refresh();
        $this->assertSame('C1: shop scale', $model->seo_title['en']);
        $this->assertSame(1, ActivityLog::where('action', 'seo_generated')->count());

        $this->get('/en/model/c1')->assertOk()
            ->assertSee('<title>C1: shop scale · ', false)
            ->assertSee('content="The C1 scale connects to the till over RS232. Available from dealers in Uzbekistan."', false);
    }

    public function test_a_price_in_the_reply_is_refused(): void
    {
        $model = $this->scaleModel();
        FakeClaude::bind()->text($this->reply(['description' => ['ru' => 'Весы C1 по выгодной цене у дилеров.']]));

        Livewire::test(Form::class, ['model' => $model])
            ->call('generateSeo')
            ->assertSet('seoProblem', 'В ответе Claude оказалось упоминание цены, такой текст не подходит. Попробуйте ещё раз.')
            ->assertSet('form.seo_title.ru', '');
    }

    public function test_missing_key_and_missing_name_are_explained(): void
    {
        FakeClaude::bind(configured: false);
        Livewire::test(Form::class)
            ->assertSee('Ключ Claude API не задан')
            ->set('form.name', 'C3')
            ->call('generateSeo')
            ->assertSet('seoProblem', 'Ключ Claude API не задан. Добавьте ANTHROPIC_API_KEY в файл .env.');

        FakeClaude::bind()->text($this->reply());
        Livewire::test(Form::class)
            ->call('generateSeo')
            ->assertSet('seoProblem', 'Сначала введите название модели.');
    }

    public function test_page_head_falls_back_to_name_and_intro_per_language(): void
    {
        $this->scaleModel(attributes: [
            'intro' => $this->tr('Вступление'),
            'seo_title' => ['ru' => 'C1: весы для магазина', 'uz' => '', 'en' => ''],
        ]);

        $this->get('/model/c1')->assertSee('<title>C1: весы для магазина · ', false);
        $this->get('/uz/model/c1')->assertSee('<title>C1 · ', false)->assertSee('content="Вступление uz"', false);
    }
}
