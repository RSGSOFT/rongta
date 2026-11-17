<?php

namespace App\Services\Chatbot;

/**
 * The tools Claude may call. Definitions are fixed (same order, same text on every
 * request) because tools sit at the very front of the cached prompt prefix.
 */
class KnowledgeTools
{
    public const REASONS = ['not_in_knowledge', 'unconfirmed_value', 'price', 'off_topic', 'needs_person', 'other'];

    public function __construct(private readonly KnowledgeBase $knowledge) {}

    /** @return list<array<string, mixed>> */
    public function definitions(): array
    {
        return [
            [
                'name' => 'find_dealers',
                'description' => 'List the confirmed Rongta dealers (resellers) and software integrators in Uzbekistan, '
                    .'optionally for one region. Call this whenever the visitor asks where to buy, who installs or services '
                    .'scales, or who connects a scale to POS or 1C software. Never name a dealer that this tool did not return.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'region' => [
                            'type' => 'string',
                            'description' => 'Region or city as the visitor wrote it (any language), or "all".',
                        ],
                        'kind' => [
                            'type' => 'string',
                            'enum' => ['reseller', 'integrator', 'any'],
                            'description' => 'reseller sells and services scales; integrator connects them to software.',
                        ],
                    ],
                    'required' => ['region', 'kind'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ],
            [
                'name' => 'report_unanswered',
                'description' => 'Record that the approved knowledge does not answer the visitor\'s question, so RSG staff can '
                    .'write the missing article. Call it every time you cannot answer, are asked for a price, or a needed value '
                    .'is UNKNOWN. Call it before you write your reply; then tell the visitor you cannot answer this and point '
                    .'them to the Dealers page or RSG.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'question' => [
                            'type' => 'string',
                            'description' => 'The question in one short sentence, in the visitor\'s language.',
                        ],
                        'reason' => ['type' => 'string', 'enum' => self::REASONS],
                    ],
                    'required' => ['question', 'reason'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ],
            [
                'name' => 'search_knowledge',
                'description' => 'Search the knowledge articles written by RSG staff (setup steps, troubleshooting, software, '
                    .'how-to answers taken from the manuals). Call this for any how-to, setup, error or troubleshooting question, '
                    .'and whenever the catalog knowledge in the system prompt does not fully answer the question.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Key words of the question, in the visitor\'s language.',
                        ],
                        'model' => [
                            'type' => 'string',
                            'description' => 'Model id or name if the question is about one model, otherwise an empty string.',
                        ],
                    ],
                    'required' => ['query', 'model'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ],
        ];
    }

    /**
     * Run one tool call. Returns the tool_result text and whether it is an error.
     *
     * @param  array<string, mixed>  $input
     * @return array{content: string, is_error: bool}
     */
    public function run(string $name, array $input, string $locale): array
    {
        // The model sometimes varies the letter case of a tool name; accept an unambiguous match.
        $result = match (strtolower(trim($name))) {
            'find_dealers' => $this->knowledge->dealers(
                (string) ($input['region'] ?? 'all'), (string) ($input['kind'] ?? 'any'), $locale
            ),
            'search_knowledge' => $this->knowledge->searchArticles(
                (string) ($input['query'] ?? ''), (string) ($input['model'] ?? ''), $locale
            ),
            'report_unanswered' => ['recorded' => true],
            default => null,
        };

        if ($result === null) {
            return [
                'content' => 'Unknown tool "'.$name.'". Available tools: find_dealers, report_unanswered, search_knowledge.',
                'is_error' => true,
            ];
        }

        return [
            'content' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_error' => false,
        ];
    }
}
