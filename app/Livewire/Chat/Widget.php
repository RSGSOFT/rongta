<?php

namespace App\Livewire\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Lead;
use App\Services\Chatbot\ChatAssistant;
use App\Services\Chatbot\ChatbotSettings;
use App\Services\Chatbot\ChatFormatter;
use App\Services\Chatbot\ChatLimits;
use App\Support\Locales;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The "R" chatbot on every public page.
 *
 * Gate first: first name, surname and Uzbek mobile number are saved as a Lead before any
 * answer. The lead and its conversation are remembered in the session, so the gate is asked
 * once per browser session. Answers come from ChatAssistant (Claude, approved knowledge only).
 */
class Widget extends Component
{
    public const SESSION_LEAD = 'chat.lead_id';

    public const SESSION_CONVERSATION = 'chat.conversation_id';

    /** Quick questions under the greeting; the label is sent as the question. */
    public const CHIPS = ['choose', 'dealers', 'docs', 'warranty', 'pos', 'partner', 'human'];

    /** Seconds a second answer() call waits for the one already running. */
    public const ANSWER_LOCK_WAIT = 150;

    /**
     * Test hook: when set, every frame that would be streamed to the browser is passed here
     * instead (a test run has no response to stream into).
     *
     * @var (Closure(string): void)|null
     */
    public static ?Closure $recordFrames = null;

    private const NAME_RULE = '/^\p{L}[\p{L}ʻʼ\'’\- ]*$/u';

    #[Locked]
    public string $locale = Locales::DEFAULT;

    #[Locked]
    public string $pageUrl = '';

    public string $first = '';

    public string $last = '';

    public string $phone = '';

    public string $text = '';

    /** A question is saved and its answer is being prepared. */
    public bool $pending = false;

    /** Limit or length message shown above the input. */
    public ?string $notice = null;

    public function mount(): void
    {
        $this->locale = app()->getLocale();
        $this->pageUrl = mb_substr(request()->fullUrl(), 0, 2048);
    }

    /** Livewire update requests do not pass the page's locale middleware. */
    public function hydrate(): void
    {
        app()->setLocale($this->locale);
    }

    public function startChat(): void
    {
        if (! ChatbotSettings::enabled() || $this->lead()) {
            return;
        }

        $this->resetErrorBag();
        $first = trim($this->first);
        $last = trim($this->last);
        $phone = preg_replace('/[\s\-()]/', '', $this->phone) ?? '';

        if (! $this->validName($first)) {
            $this->addError('first', ui('err.first'));
        }
        if (! $this->validName($last)) {
            $this->addError('last', ui('err.last'));
        }
        if (! preg_match('/^\+998\d{9}$/', $phone)) {
            $this->addError('phone', ui('err.phone'));
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('chat-focus', field: $this->getErrorBag()->keys()[0]);

            return;
        }

        $rateKey = 'chat-gate:'.request()->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            $this->addError('phone', ui('bot.err.gate'));

            return;
        }
        RateLimiter::hit($rateKey, 3600);

        $lead = Lead::create([
            'first_name' => mb_substr($first, 0, 100),
            'last_name' => mb_substr($last, 0, 100),
            'phone' => $phone,
            'locale' => $this->locale,
            'source' => 'chatbot',
            'page_url' => $this->pageUrl,
        ]);
        $conversation = ChatConversation::create([
            'lead_id' => $lead->id,
            'locale' => $this->locale,
            'page_url' => $this->pageUrl,
            'ip' => request()->ip(),
        ]);

