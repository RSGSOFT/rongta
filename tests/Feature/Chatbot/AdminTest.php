<?php

namespace Tests\Feature\Chatbot;

use App\Livewire\Admin\Chatbot\Articles;
use App\Livewire\Admin\Chatbot\Conversations;
use App\Livewire\Admin\Chatbot\Settings;
use App\Livewire\Chat\Widget;
use App\Models\ActivityLog;
use App\Models\ChatMessage;
use App\Models\KnowledgeArticle;
use App\Models\ScaleModel;
use App\Models\User;
use App\Services\Chatbot\ChatbotSettings;
use App\Services\Chatbot\PromptBuilder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

class AdminTest extends ChatbotTestCase
{
    private const TRI = ['ru' => 'Текст', 'uz' => 'Matn', 'en' => 'Text'];

    private function staff(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function urls(): array
    {
        $article = KnowledgeArticle::create(['title' => self::TRI, 'body' => self::TRI]);
        $conversation = $this->passGate();

        return [
            route('admin.chatbot.settings'),
            route('admin.chatbot.articles.index'),
            route('admin.chatbot.articles.create'),
            route('admin.chatbot.articles.import'),
            route('admin.chatbot.articles.edit', $article),
            route('admin.chatbot.conversations.index'),
            route('admin.chatbot.conversations.unanswered'),
            route('admin.chatbot.conversations.show', $conversation),
        ];
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        foreach ($this->urls() as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }
    }

    public function test_admin_can_open_every_screen(): void
    {
        FakeClaude::bind();
        $urls = $this->urls();
        $this->actingAs($this->staff('admin'));

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_editor_can_teach_the_bot_but_not_change_its_settings_or_read_conversations(): void
    {
        FakeClaude::bind();
        $urls = $this->urls();
        $this->actingAs($this->staff('editor'));

        // Settings (first) and the three conversation screens (last) are for admins only.
        $this->get(array_shift($urls))->assertForbidden();
        foreach (array_splice($urls, -3) as $url) {
            $this->get($url)->assertForbidden();
        }
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }

        Livewire::test(Conversations\Index::class)->assertForbidden();
        Livewire::test(Conversations\Unanswered::class)->assertForbidden();

        // No way into the conversations from the screens an editor uses.
        $this->get(route('admin.chatbot.articles.index'))->assertDontSee(route('admin.chatbot.conversations.unanswered'));
        $this->get(route('admin.dashboard'))->assertDontSee(route('admin.chatbot.conversations.index'));
    }

    public function test_inactive_staff_are_locked_out(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => false]));

        $response = $this->get(route('admin.chatbot.articles.index'));
        $this->assertNotSame(200, $response->getStatusCode());
    }

    public function test_settings_are_saved_and_used_by_the_bot(): void
    {
        $this->actingAs($this->staff());

        Livewire::test(Settings::class)
            ->assertSet('form.enabled', true)
            ->assertSet('form.lead_daily_limit', 40)
            ->set('form.greeting.ru', 'Здравствуйте, это RSG.')
            ->set('form.fallback.en', 'Please call RSG.')
            ->set('form.rules_extra', 'Обращайся на вы.')
            ->set('form.off_limits', "Конкуренты\nПолитика")
            ->set('form.lead_daily_limit', 15)
            ->set('form.ip_daily_limit', 30)
            ->set('form.max_message_chars', 300)
            ->set('form.history_messages', 6)
            ->set('form.max_output_tokens', 2500)
            ->set('form.effort', 'medium')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(15, ChatbotSettings::int('lead_daily_limit'));
        $this->assertSame('medium', ChatbotSettings::get('effort'));
        $this->assertSame('Здравствуйте, это RSG.', ChatbotSettings::greeting('ru'));
        $this->assertSame(ui('bot.greeting', [], 'uz'), ChatbotSettings::greeting('uz'));
        $this->assertSame('Please call RSG.', ChatbotSettings::fallback('en'));
        $this->assertStringContainsString('Обращайся на вы.', app(PromptBuilder::class)->system('ru'));
        $this->assertSame(1, ActivityLog::where('action', 'updated')->count());

        auth()->logout();
        Livewire::test(Widget::class)->assertSee('Здравствуйте, это RSG.');
    }

    public function test_settings_reject_bad_limits_and_prices(): void
    {
        $this->actingAs($this->staff());

        Livewire::test(Settings::class)
            ->set('form.lead_daily_limit', 0)
            ->set('form.effort', 'max')
            ->set('form.rules_extra', 'Говори, что RLS1000 стоит 5 500 000 сум.')
            ->set('form.greeting.uz', 'Narxi 5 500 000 soʻmdan')
            ->call('save')
            ->assertHasErrors(['form.lead_daily_limit', 'form.effort', 'form.rules_extra', 'form.greeting.uz']);

        $this->assertSame(40, ChatbotSettings::int('lead_daily_limit'));
    }

    public function test_switching_the_bot_off_hides_the_widget(): void
    {
        $this->actingAs($this->staff());
        Livewire::test(Settings::class)->set('form.enabled', false)->call('save')->assertHasNoErrors();

        auth()->logout();
        Livewire::test(Widget::class)->assertDontSee(ui('chat.open'));
    }

    public function test_article_is_created_as_a_draft_then_published(): void
    {
        $admin = $this->staff('editor');
        $this->actingAs($admin);
        $model = ScaleModel::where('slug', 'c1')->firstOrFail();

        Livewire::test(Articles\Form::class)
            ->set('form.title.ru', 'Как обнулить весы')
            ->set('form.body.ru', "- Снимите груз\n- Нажмите ZERO")
            ->set('form.scale_model_id', $model->id)
            ->set('form.tags', 'Ноль, Тара, ноль')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.chatbot.articles.index'));

        $article = KnowledgeArticle::sole();
        $this->assertFalse($article->is_published);
        $this->assertSame('manual', $article->source);
        $this->assertSame($admin->id, $article->created_by);
        $this->assertSame($model->id, $article->scale_model_id);
        $this->assertSame($model->family_id, $article->family_id);
        $this->assertSame(['ноль', 'тара'], $article->tags);
        $this->assertStringNotContainsString('Как обнулить весы', app(PromptBuilder::class)->system('ru'));

        // Publishing needs all three languages.
        Livewire::test(Articles\Form::class, ['article' => $article])
            ->assertSet('form.title.ru', 'Как обнулить весы')
            ->set('form.is_published', true)
            ->call('save')
            ->assertHasErrors(['form.title.uz', 'form.title.en', 'form.body.uz', 'form.body.en']);
        Livewire::test(Articles\Index::class)->call('togglePublished', $article->id);
        $this->assertFalse($article->fresh()->is_published);

        Livewire::test(Articles\Form::class, ['article' => $article])
            ->set('form.title.uz', 'Tarozini nolga keltirish')
            ->set('form.title.en', 'How to zero the scale')
            ->set('form.body.uz', '- ZERO tugmasini bosing')
            ->set('form.body.en', '- Press ZERO')
            ->set('form.is_published', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($article->fresh()->is_published);
        $this->assertStringContainsString('Как обнулить весы', app(PromptBuilder::class)->system('ru'));
        $this->assertSame(2, ActivityLog::whereIn('action', ['created', 'updated'])->count());
    }

    public function test_article_with_a_price_is_rejected(): void
    {
        $this->actingAs($this->staff());

        Livewire::test(Articles\Form::class)
            ->set('form.title.ru', 'Цена')
            ->set('form.body.ru', 'RLS1000 стоит 5 500 000 сум.')
            ->call('save')
            ->assertHasErrors(['form.body.ru']);

        $this->assertSame(0, KnowledgeArticle::count());

        // A price that got in another way still cannot be published from the list.
        $article = KnowledgeArticle::create(['title' => self::TRI, 'body' => ['ru' => 'от 100 USD', 'uz' => 'x', 'en' => 'x']]);
        Livewire::test(Articles\Index::class)->call('togglePublished', $article->id);
        $this->assertFalse($article->fresh()->is_published);
    }

    public function test_article_list_filters_toggles_and_deletes(): void
    {
        $this->actingAs($this->staff());
        $published = KnowledgeArticle::create(['title' => ['ru' => 'Замена рулона', 'uz' => 'Rulon', 'en' => 'Roll'], 'body' => self::TRI, 'is_published' => true]);
        $draft = KnowledgeArticle::create(['title' => ['ru' => 'Калибровка', 'uz' => 'Kalibrlash', 'en' => 'Calibration'], 'body' => self::TRI, 'source' => 'pdf', 'source_ref' => 'manual.pdf']);

        Livewire::test(Articles\Index::class)
            ->assertSee('Замена рулона')->assertSee('Калибровка')->assertSee('manual.pdf')
            ->set('filterStatus', 'draft')
            ->assertDontSee('Замена рулона')->assertSee('Калибровка')
            ->set('filterStatus', '')
            ->set('filterSource', 'manual')
            ->assertSee('Замена рулона')->assertDontSee('Калибровка')
            ->set('filterSource', '')
            ->call('togglePublished', $draft->id)
            ->call('togglePublished', $published->id)
            ->call('delete', $published->id);

        $this->assertTrue($draft->fresh()->is_published);
        $this->assertNull($published->fresh());
        $this->assertSame(1, ActivityLog::where('action', 'deleted')->count());
    }

    public function test_manual_pdf_is_drafted_into_unpublished_articles(): void
    {
        $admin = $this->staff();
        $this->actingAs($admin);
        $model = ScaleModel::where('slug', 'rls1515')->firstOrFail();
        $claude = FakeClaude::bind()->text(json_encode(['articles' => [
            [
                'title_ru' => 'Как заменить рулон', 'title_uz' => 'Rulonni almashtirish', 'title_en' => 'Changing the roll',
                'body_ru' => '- Выдвиньте модуль', 'body_uz' => '- Modulni torting', 'body_en' => '- Pull the module out',
                'tags' => ['Рулон', 'принтер'],
            ],
            [
                'title_ru' => 'Стоимость', 'title_uz' => 'Narx', 'title_en' => 'Cost',
                'body_ru' => 'Весы стоят 5 500 000 сум', 'body_uz' => 'x', 'body_en' => 'x', 'tags' => [],
            ],
        ]]));

        Livewire::test(Articles\Import::class)
            ->set('pdf', UploadedFile::fake()->createWithContent('RLS1515 manual.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF"))
            ->set('modelId', (string) $model->id)
            ->call('draft')
            ->assertHasNoErrors()
            ->assertSet('failure', null)
            ->assertSee('Как заменить рулон')
            ->assertDontSee('Стоимость');

        $article = KnowledgeArticle::sole();
        $this->assertFalse($article->is_published, 'drafts are never published automatically');
        $this->assertSame('pdf', $article->source);
        $this->assertSame('RLS1515 manual.pdf', $article->source_ref);
        $this->assertSame($model->id, $article->scale_model_id);
        $this->assertSame($admin->id, $article->created_by);
        $this->assertSame(['рулон', 'принтер'], $article->tags);
        $this->assertSame('Rulonni almashtirish', $article->title['uz']);

        // The request: the PDF as a base64 document block, structured JSON output, no forced tool.
        $request = $claude->requests[0];
        $this->assertSame('claude-sonnet-5-5', $request['model']);
        $document = $request['messages'][0]['content'][0];
        $this->assertSame('document', $document['type']);
        $this->assertSame('application/pdf', $document['source']['mediaType']);
        $this->assertStringStartsWith('%PDF', base64_decode($document['source']['data']));
        $this->assertStringContainsString($model->name, $request['messages'][0]['content'][1]['text']);
        $this->assertSame('json_schema', $request['outputConfig']['format']['type']);
        $this->assertSame('default', $request['fallbacks']);
        $this->assertArrayNotHasKey('toolChoice', $request);
    }

    public function test_manual_import_explains_failures(): void
    {
        $this->actingAs($this->staff());
        $pdf = fn () => UploadedFile::fake()->createWithContent('m.pdf', "%PDF-1.4\n%%EOF");

        FakeClaude::bind(configured: false);
        Livewire::test(Articles\Import::class)
            ->assertSee('Ключ Claude API не задан')
            ->set('pdf', $pdf())->call('draft')
            ->assertSet('failure', 'Ключ Claude API не задан. Добавьте ANTHROPIC_API_KEY в файл .env.');

        FakeClaude::bind()->fail('rate_limit');
        Livewire::test(Articles\Import::class)->set('pdf', $pdf())->call('draft')->assertSee('Не удалось обратиться к Claude');

        FakeClaude::bind()->text('{"articles":[]}');
        Livewire::test(Articles\Import::class)->set('pdf', $pdf())->call('draft')->assertSee('не получилось ни одной статьи');

        FakeClaude::bind();
        Livewire::test(Articles\Import::class)
            ->set('pdf', UploadedFile::fake()->createWithContent('notes.txt', 'hello'))
            ->call('draft')
            ->assertHasErrors(['pdf']);
        Livewire::test(Articles\Import::class)->call('draft')->assertHasErrors(['pdf']);

        $this->assertSame(0, KnowledgeArticle::count());
    }

    public function test_conversations_list_and_detail_show_the_lead(): void
    {
        $conversation = $this->passGate('uz');
        $question = $conversation->messages()->create(['role' => 'user', 'content' => 'Kafolat qancha?', 'is_unanswered' => true, 'unanswered_reason' => 'not_in_knowledge']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Bilmayman.', 'status' => 'unanswered', 'model' => 'claude-sonnet-5-5', 'input_tokens' => 1234, 'output_tokens' => 56]);
        $conversation->update(['unanswered_count' => 1]);
        $quiet = $this->passGate('ru');
        $quiet->lead->update(['first_name' => 'Timur', 'last_name' => 'Aliyev', 'phone' => '+998935554433']);
        $this->actingAs($this->staff('admin'));

        Livewire::test(Conversations\Index::class)
            ->assertSee('Karimov')->assertSee('+998901234567')->assertSee('Timur')
            ->set('filterOnly', 'unanswered')
            ->assertSee('Karimov')->assertDontSee('Timur')
            ->set('filterOnly', '')
            ->set('search', '5554433')
            ->assertSee('Timur')->assertDontSee('Karimov');

        Livewire::test(Conversations\Show::class, ['conversation' => $conversation])
            ->assertSee('Aziz')->assertSee('+998901234567')
            ->assertSee('Kafolat qancha?')->assertSee('Bilmayman.')
            ->assertSee('не смог ответить')->assertSee('1 234')
            ->assertSee('Сделать статьёй');

        Livewire::test(Conversations\Unanswered::class)
            ->assertSee('Kafolat qancha?')->assertSee('нет в базе ответов')->assertSee('Karimov');

        $this->assertSame([$question->id], ChatMessage::openQuestions()->pluck('id')->all());
    }

    public function test_unanswered_question_becomes_a_draft_article_in_one_click(): void
    {
        $conversation = $this->passGate('uz');
        $question = $conversation->messages()->create(['role' => 'user', 'content' => "Kafolat\n qancha?", 'is_unanswered' => true]);
        $other = $conversation->messages()->create(['role' => 'user', 'content' => 'spam', 'is_unanswered' => true]);
        $conversation->update(['unanswered_count' => 2]);
        $admin = $this->staff('admin');
        $this->actingAs($admin);

        $article = null;
        Livewire::test(Conversations\Unanswered::class)
            ->call('makeArticle', $question->id)
            ->assertRedirect(route('admin.chatbot.articles.edit', $article = KnowledgeArticle::sole()));

        $this->assertFalse($article->is_published);
        $this->assertSame('conversation', $article->source);
        $this->assertSame(['ru' => '', 'uz' => 'Kafolat qancha?', 'en' => ''], $article->title);
        $this->assertSame($admin->id, $article->created_by);
        $this->assertSame($article->id, $question->fresh()->knowledge_article_id);

        // A second click opens the same draft; the question leaves the open list.
        Livewire::test(Conversations\Show::class, ['conversation' => $conversation])->call('makeArticle', $question->id);
        $this->assertSame(1, KnowledgeArticle::count());

        Livewire::test(Conversations\Unanswered::class)
            ->assertDontSee('Kafolat')
            ->call('dismissQuestion', $other->id)
            ->assertDontSee('spam');
        $this->assertSame(0, ChatMessage::openQuestions()->count());
        $this->assertSame(0, $conversation->fresh()->unanswered_count);

        $this->get(route('admin.chatbot.articles.edit', $article))->assertOk()->assertSee('Kafolat qancha?');
    }
}
