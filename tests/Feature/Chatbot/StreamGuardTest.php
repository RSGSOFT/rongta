<?php

namespace Tests\Feature\Chatbot;

use App\Livewire\Chat\Widget;
use App\Models\ChatMessage;
use App\Services\Chatbot\ChatbotSettings;
use App\Services\Chatbot\GuardedStream;
use App\Services\Chatbot\PriceGuard;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;

/** What the visitor sees while a reply is being written, and one question = one API call. */
class StreamGuardTest extends ChatbotTestCase
{
    private const INTRO = 'Весы RLS1000 печатают этикетки со штрихкодом, подключаются по Ethernet и работают с кассовыми программами. '
        .'Модель подходит для магазина у дома и для отдела в супермаркете, где товар взвешивают при покупателе. ';

    /** @var list<string> */
    private array $frames = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->frames = [];
        Widget::$recordFrames = function (string $html) {
            $this->frames[] = $html;
        };
    }

    protected function tearDown(): void
    {
        Widget::$recordFrames = null;
        parent::tearDown();
    }

    private function ask(): Testable
    {
        return Livewire::test(Widget::class)->set('text', 'Вопрос')->call('send')->call('answer');
    }

    private function framesText(): string
    {
        return html_entity_decode(strip_tags(implode("\n", $this->frames)));
    }

    public static function pricedReplies(): array
    {
        return [
            'currency' => ['Цена 5 500 000 сум.', '500'],
            'no currency' => ['Цена 5 500 000, уточните у дилера.', '500'],
            'reseller offer' => ['Партнёрам от 4 625 000 за штуку, 10 + 1.', '625'],
            'millions' => ['Это около 5.5 млн.', '5.5'],
            'words' => ['Примерно пять с половиной миллионов сум.', 'миллион'],
            'english' => ['The price is 5,500,000.', '500'],
            'uzbek' => ['Narxi 5 500 000.', '500'],
        ];
    }

    #[DataProvider('pricedReplies')]
    public function test_a_priced_reply_is_never_displayed_however_it_is_split(string $priced, string $marker): void
    {
        foreach ([1, 3, 7, 50] as $chunk) {
            $this->frames = [];
            $conversation = $this->passGate();
            $claude = FakeClaude::bind()->text(self::INTRO.$priced.' '.self::INTRO);
            $claude->chunk = $chunk;

            $page = $this->ask();

            $this->assertNotEmpty($this->frames, 'the safe start of the reply was streamed');
            foreach ($this->frames as $frame) {
                $shown = html_entity_decode(strip_tags($frame));
                $this->assertFalse(PriceGuard::hasAmount($shown), "chunk $chunk: $shown");
                $this->assertStringNotContainsString($marker, $shown, "chunk $chunk");
            }
            $this->assertStringContainsString('rc-typing', end($this->frames), 'what was displayed is taken back');

            $answer = $conversation->messages()->where('role', 'assistant')->sole();
            $this->assertSame('blocked', $answer->status);
            $this->assertSame(ChatbotSettings::fallback('ru'), $answer->content);
            $page->assertDontSee($marker)->assertSee(ChatbotSettings::fallback('ru'));
        }
    }

    public function test_an_amount_at_the_very_start_shows_nothing_at_all(): void
    {
        $this->passGate();
        $claude = FakeClaude::bind()->text('5 500 000 сум. '.self::INTRO.self::INTRO);
        $claude->chunk = 2;

        $this->ask();

        $this->assertSame('', trim($this->framesText()));
    }

    public function test_a_clean_reply_is_streamed_behind_a_held_back_tail(): void
    {
        $conversation = $this->passGate();
        $text = self::INTRO.'Память на 10 000 товаров, печать 90 мм/с, 203 dpi. '.self::INTRO;
        $claude = FakeClaude::bind()->text($text);
        $claude->chunk = 5;

        $this->ask()->assertSee('203 dpi');

        $this->assertGreaterThan(5, count($this->frames));
        $previous = '';
        foreach ($this->frames as $frame) {
            $shown = html_entity_decode(strip_tags($frame));
            $this->assertStringStartsWith($shown, $text);
            $this->assertGreaterThan(mb_strlen($previous), mb_strlen($shown));
            $previous = $shown;
        }
        $this->assertLessThanOrEqual(mb_strlen($text) - GuardedStream::HOLD_BACK, mb_strlen($previous));
        $this->assertSame('answered', $conversation->messages()->where('role', 'assistant')->sole()->status);
    }

    public function test_text_before_a_tool_call_is_guarded_and_ends_the_reply(): void
    {
        $conversation = $this->passGate();
        $claude = FakeClaude::bind()
            ->tool('find_dealers', ['region' => 'Tashkent'], text: self::INTRO.'Цена 5 500 000. Сейчас найду дилеров. '.self::INTRO)
            ->text('Дилеры в Ташкенте.');
        $claude->chunk = 4;

        $this->ask()->assertDontSee('5 500 000')->assertDontSee('Дилеры в Ташкенте.');

        $this->assertCount(1, $claude->requests, 'no further round after a blocked one');
        $this->assertStringNotContainsString('500', $this->framesText());
        $this->assertSame('blocked', $conversation->messages()->where('role', 'assistant')->sole()->status);
        $this->assertTrue($conversation->messages()->where('role', 'user')->sole()->is_unanswered);
    }

    public function test_text_before_a_tool_call_is_checked_without_streaming_too(): void
    {
        Widget::$recordFrames = null;
        $conversation = $this->passGate();
        $claude = FakeClaude::bind()
            ->tool('find_dealers', ['region' => 'Tashkent'], text: 'Цена 5 500 000.')
            ->text('Дилеры в Ташкенте.');

        $this->ask()->assertDontSee('5 500 000');

        $this->assertCount(1, $claude->requests);
        $this->assertSame('blocked', $conversation->messages()->where('role', 'assistant')->sole()->status);
    }

    public function test_tail_is_held_back_and_cut_at_a_word(): void
    {
        $this->assertSame('', GuardedStream::releasable(str_repeat('я', GuardedStream::HOLD_BACK)));
        $this->assertSame('один два', GuardedStream::releasable('один два три'.str_repeat('x', GuardedStream::HOLD_BACK - 2)));

        // "Цена 5" must not be released while " 500 000" may still follow.
        $shown = [];
        $stream = new GuardedStream(function (string $text) use (&$shown) {
            $shown[] = $text;
        });
        $stream->push(self::INTRO.self::INTRO.'Цена 5');
        $this->assertNotSame('', end($shown));
        $this->assertStringNotContainsString('Цена', implode('', $shown));
        $stream->push(' 500 000');
        $this->assertTrue($stream->blocked());
        $this->assertSame('', end($shown));
        $stream->push(' и ещё текст '.self::INTRO);
        $this->assertSame('', end($shown), 'a blocked stream stays closed');
    }

    public function test_a_second_answer_call_waits_and_makes_no_second_api_call(): void
    {
        $conversation = $this->passGate();
        $claude = FakeClaude::bind()->text('Первый ответ.')->text('Второй ответ.');

        $page = Livewire::test(Widget::class)->set('text', 'Вопрос')->call('send');

        // Another request is already answering this conversation.
        config(['services.anthropic.answer_lock_wait' => 0]);
        $lock = Cache::lock('chat-answer:'.$conversation->id, 300);
        $this->assertTrue($lock->get());
        $page->call('answer')->assertSet('pending', false);
        $this->assertSame([], $claude->requests);
        $this->assertSame(0, $conversation->messages()->where('role', ChatMessage::ROLE_ASSISTANT)->count());
        $lock->release();

        // The winner answers; a late duplicate finds the answer stored and calls nothing.
        $page->set('pending', true)->call('answer');
        $page->set('pending', true)->call('answer')->assertSet('pending', false)->assertSee('Первый ответ.');

        $this->assertCount(1, $claude->requests);
        $this->assertSame(1, $conversation->messages()->where('role', ChatMessage::ROLE_ASSISTANT)->count());
        $this->assertTrue(Cache::lock('chat-answer:'.$conversation->id, 5)->get(), 'the lock is released after answering');
    }
}
