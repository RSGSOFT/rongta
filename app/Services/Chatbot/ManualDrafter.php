<?php

namespace App\Services\Chatbot;

use App\Models\KnowledgeArticle;
use App\Models\ScaleModel;
use App\Services\Chatbot\Claude\ClaudeClient;
use App\Services\Chatbot\Claude\ClaudeFactory;
use App\Services\Chatbot\Claude\ClaudeUnavailable;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * "Teach from a manual": Claude reads an uploaded PDF and drafts knowledge articles from it.
 * Every draft is saved unpublished; staff review, correct and publish each one by hand.
 */
class ManualDrafter
{
    /** The API accepts requests up to 32 MB; base64 adds a third to the file size. */
    public const MAX_PDF_BYTES = 20 * 1024 * 1024;

    private ClaudeClient $claude;

    public function __construct(?ClaudeClient $claude = null)
    {
        $this->claude = $claude ?? ClaudeFactory::make();
    }

    public function available(): bool
    {
        return $this->claude->isConfigured();
    }

    /**
     * The request sent to Claude. Public so the exact shape can be tested and shown.
     *
     * @return array<string, mixed>
     */
    public function request(string $pdfBytes, string $fileName, ?ScaleModel $model): array
    {
        $about = $model
            ? 'The manual is for the Rongta scale "'.$model->name.'".'
            : 'The manual is for a Rongta commercial scale; the model is not specified.';

        $system = <<<'TXT'
        You prepare knowledge-base articles for the support chatbot of rongta.uz, the website of Rongta commercial scales in Uzbekistan. RSG staff upload a product manual; you draft articles from it, and a staff member reviews every draft before it is published. The chatbot later answers shop owners and technicians from these articles, so each one must be accurate and usable on its own.

        Write one article per practical topic a scale owner or technician would ask about: first setup, loading paper or labels, connecting to a network or a PC, loading products, calibration and checks, error messages and their fixes, cleaning and care, and similar. Skip legal boilerplate, tables of contents and marketing text. A short manual may give three articles, a long one up to fifteen; do not pad.

        Each article:
        - States only what the manual says. Do not add steps, values, model names or advice from your own knowledge. If the manual is unclear on a point, leave the point out.
        - Is self-contained: name the model it applies to if the manual does, and write the steps in order, one per line, starting with "- ".
        - Has the same content in Russian, in Uzbek (Latin script, with oʻ and gʻ written with the ʻ character, U+02BB) and in English. Keep button names, menu items and codes exactly as printed in the manual.
        - Has a short title that reads like the question it answers, and two to five lower-case tags.
        - Contains no prices or costs of any kind, and no warranty periods.

        If the document is not a manual for a commercial scale, or has no usable content, return an empty list of articles.
        TXT;

        return [
            'model' => ClaudeFactory::model(),
            'maxTokens' => 32000,
            'system' => $system,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'document',
                        'source' => ['type' => 'base64', 'mediaType' => 'application/pdf', 'data' => base64_encode($pdfBytes)],
                        'title' => $fileName,
                    ],
                    ['type' => 'text', 'text' => $about.' Draft the knowledge articles from this manual.'],
                ],
            ]],
            'thinking' => ['type' => 'adaptive'],
            'outputConfig' => [
                'effort' => 'medium',
                'format' => ['type' => 'json_schema', 'schema' => self::schema()],
            ],
            ...ClaudeFactory::fallbackOptions(),
            'requestOptions' => ['timeout' => 600.0, 'maxRetries' => 0],
        ];
    }

    /**
     * @return Collection<int, KnowledgeArticle> the saved, unpublished drafts
     *
     * @throws RuntimeException with a message for staff (in Russian) when nothing could be drafted
     */
    public function draft(string $pdfPath, string $fileName, ?ScaleModel $model = null, ?int $userId = null): Collection
    {
        $bytes = @file_get_contents($pdfPath);
        if ($bytes === false || ! str_starts_with($bytes, '%PDF')) {
            throw new RuntimeException('Файл не похож на PDF. Загрузите руководство в формате PDF.');
        }
        if (strlen($bytes) > self::MAX_PDF_BYTES) {
            throw new RuntimeException('Файл слишком большой. Разделите руководство на части до 20 МБ и загрузите их по очереди.');
        }

        try {
            // Streamed: a long answer would otherwise run into HTTP timeouts.
            $reply = $this->claude->send($this->request($bytes, $fileName, $model), static function (string $piece): void {});
        } catch (ClaudeUnavailable $e) {
            throw new RuntimeException($e->reason === 'missing_key'
                ? 'Ключ Claude API не задан. Добавьте ANTHROPIC_API_KEY в файл .env.'
                : 'Не удалось обратиться к Claude ('.$e->reason.'). Попробуйте позже. Подробности записаны в журнал ошибок.', 0, $e);
        }

        if ($reply->stopReason === 'refusal') {
            throw new RuntimeException('Claude отказался обрабатывать этот документ. Проверьте, что это руководство к весам.');
        }
        if ($reply->stopReason === 'max_tokens') {
            throw new RuntimeException('Руководство слишком объёмное для одного раза. Разделите PDF на части и загрузите их по очереди.');
        }

        $data = json_decode($reply->text, true);
        if (! is_array($data) || ! is_array($data['articles'] ?? null)) {
            throw new RuntimeException('Claude вернул ответ в неожиданном виде. Попробуйте ещё раз.');
        }

        $drafts = collect();
        foreach ($data['articles'] as $row) {
            $title = ['ru' => trim((string) ($row['title_ru'] ?? '')), 'uz' => trim((string) ($row['title_uz'] ?? '')), 'en' => trim((string) ($row['title_en'] ?? ''))];
            $body = ['ru' => trim((string) ($row['body_ru'] ?? '')), 'uz' => trim((string) ($row['body_uz'] ?? '')), 'en' => trim((string) ($row['body_en'] ?? ''))];
            // No title, no text, or a money amount: not a usable draft.
            if ($title['ru'] === '' || $body['ru'] === '' || PriceGuard::hasAmount(implode(' ', $title).' '.implode(' ', $body))) {
                continue;
            }
            $drafts->push(KnowledgeArticle::create([
                'title' => array_map(fn (string $t) => mb_substr($t, 0, 200), $title),
                'body' => $body,
                'scale_model_id' => $model?->id,
                'family_id' => $model?->family_id,
                'tags' => collect($row['tags'] ?? [])->filter(fn ($t) => is_string($t) && trim($t) !== '')
                    ->map(fn (string $t) => mb_strtolower(trim($t)))->unique()->take(8)->values()->all(),
                'is_published' => false,
                'source' => 'pdf',
                'source_ref' => mb_substr($fileName, 0, 255),
                'created_by' => $userId,
            ]));
        }

        if ($drafts->isEmpty()) {
            throw new RuntimeException('Из этого документа не получилось ни одной статьи. Проверьте, что это руководство к весам с текстом, а не сканы без распознавания.');
        }

        return $drafts;
    }

    /** @return array<string, mixed> */
    private static function schema(): array
    {
        $text = ['type' => 'string'];

        return [
            'type' => 'object',
            'properties' => [
                'articles' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title_ru' => $text, 'title_uz' => $text, 'title_en' => $text,
                            'body_ru' => $text, 'body_uz' => $text, 'body_en' => $text,
                            'tags' => ['type' => 'array', 'items' => $text],
                        ],
                        'required' => ['title_ru', 'title_uz', 'title_en', 'body_ru', 'body_uz', 'body_en', 'tags'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['articles'],
            'additionalProperties' => false,
        ];
    }
}
