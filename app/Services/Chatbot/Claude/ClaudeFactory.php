<?php

namespace App\Services\Chatbot\Claude;

/** Returns the bound ClaudeClient (tests bind a fake) or the real SDK client. */
class ClaudeFactory
{
    public static function make(): ClaudeClient
    {
        if (app()->bound(ClaudeClient::class)) {
            return app(ClaudeClient::class);
        }

        return new AnthropicClaudeClient(config('services.anthropic.key'));
    }

    public static function model(): string
    {
        return (string) config('services.anthropic.model', 'claude-sonnet-5-5');
    }

    /**
     * Refusal handling shared by every chatbot request: on a safety decline the API
     * retries on the model's server-defined fallback inside the same call.
     *
     * @return array<string, mixed>
     */
    public static function fallbackOptions(): array
    {
        return [
            'betas' => ['server-side-fallback-2026-07-01'],
            'fallbacks' => 'default',
        ];
    }
}
