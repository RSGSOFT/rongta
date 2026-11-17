<?php

namespace App\Services\Chatbot\Claude;

use Anthropic\Beta\Messages\BetaMessage;
use Anthropic\Beta\Messages\BetaRawContentBlockDeltaEvent;
use Anthropic\Beta\Messages\BetaTextDelta;
use Anthropic\Client;
use Anthropic\Core\Exceptions\AnthropicException;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use Anthropic\Lib\Streaming\MessageAccumulator;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Claude through the official PHP SDK (anthropic-ai/sdk), on the beta Messages endpoint
 * because the server-side refusal fallback ("fallbacks") only exists there.
 */
class AnthropicClaudeClient implements ClaudeClient
{
    private ?Client $client = null;

    public function __construct(
        private readonly ?string $apiKey,
        private readonly float $timeout = 90.0,
        /** Extra SDK request options; tests pass a fake HTTP transporter here. */
        private readonly array $requestOptions = [],
    ) {}

    public function isConfigured(): bool
    {
        return is_string($this->apiKey) && trim($this->apiKey) !== '';
    }

    public function send(array $request, ?callable $onText = null): ClaudeReply
    {
        if (! $this->isConfigured()) {
            throw new ClaudeUnavailable('missing_key');
        }

        $request['requestOptions'] = [
            'timeout' => $this->timeout,
            'maxRetries' => 1,
            ...($request['requestOptions'] ?? []),
            ...$this->requestOptions,
        ];

        try {
            $message = $onText === null
                ? $this->client()->beta->messages->create(...$request)
                : $this->stream($request, $onText);
        } catch (AuthenticationException $e) {
            throw $this->unavailable('auth', $e);
        } catch (RateLimitException $e) {
            throw $this->unavailable('rate_limit', $e);
        } catch (APIStatusException $e) {
            throw $this->unavailable('api_error', $e);
        } catch (APIConnectionException $e) {
            throw $this->unavailable('connection', $e);
        } catch (AnthropicException|Throwable $e) {
            throw $this->unavailable('error', $e);
        }

        return $this->toReply($message);
    }

    /** @param array<string, mixed> $request */
    private function stream(array $request, callable $onText): BetaMessage
    {
        $accumulator = MessageAccumulator::forBetaMessages();

        foreach ($this->client()->beta->messages->createStream(...$request) as $event) {
            $accumulator->accumulate($event);
            if ($event instanceof BetaRawContentBlockDeltaEvent && $event->delta instanceof BetaTextDelta) {
                $onText($event->delta->text);
            }
        }

        $message = $accumulator->message();
        assert($message instanceof BetaMessage);

        return $message;
    }

    private function toReply(BetaMessage $message): ClaudeReply
    {
        $text = '';
        $toolCalls = [];
        // Read blocks by type, never by position: thinking blocks can come first.
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            } elseif ($block->type === 'tool_use') {
                $toolCalls[] = ['id' => $block->id, 'name' => $block->name, 'input' => (array) $block->input];
            }
        }

        return new ClaudeReply(
            text: $text,
            toolCalls: $toolCalls,
            content: $message->content,
            stopReason: $message->stopReason,
            refusalCategory: $message->stopReason === 'refusal' ? ($message->stopDetails?->category ?? 'unknown') : null,
            inputTokens: (int) $message->usage->inputTokens,
            outputTokens: (int) $message->usage->outputTokens,
            cacheReadTokens: (int) ($message->usage->cacheReadInputTokens ?? 0),
            cacheWriteTokens: (int) ($message->usage->cacheCreationInputTokens ?? 0),
            model: $message->model,
        );
    }

    private function client(): Client
    {
        return $this->client ??= new Client(apiKey: trim((string) $this->apiKey));
    }

    private function unavailable(string $reason, Throwable $e): ClaudeUnavailable
    {
        Log::warning('Chatbot: Claude API call failed', [
            'reason' => $reason,
            'exception' => $e::class,
            'status' => $e instanceof APIStatusException ? ($e->status ?? null) : null,
            'message' => $e->getMessage(),
        ]);

        return new ClaudeUnavailable($reason, $e->getMessage(), $e);
    }
}
