<?php

namespace App\Services\Chatbot;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chatbot\Claude\ClaudeClient;
use App\Services\Chatbot\Claude\ClaudeFactory;
use App\Services\Chatbot\Claude\ClaudeUnavailable;
use App\Support\UzbekText;

/**
 * Answers one visitor message with Claude and stores the reply.
 *
 * Whatever goes wrong (no API key, API error, safety refusal, a reply that names a price),
 * the visitor gets a polite text pointing to a dealer or RSG, never an error.
 */
class ChatAssistant
{
    /** A reply needs at most: one round of lookups, one follow-up, then the answer. */
    private const MAX_ROUNDS = 4;

    private ClaudeClient $claude;

    public function __construct(
        private readonly PromptBuilder $prompts,
        private readonly KnowledgeTools $tools,
        ?ClaudeClient $claude = null,
    ) {
        $this->claude = $claude ?? ClaudeFactory::make();
    }

    /**
     * The request for the next Claude call. Public so the exact shape can be tested and shown.
     *
     * @param  list<array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    public function request(string $locale, array $messages): array
    {
        return [
            'model' => ClaudeFactory::model(),
            'maxTokens' => ChatbotSettings::int('max_output_tokens', 500),
            // Stable prefix (tools render first, then system): one cache breakpoint at its end.
            'system' => [[
                'type' => 'text',
                'text' => $this->prompts->system($locale),
                'cacheControl' => ['type' => 'ephemeral'],
            ]],
            'tools' => $this->tools->definitions(),
            'messages' => $messages,
            'thinking' => ['type' => 'adaptive'],
            'outputConfig' => ['effort' => ChatbotSettings::get('effort')],
            ...ClaudeFactory::fallbackOptions(),
        ];
    }

    /**
     * @param  (callable(string): void)|null  $onText  receives the reply text that may be displayed
     *                                                 so far (already price-checked, tail held back)
     */
    public function reply(ChatConversation $conversation, ChatMessage $question, ?callable $onText = null): ChatMessage
    {
        $locale = $conversation->locale;
        $messages = $this->history($conversation, $question);

        $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'cache_write_tokens' => 0];
        $calls = [];
        $model = null;
        $text = '';
        $status = 'answered';
        $reason = null;

        // Live display goes through the price guard: text it would block is never shown.
        // Uzbek replies get the site's oʻ/gʻ apostrophes, live and stored alike.
        $tidy = $locale === 'uz' ? UzbekText::normalize(...) : static fn (string $t): string => $t;
        $stream = $onText === null ? null : new GuardedStream(fn (string $shown) => $onText($tidy($shown)));

        try {
            for ($round = 1; $round <= self::MAX_ROUNDS; $round++) {
                $stream?->newRound();
                $reply = $this->claude->send(
                    $this->request($locale, $messages),
                    $stream === null ? null : $stream->push(...),
                );

                $model = $reply->model ?? $model;
                $usage['input_tokens'] += $reply->inputTokens;
                $usage['output_tokens'] += $reply->outputTokens;
                $usage['cache_read_tokens'] += $reply->cacheReadTokens;
                $usage['cache_write_tokens'] += $reply->cacheWriteTokens;

                // Check the stop reason before reading any content.
                if ($reply->stopReason === 'refusal') {
                    [$status, $reason, $text] = ['refused', 'refusal:'.($reply->refusalCategory ?? 'unknown'), ''];
                    break;
                }

                $text = trim($reply->text);

                // Also the text written before a tool call: stop here, no further rounds.
                if ($stream?->blocked() || PriceGuard::hasAmount($text)) {
                    [$status, $reason, $text] = ['blocked', 'price_in_reply', ''];
                    break;
                }

                if ($reply->stopReason !== 'tool_use' || $reply->toolCalls === []) {
                    break;
                }
                if ($round === self::MAX_ROUNDS) {
                    [$status, $reason, $text] = ['unanswered', 'tool_loop', ''];
                    break;
                }

                // Pass the assistant turn back exactly as received (thinking blocks included),
                // then all tool results together in one user message.
                $results = [];
                foreach ($reply->toolCalls as $call) {
                    $calls[] = ['name' => $call['name'], 'input' => $call['input']];
                    if (strtolower($call['name']) === 'report_unanswered') {
                        $status = 'unanswered';
                        $reason = (string) ($call['input']['reason'] ?? 'other');
                    }
                    $result = $this->tools->run($call['name'], $call['input'], $locale);
                    $results[] = [
                        'type' => 'tool_result',
                        'toolUseID' => $call['id'],
                        'content' => $result['content'],
                        'isError' => $result['is_error'],
                    ];
                }
                $messages[] = ['role' => 'assistant', 'content' => $reply->content];
                $messages[] = ['role' => 'user', 'content' => $results];
            }
        } catch (ClaudeUnavailable $e) {
            [$status, $reason, $text] = ['unavailable', $e->reason, ui('bot.unavailable', [], $locale)];
        }

        // Last line of defence for the "no prices" rule: a reply with a money amount is not shown.
        if ($status !== 'unavailable' && PriceGuard::hasAmount($text)) {
            [$status, $reason, $text] = ['blocked', 'price_in_reply', ''];
        }
        if ($text === '') {
            $text = ChatbotSettings::fallback($locale);
            $status = $status === 'answered' ? 'unanswered' : $status;
            $reason ??= 'empty_reply';
        }

        $answer = $conversation->messages()->create([
            'role' => ChatMessage::ROLE_ASSISTANT,
            'content' => $tidy($text),
            'status' => $status,
            'model' => $model,
            'tool_calls' => $calls === [] ? null : $calls,
            ...$usage,
        ]);

        // "unavailable" is our outage, not a gap in the knowledge, so it is not flagged for staff.
        if (in_array($status, ['unanswered', 'refused', 'blocked'], true)) {
            $question->update(['is_unanswered' => true, 'unanswered_reason' => mb_substr((string) $reason, 0, 40)]);
            $conversation->increment('unanswered_count');
        }
        $conversation->update(['last_message_at' => now()]);

        return $answer;
    }

    /**
     * The last few messages as plain text turns, ending with the new question. Thinking and
     * tool blocks of earlier turns are not stored or replayed, so trimming old turns here
     * never edits a turn that carries a thinking block.
     *
     * @return list<array<string, mixed>>
     */
    private function history(ChatConversation $conversation, ChatMessage $question): array
    {
        $earlier = $conversation->messages()
            ->where('id', '<', $question->id)
            ->reorder('id', 'desc')
            ->limit(ChatbotSettings::int('history_messages', 0))
            ->get()->reverse()->values();

        // The Messages API needs the first turn to be the visitor's.
        while ($earlier->isNotEmpty() && $earlier->first()->role !== ChatMessage::ROLE_USER) {
            $earlier->shift();
        }

        $messages = $earlier->map(fn (ChatMessage $m) => ['role' => $m->role, 'content' => $m->content])->all();
        $messages[] = ['role' => 'user', 'content' => $question->content];

        return array_values($messages);
    }
}
