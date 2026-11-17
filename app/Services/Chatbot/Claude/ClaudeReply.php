<?php

namespace App\Services\Chatbot\Claude;

/** What came back from one Messages API call, reduced to what the chatbot uses. */
final class ClaudeReply
{
    /**
     * @param  list<array{id: string, name: string, input: array<string, mixed>}>  $toolCalls
     * @param  mixed  $content  the response content blocks exactly as received, to pass back
     *                          unchanged in the next request of a tool loop
     */
    public function __construct(
        public readonly string $text = '',
        public readonly array $toolCalls = [],
        public readonly mixed $content = [],
        public readonly ?string $stopReason = 'end_turn',
        public readonly ?string $refusalCategory = null,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $cacheReadTokens = 0,
        public readonly int $cacheWriteTokens = 0,
        public readonly ?string $model = null,
    ) {}
}
