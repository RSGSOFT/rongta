<?php

namespace Tests\Feature\Chatbot;

use App\Services\Chatbot\Claude\ClaudeClient;
use App\Services\Chatbot\Claude\ClaudeReply;
use App\Services\Chatbot\Claude\ClaudeUnavailable;

/** Scripted stand-in for the Claude API. Records every request; never touches the network. */
class FakeClaude implements ClaudeClient
{
    /** @var list<array<string, mixed>> */
    public array $requests = [];

    /** @var list<ClaudeReply|ClaudeUnavailable> */
    private array $queue = [];

    /** Characters per streamed piece; 0 hands the whole text over in one piece. */
    public int $chunk = 0;

    public function __construct(public bool $configured = true) {}

    public static function bind(bool $configured = true): self
    {
        $fake = new self($configured);
        app()->instance(ClaudeClient::class, $fake);

        return $fake;
    }

    public function text(string $text, int $in = 100, int $out = 20, int $cacheRead = 0, int $cacheWrite = 0): self
    {
        $this->queue[] = new ClaudeReply(
            text: $text,
            content: [['type' => 'text', 'text' => $text]],
            inputTokens: $in,
            outputTokens: $out,
            cacheReadTokens: $cacheRead,
            cacheWriteTokens: $cacheWrite,
            model: 'claude-sonnet-5-5',
        );

        return $this;
    }

    /** @param array<string, mixed> $input */
    public function tool(string $name, array $input, string $id = 'toolu_1', string $text = ''): self
    {
        $this->queue[] = new ClaudeReply(
            text: $text,
            toolCalls: [['id' => $id, 'name' => $name, 'input' => $input]],
            content: [['type' => 'tool_use', 'id' => $id, 'name' => $name, 'input' => $input]],
            stopReason: 'tool_use',
            inputTokens: 50,
            outputTokens: 10,
            model: 'claude-sonnet-5-5',
        );

        return $this;
    }

    public function refusal(string $category = 'general_harms'): self
    {
        $this->queue[] = new ClaudeReply(stopReason: 'refusal', refusalCategory: $category, model: 'claude-sonnet-5-5');

        return $this;
    }

    public function fail(string $reason = 'api_error'): self
    {
        $this->queue[] = new ClaudeUnavailable($reason);

        return $this;
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function send(array $request, ?callable $onText = null): ClaudeReply
    {
        if (! $this->configured) {
            throw new ClaudeUnavailable('missing_key');
        }

        $this->requests[] = $request;
        $next = array_shift($this->queue) ?? new ClaudeReply(text: 'ok', content: [['type' => 'text', 'text' => 'ok']]);
        if ($next instanceof ClaudeUnavailable) {
            throw $next;
        }
        if ($onText && $next->text !== '') {
            foreach ($this->chunk > 0 ? mb_str_split($next->text, $this->chunk) : [$next->text] as $piece) {
                $onText($piece);
            }
        }

        return $next;
    }

    /** tool_result contents of the last request, decoded. */
    public function lastToolResults(): array
    {
        $last = end($this->requests)['messages'] ?? [];
        $results = end($last)['content'] ?? [];

        return array_map(fn (array $r) => json_decode($r['content'], true) ?? $r['content'], is_array($results) ? $results : []);
    }
}
