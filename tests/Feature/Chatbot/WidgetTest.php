<?php

namespace Tests\Feature\Chatbot;

use App\Livewire\Chat\Widget;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Lead;
use App\Services\Chatbot\ChatbotSettings;
use Livewire\Livewire;

class WidgetTest extends ChatbotTestCase
{
    public function test_button_and_gate_are_rendered_in_the_site_language(): void
    {
        app()->setLocale('uz');

        Livewire::test(Widget::class)
            ->assertSee(ui('chat.open', [], 'uz'))
            ->assertSee(ui('chat.gate.submit', [], 'uz'))
            ->assertSee('Rongta Uzbekistan yordamchisiman')
            ->assertDontSee('demo')
            ->assertDontSee('Prototip');
    }

    public function test_widget_is_hidden_when_switched_off(): void
    {
        ChatbotSettings::save(['enabled' => false]);

        Livewire::test(Widget::class)->assertDontSee(ui('chat.open'));
    }

    public function test_gate_rejects_bad_names_and_phones(): void
    {
        FakeClaude::bind();

        Livewire::test(Widget::class)
            ->set('first', 'A')
            ->set('last', 'Karimov2')
            ->set('phone', '+7 901 123 45 67')
            ->call('startChat')
            ->assertHasErrors(['first', 'last', 'phone'])
            ->assertSee(ui('err.phone'));

        foreach (['998901234567', '+99890123456', '+9989012345678', '901234567'] as $phone) {
            Livewire::test(Widget::class)
                ->set('first', 'Aziz')->set('last', 'Karimov')->set('phone', $phone)
                ->call('startChat')
                ->assertHasErrors(['phone']);
        }

        $this->assertSame(0, Lead::count());
        $this->assertSame(0, ChatConversation::count());
    }

    public function test_gate_saves_a_lead_and_ties_the_conversation_to_it(): void
    {
        app()->setLocale('uz');

        Livewire::test(Widget::class)
            ->set('first', ' Oʻgʻiloy ')
            ->set('last', 'Abdulla-yeva')
            ->set('phone', '+998 (90) 123-45-67')
            ->call('startChat')
            ->assertHasNoErrors()
            ->assertSee('Rahmat, Oʻgʻiloy!')
            ->assertDontSee(ui('chat.gate.submit', [], 'uz'));

        $lead = Lead::sole();
        $this->assertSame('Oʻgʻiloy', $lead->first_name);
        $this->assertSame('Abdulla-yeva', $lead->last_name);
        $this->assertSame('+998901234567', $lead->phone);
        $this->assertSame('chatbot', $lead->source);
        $this->assertSame('uz', $lead->locale);
        $this->assertNotEmpty($lead->page_url);

        $conversation = ChatConversation::sole();
        $this->assertSame($lead->id, $conversation->lead_id);
        $this->assertSame('uz', $conversation->locale);
        $this->assertSame($lead->id, session(Widget::SESSION_LEAD));
    }

    public function test_gate_is_asked_once_per_session(): void
    {
        $this->passGate();

        Livewire::test(Widget::class)
            ->assertDontSee(ui('chat.gate.submit'))
            ->assertSee('Aziz')
            ->call('startChat');

        $this->assertSame(1, Lead::count());
    }

    public function test_nothing_is_answered_before_the_gate(): void
    {
        $claude = FakeClaude::bind();

        Livewire::test(Widget::class)
            ->set('text', 'Какие весы выбрать?')
            ->call('send')
            ->call('answer')
            ->call('askChip', 'choose');

        $this->assertSame([], $claude->requests);
        $this->assertSame(0, ChatMessage::count());
    }

    public function test_question_is_saved_then_answered_with_usage_logged(): void
    {
        $conversation = $this->passGate();
        $claude = FakeClaude::bind()->text('RLS1000 печатает **этикетки**.', in: 321, out: 45, cacheRead: 9000, cacheWrite: 12);

        Livewire::test(Widget::class)
            ->set('text', '  Печатает ли RLS1000 этикетки?  ')
            ->call('send')
            ->assertSet('pending', true)
            ->assertSet('text', '')
            ->assertSee('Печатает ли RLS1000 этикетки?')
            ->call('answer')
            ->assertSet('pending', false)
            ->assertSeeHtml('<b>этикетки</b>');

        $this->assertCount(1, $claude->requests);
        [$question, $answer] = $conversation->messages()->get()->all();
        $this->assertSame('user', $question->role);
        $this->assertSame('Печатает ли RLS1000 этикетки?', $question->content);
        $this->assertFalse($question->is_unanswered);
        $this->assertSame('assistant', $answer->role);
        $this->assertSame('answered', $answer->status);
        $this->assertSame([321, 45, 9000, 12], [$answer->input_tokens, $answer->output_tokens, $answer->cache_read_tokens, $answer->cache_write_tokens]);
        $this->assertSame('claude-sonnet-5-5', $answer->model);
    }

