<?php

namespace Tests\Feature\Chatbot;

use App\Services\Chatbot\ChatAssistant;
use App\Services\Chatbot\Claude\AnthropicClaudeClient;
use App\Services\Chatbot\Claude\ClaudeUnavailable;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Runs the real SDK client against a fake HTTP transport (no network), to check that the
 * request really serialises to the wire shape the Messages API expects.
 */
class SdkClientTest extends ChatbotTestCase
{
    private function transport(int $status, array|string $body, string $type = 'application/json'): ClientInterface
    {
        return new class($status, $body, $type) implements ClientInterface
        {
            public ?RequestInterface $request = null;

            public function __construct(private int $status, private array|string $body, private string $type) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->request = $request;

                return new Response(
                    $this->status,
                    ['Content-Type' => $this->type],
                    is_array($this->body) ? json_encode($this->body) : $this->body,
                );
            }
        };
    }

    private function message(array $content, string $stop = 'end_turn'): array
    {
        return [
            'id' => 'msg_1', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-sonnet-5-5',
            'content' => $content, 'stop_reason' => $stop, 'stop_sequence' => null,
            'usage' => ['input_tokens' => 12, 'output_tokens' => 7, 'cache_read_input_tokens' => 9000, 'cache_creation_input_tokens' => 0],
        ];
    }

    private function request(string $locale, array $messages): array
    {
        return app(ChatAssistant::class)->request($locale, $messages);
    }

    public function test_request_goes_out_in_the_messages_api_wire_shape(): void
    {
        $http = $this->transport(200, $this->message([
            ['type' => 'thinking', 'thinking' => '', 'signature' => 'sig'],
            ['type' => 'text', 'text' => 'Да, печатает.'],
        ]));
        $client = new AnthropicClaudeClient('test-key', requestOptions: ['transporter' => $http]);

        $reply = $client->send($this->request('ru', [['role' => 'user', 'content' => 'Печатает ли C1 этикетки?']]));

        $this->assertSame('Да, печатает.', $reply->text);
        $this->assertSame('end_turn', $reply->stopReason);
        $this->assertSame([12, 7, 9000, 0], [$reply->inputTokens, $reply->outputTokens, $reply->cacheReadTokens, $reply->cacheWriteTokens]);

        $sent = $http->request;
        $this->assertStringEndsWith('/v1/messages', $sent->getUri()->getPath());
        $this->assertStringContainsString('test-key', $sent->getHeaderLine('Authorization'));
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $sent->getHeaderLine('anthropic-beta'));

        $body = json_decode((string) $sent->getBody(), true);
        $this->assertSame('claude-sonnet-5-5', $body['model']);
        $this->assertSame('default', $body['fallbacks']);
        $this->assertSame(['type' => 'adaptive'], $body['thinking']);
        $this->assertSame(['effort' => 'low'], $body['output_config']);
        $this->assertSame(['type' => 'ephemeral'], $body['system'][0]['cache_control']);
        $this->assertSame('find_dealers', $body['tools'][0]['name']);
        $this->assertTrue($body['tools'][0]['strict']);
        $this->assertArrayHasKey('input_schema', $body['tools'][0]);
        foreach (['betas', 'tool_choice', 'temperature', 'stream'] as $absent) {
            $this->assertArrayNotHasKey($absent, $body);
        }
    }

    public function test_tool_use_and_refusal_responses_are_read_by_block_type(): void
    {
        $http = $this->transport(200, $this->message([
            ['type' => 'thinking', 'thinking' => '', 'signature' => 'sig'],
            ['type' => 'tool_use', 'id' => 'toolu_9', 'name' => 'find_dealers', 'input' => ['region' => 'Samarqand', 'kind' => 'any']],
        ], 'tool_use'));
        $reply = (new AnthropicClaudeClient('test-key', requestOptions: ['transporter' => $http]))
            ->send($this->request('uz', [['role' => 'user', 'content' => 'Samarqandda diler bormi?']]));

        $this->assertSame('tool_use', $reply->stopReason);
        $this->assertSame([['id' => 'toolu_9', 'name' => 'find_dealers', 'input' => ['region' => 'Samarqand', 'kind' => 'any']]], $reply->toolCalls);

        // The tool turn is sent back with the SDK's own content blocks, then the tool result.
        $http2 = $this->transport(200, $this->message([['type' => 'text', 'text' => 'Mana.']]));
        (new AnthropicClaudeClient('test-key', requestOptions: ['transporter' => $http2]))
            ->send($this->request('uz', [
                ['role' => 'user', 'content' => 'Samarqandda diler bormi?'],
                ['role' => 'assistant', 'content' => $reply->content],
                ['role' => 'user', 'content' => [['type' => 'tool_result', 'toolUseID' => 'toolu_9', 'content' => '{"dealers":[]}', 'isError' => false]]],
            ]));
        $body = json_decode((string) $http2->request->getBody(), true);
        $this->assertSame('thinking', $body['messages'][1]['content'][0]['type']);
        $this->assertSame('sig', $body['messages'][1]['content'][0]['signature']);
        $this->assertSame('tool_use', $body['messages'][1]['content'][1]['type']);
        $this->assertSame('toolu_9', $body['messages'][2]['content'][0]['tool_use_id']);
        $this->assertFalse($body['messages'][2]['content'][0]['is_error']);

        $refusal = $this->message([], 'refusal') + ['stop_details' => ['type' => 'refusal', 'category' => 'general_harms', 'explanation' => null]];
        $reply = (new AnthropicClaudeClient('test-key', requestOptions: ['transporter' => $this->transport(200, $refusal)]))
            ->send($this->request('ru', [['role' => 'user', 'content' => '...']]));
        $this->assertSame('refusal', $reply->stopReason);
        $this->assertSame('general_harms', $reply->refusalCategory);
    }

    public function test_streamed_reply_is_passed_on_piece_by_piece(): void
    {
        $events = [
            ['type' => 'message_start', 'message' => $this->message([])],
            ['type' => 'content_block_start', 'index' => 0, 'content_block' => ['type' => 'text', 'text' => '']],
            ['type' => 'content_block_delta', 'index' => 0, 'delta' => ['type' => 'text_delta', 'text' => 'Да, ']],
            ['type' => 'content_block_delta', 'index' => 0, 'delta' => ['type' => 'text_delta', 'text' => 'печатает.']],
            ['type' => 'content_block_stop', 'index' => 0],
            ['type' => 'message_delta', 'delta' => ['stop_reason' => 'end_turn', 'stop_sequence' => null], 'usage' => ['output_tokens' => 21]],
            ['type' => 'message_stop'],
        ];
        $sse = implode('', array_map(fn ($e) => 'event: '.$e['type']."\ndata: ".json_encode($e)."\n\n", $events));
        $http = $this->transport(200, $sse, 'text/event-stream');
        $client = new AnthropicClaudeClient('test-key', requestOptions: ['transporter' => $http, 'streamingTransporter' => $http]);

        $pieces = [];
        $reply = $client->send(
            $this->request('ru', [['role' => 'user', 'content' => 'Печатает?']]),
            function (string $piece) use (&$pieces) {
                $pieces[] = $piece;
            },
        );

        $this->assertSame(['Да, ', 'печатает.'], $pieces);
        $this->assertSame('Да, печатает.', $reply->text);
        $this->assertSame(21, $reply->outputTokens);
        $this->assertTrue(json_decode((string) $http->request->getBody(), true)['stream']);
    }

    public function test_api_errors_become_claude_unavailable(): void
    {
        foreach ([401 => 'auth', 429 => 'rate_limit', 400 => 'api_error', 529 => 'api_error'] as $status => $reason) {
            $http = $this->transport($status, ['type' => 'error', 'error' => ['type' => 'x_error', 'message' => 'no']]);
            $client = new AnthropicClaudeClient('test-key', requestOptions: ['transporter' => $http, 'maxRetries' => 0]);
            try {
                $client->send($this->request('ru', [['role' => 'user', 'content' => '?']]));
                $this->fail('expected ClaudeUnavailable');
            } catch (ClaudeUnavailable $e) {
                $this->assertSame($reason, $e->reason, "HTTP $status");
            }
        }
    }

    public function test_missing_key_never_reaches_the_transport(): void
    {
        $http = $this->transport(200, $this->message([]));
        foreach ([null, '', '  '] as $key) {
            $client = new AnthropicClaudeClient($key, requestOptions: ['transporter' => $http]);
            $this->assertFalse($client->isConfigured());
            try {
                $client->send(['model' => 'claude-sonnet-5-5', 'maxTokens' => 10, 'messages' => []]);
                $this->fail('expected ClaudeUnavailable');
            } catch (ClaudeUnavailable $e) {
                $this->assertSame('missing_key', $e->reason);
            }
        }
        $this->assertNull($http->request);
    }
}