        session([self::SESSION_LEAD => $lead->id, self::SESSION_CONVERSATION => $conversation->id]);
        $this->reset('first', 'last', 'phone');
        $this->dispatch('chat-focus', field: 'input');
    }

    public function askChip(string $chip): void
    {
        if (in_array($chip, self::CHIPS, true)) {
            $this->text = ui('chat.chip.'.$chip);
            $this->send();
        }
    }

    /** Step 1: save the question and show it. The answer is fetched by a second request. */
    public function send(): void
    {
        $text = trim($this->text);
        $conversation = $this->conversation();
        if (! ChatbotSettings::enabled() || ! $conversation || $this->pending || $text === '') {
            return;
        }

        $this->notice = null;
        if ($error = app(ChatLimits::class)->check($conversation, $text)) {
            $this->notice = ui($error, ['max' => ChatbotSettings::int('max_message_chars')]);

            return;
        }

        // The visitor now reads this language; later answers follow it.
        if ($conversation->locale !== $this->locale) {
            $conversation->update(['locale' => $this->locale]);
        }
        $conversation->messages()->create(['role' => ChatMessage::ROLE_USER, 'content' => $text]);
        $conversation->update(['last_message_at' => now()]);

        $this->text = '';
        $this->pending = true;
        $this->js('$wire.answer()');
    }

    /**
     * Step 2: ask Claude, streaming the reply into the panel as it is written.
     *
     * One question is one API call: the conversation is locked while its answer is prepared.
     * A second call for the same question waits, then finds the answer already stored.
     */
    public function answer(ChatAssistant $assistant): void
    {
        $conversation = $this->conversation();
        if (! $this->pending || ! $conversation) {
            $this->pending = false;

            return;
        }

        @set_time_limit(self::ANSWER_LOCK_WAIT + 180);
        $lock = Cache::lock('chat-answer:'.$conversation->id, 300);

        try {
            $lock->block((int) config('services.anthropic.answer_lock_wait', self::ANSWER_LOCK_WAIT));
        } catch (LockTimeoutException) {
            $this->pending = false;

            return;
        }

        try {
            // Read under the lock: a parallel call may have answered in the meantime.
            $question = $conversation->messages()->reorder('id', 'desc')->first();
            if ($question && $question->role === ChatMessage::ROLE_USER) {
                $assistant->reply($conversation, $question, $this->frameSink());
            }
        } finally {
            $lock->release();
            $this->pending = false;
        }
        $this->dispatch('chat-focus', field: 'input');
    }

    /**
     * Where the reply goes while it is being written. ChatAssistant passes only text that
     * has cleared the price guard; an empty text means "nothing to show yet" (or taken back).
     *
     * @return (Closure(string): void)|null
     */
    private function frameSink(): ?Closure
    {
        $typing = '<span class="rc-typing"><i></i><i></i><i></i></span>';
        $html = fn (string $soFar): string => $soFar === '' ? $typing : ChatFormatter::html($soFar);

        if (self::$recordFrames !== null) {
            return fn (string $soFar) => (self::$recordFrames)($html($soFar));
        }
        // Livewire streaming writes straight to the response, which a test run has none of.
        if (app()->runningUnitTests()) {
            return null;
        }

        return function (string $soFar) use ($html) {
            $this->stream(content: $html($soFar), replace: true, name: 'chat-answer');
        };
    }

    public function render(): View
    {
        $lead = $this->lead();

        return view('livewire.chat.widget', [
            'enabled' => ChatbotSettings::enabled(),
            'lead' => $lead,
            'messages' => $lead ? $this->messages() : collect(),
            'chips' => self::CHIPS,
            'maxChars' => ChatbotSettings::int('max_message_chars'),
        ]);
    }

    private function validName(string $name): bool
    {
        return (bool) preg_match(self::NAME_RULE, $name) && preg_match_all('/\p{L}/u', $name) >= 2;
    }

    private function lead(): ?Lead
    {
        $id = session(self::SESSION_LEAD);

        return $id ? Lead::find($id) : null;
    }

    private function conversation(): ?ChatConversation
    {
        $lead = $this->lead();
        $id = session(self::SESSION_CONVERSATION);

        return $lead && $id ? ChatConversation::where('lead_id', $lead->id)->find($id) : null;
    }

    /** @return Collection<int, ChatMessage> */
    private function messages(): Collection
    {
        $conversation = $this->conversation();

        return $conversation
            ? $conversation->messages()->reorder('id', 'desc')->limit(60)->get()->reverse()->values()
            : collect();
    }
}
