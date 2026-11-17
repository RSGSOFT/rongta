<?php

namespace App\Services\Chatbot\Claude;

/**
 * The one door to the Claude API. The real implementation is AnthropicClaudeClient;
 * tests bind a fake, so no test ever makes a network call.
 */
interface ClaudeClient
{
    /** False while no API key is configured. */
    public function isConfigured(): bool;

    /**
     * Send one Messages API request.
     *
     * @param  array<string, mixed>  $request  named arguments of the SDK's beta messages call
     *                                         (model, maxTokens, system, tools, messages, ...)
     * @param  (callable(string): void)|null  $onText  when given, the request is streamed and
     *                                                 each piece of reply text is passed to it
     *
     * @throws ClaudeUnavailable when the key is missing or the API call fails
     */
    public function send(array $request, ?callable $onText = null): ClaudeReply;
}