    public function test_chip_sends_its_label_as_the_question(): void
    {
        $conversation = $this->passGate();
        FakeClaude::bind();

        Livewire::test(Widget::class)->call('askChip', 'dealers')->assertSet('pending', true);
        Livewire::test(Widget::class)->call('askChip', 'price');

        $this->assertSame([ui('chat.chip.dealers')], $conversation->messages()->pluck('content')->all());
    }

    public function test_message_length_limit(): void
    {
        $conversation = $this->passGate();
        $claude = FakeClaude::bind();
        ChatbotSettings::save(['max_message_chars' => 20]);

        Livewire::test(Widget::class)
            ->set('text', str_repeat('я', 21))
            ->call('send')
            ->assertSet('pending', false)
            ->assertSee(ui('bot.err.long', ['max' => 20]));

        $this->assertSame(0, $conversation->messages()->count());
        $this->assertSame([], $claude->requests);
    }

    public function test_daily_limit_per_lead(): void
    {
        $conversation = $this->passGate();
        $claude = FakeClaude::bind();
        ChatbotSettings::save(['lead_daily_limit' => 2, 'ip_daily_limit' => 100]);

        foreach (['один', 'два'] as $text) {
            Livewire::test(Widget::class)->set('text', $text)->call('send')->call('answer');
        }
        Livewire::test(Widget::class)
            ->set('text', 'три')
            ->call('send')
            ->assertSet('pending', false)
            ->assertSee(ui('bot.err.limit'));

        $this->assertSame(2, $conversation->messages()->where('role', 'user')->count());
        $this->assertCount(2, $claude->requests);
    }

    public function test_daily_limit_per_ip_spans_leads_and_resets_next_day(): void
    {
        ChatbotSettings::save(['lead_daily_limit' => 100, 'ip_daily_limit' => 2]);
        FakeClaude::bind();

        // Two messages from another lead on the same IP address today, one from yesterday.
        $other = $this->passGate();
        $other->messages()->create(['role' => 'user', 'content' => 'a']);
        $other->messages()->create(['role' => 'user', 'content' => 'b']);

        $mine = $this->passGate();
        Livewire::test(Widget::class)->set('text', 'c')->call('send')->assertSee(ui('bot.err.limit'));
        $this->assertSame(0, $mine->messages()->count());

        ChatMessage::query()->update(['created_at' => now()->subDay()]);
        Livewire::test(Widget::class)->set('text', 'c')->call('send')->assertSet('pending', true);
        $this->assertSame(1, $mine->messages()->count());
    }

    public function test_missing_api_key_shows_a_polite_message_not_an_error(): void
    {
        $conversation = $this->passGate();
        FakeClaude::bind(configured: false);

        Livewire::test(Widget::class)
            ->set('text', 'Как подключить весы к 1С?')
            ->call('send')
            ->call('answer')
            ->assertOk()
            ->assertSee(ui('bot.unavailable'))
            ->assertSee(ui('bot.dealers.link'));

        $question = $conversation->messages()->where('role', 'user')->sole();
        $answer = $conversation->messages()->where('role', 'assistant')->sole();
        $this->assertSame('unavailable', $answer->status);
        $this->assertFalse($question->is_unanswered, 'an outage is not a knowledge gap');
    }

    public function test_real_client_without_a_key_makes_no_call_and_falls_back(): void
    {
        config(['services.anthropic.key' => null]);
        $conversation = $this->passGate('en');
        app()->setLocale('en');

        Livewire::test(Widget::class)
            ->set('text', 'Does the C1 print labels?')
            ->call('send')
            ->call('answer')
            ->assertOk()
            ->assertSee(ui('bot.unavailable', [], 'en'));

        $this->assertSame('unavailable', $conversation->messages()->where('role', 'assistant')->sole()->status);
    }

    public function test_api_failure_shows_the_same_polite_message(): void
    {
        $conversation = $this->passGate();
        FakeClaude::bind()->fail('rate_limit');

        Livewire::test(Widget::class)->set('text', 'Вопрос')->call('send')->call('answer')
            ->assertSee(ui('bot.unavailable'));

        $this->assertSame('unavailable', $conversation->messages()->where('role', 'assistant')->sole()->status);
    }

    public function test_reply_is_escaped_and_only_site_links_become_links(): void
    {
        $this->passGate();
        FakeClaude::bind()->text("<script>alert(1)</script> [Дилеры](/dealers) [x](https://evil.example) [y](//evil.example)\n- один\n- два");

        Livewire::test(Widget::class)->set('text', 'Вопрос')->call('send')->call('answer')
            ->assertDontSeeHtml('<script>alert(1)</script>')
            ->assertSeeHtml('<a href="/dealers">Дилеры</a>')
            ->assertDontSeeHtml('href="https://evil.example"')
            ->assertDontSeeHtml('href="//evil.example"')
            ->assertSeeHtml('<ul><li>один</li><li>два</li></ul>');
    }
}
