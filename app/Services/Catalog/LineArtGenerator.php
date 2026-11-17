<?php

namespace App\Services\Catalog;

use App\Models\ScaleModel;
use App\Services\Chatbot\Claude\ClaudeClient;
use App\Services\Chatbot\Claude\ClaudeFactory;
use App\Services\Chatbot\Claude\ClaudeReply;
use App\Services\Chatbot\Claude\ClaudeUnavailable;
use App\Support\LineArt;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Admin "Generate image": Claude looks at a model's photos and draws its line drawing by the
 * scale line-art prompt (LineArt::prompt(), editable in admin settings). The result is only a
 * draft: staff look at it and decide whether the model uses it.
 */
class LineArtGenerator
{
    public const MAX_PHOTOS = 5;

    /** Per image limit of the Claude API. */
    public const MAX_PHOTO_BYTES = 5 * 1024 * 1024;

    private const MEDIA_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    private ClaudeClient $claude;

    /** The reply of the last generate() call, for the activity log. */
    public ?ClaudeReply $lastReply = null;

    public function __construct(?ClaudeClient $claude = null)
    {
        $this->claude = $claude ?? ClaudeFactory::make();
    }

    public function available(): bool
    {
        return $this->claude->isConfigured();
    }

    /**
     * The request sent to Claude. Public so its exact shape can be tested.
     *
     * @param  list<array{mediaType: string, data: string}>  $photos  base64 photos
     * @return array<string, mixed>
     */
    public function request(ScaleModel $model, array $photos): array
    {
        $system = LineArt::prompt()."\n\n".<<<'TXT'
        ## How you are called here

        You run inside the admin panel of rongta.uz, in one step, with no one to answer questions. The inputs are complete: the model name and facts below, and the attached photos. Do not ask for anything.

        - Draw for every use at once: catalog card, model page gallery and hero. Use the model page level of detail and always give the two detail crops.
        - Return the SVG markup without any <style> block, inline colours, style attributes, ids, links or images: the site stylesheet provides the classes. Only these elements: g, path, rect, circle, ellipse, line, polyline, polygon, text, tspan.
        - Write the parts list and the note in Russian: RSG staff read them.
        - If the photos do not show a commercial scale, or are too unclear to draw from, leave "svg" empty and explain why in "problem". Otherwise "problem" is empty.
        - The facts below describe the model, but the photos decide what is drawn: never draw a part that the photos do not show.
        TXT;

        $family = $model->family?->tr('name', 'en') ?: '-';
        $printing = match ($model->label_print) {
            'no' => 'does not print',
            default => 'prints (labels or receipts: check the photos)',
        };
        $facts = "Model name: {$model->name}\nFamily: {$family}\nPrinting: {$printing}\n"
            .'AI camera family (AURORA): '.($model->is_aurora ? 'yes' : 'no')."\n"
            .'Photos attached: '.count($photos).'. Draw this model.';

        $content = [];
        foreach ($photos as $photo) {
            $content[] = ['type' => 'image', 'source' => ['type' => 'base64', 'mediaType' => $photo['mediaType'], 'data' => $photo['data']]];
        }
        $content[] = ['type' => 'text', 'text' => $facts];

        return [
            'model' => ClaudeFactory::model(),
            'maxTokens' => 32000,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $content]],
            'thinking' => ['type' => 'adaptive'],
            'outputConfig' => [
                'effort' => 'high',
                'format' => ['type' => 'json_schema', 'schema' => self::schema()],
            ],
            ...ClaudeFactory::fallbackOptions(),
            'requestOptions' => ['timeout' => 600.0, 'maxRetries' => 0],
        ];
    }

    /**
     * @param  list<string>  $paths  photo paths on the public disk
     * @return array{markup: string, viewBox: string, crops: list<array{viewBox: string, key: string}>, parts: list<string>, note: string}
     *
     * @throws RuntimeException with a message for staff (in Russian)
     */
    public function generate(ScaleModel $model, array $paths): array
    {
        $photos = $this->photos($paths);

        try {
            // Streamed: a long answer would otherwise run into HTTP timeouts.
            $reply = $this->lastReply = $this->claude->send($this->request($model, $photos), static function (string $piece): void {});
        } catch (ClaudeUnavailable $e) {
            throw new RuntimeException($e->reason === 'missing_key'
                ? 'Ключ Claude API не задан. Добавьте ANTHROPIC_API_KEY в файл .env.'
                : 'Не удалось обратиться к Claude ('.$e->reason.'). Попробуйте позже. Подробности записаны в журнал ошибок.', 0, $e);
        }

        if ($reply->stopReason === 'refusal') {
            throw new RuntimeException('Claude отказался рисовать по этим фото. Выберите другие фотографии весов.');
        }
        if ($reply->stopReason === 'max_tokens') {
            throw new RuntimeException('Рисунок получился слишком большим и оборвался. Попробуйте ещё раз.');
        }

        $data = json_decode($reply->text, true);
        if (! is_array($data)) {
            throw new RuntimeException('Claude вернул ответ в неожиданном виде. Попробуйте ещё раз.');
        }

        $problem = trim((string) ($data['problem'] ?? ''));
        $drawing = LineArt::clean((string) ($data['svg'] ?? ''));
        if ($drawing === null) {
            throw new RuntimeException($problem !== ''
                ? 'Claude не смог нарисовать модель: '.$problem
                : 'Claude не вернул пригодный рисунок. Попробуйте ещё раз или выберите другие фото.');
        }

        return [
            ...$drawing,
            'crops' => LineArt::crops((array) ($data['crops'] ?? [])),
            'parts' => collect((array) ($data['parts'] ?? []))->filter(fn ($p) => is_string($p) && trim($p) !== '')
                ->map(fn (string $p) => mb_substr(trim($p), 0, 300))->take(20)->values()->all(),
            'note' => mb_substr(trim((string) ($data['note'] ?? '')), 0, 600),
        ];
    }

    /**
     * Read and check the chosen photos.
     *
     * @param  list<string>  $paths
     * @return list<array{mediaType: string, data: string}>
     */
    private function photos(array $paths): array
    {
        $paths = array_slice(array_values(array_unique($paths)), 0, self::MAX_PHOTOS);
        if ($paths === []) {
            throw new RuntimeException('Выберите хотя бы одно фото модели. Без фото рисунок не делается.');
        }

        $disk = Storage::disk('public');
        $photos = [];
        foreach ($paths as $path) {
            $bytes = $disk->exists($path) ? $disk->get($path) : null;
            $type = $bytes !== null ? (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) : null;
            if ($bytes === null || ! in_array($type, self::MEDIA_TYPES, true)) {
                throw new RuntimeException('Фото «'.basename($path).'» не найдено или это не изображение JPG, PNG или WebP.');
            }
            if (strlen($bytes) > self::MAX_PHOTO_BYTES) {
                throw new RuntimeException('Фото «'.basename($path).'» больше 5 МБ. Уменьшите его и загрузите снова.');
            }
            $photos[] = ['mediaType' => $type, 'data' => base64_encode($bytes)];
        }

        return $photos;
    }

    /** @return array<string, mixed> */
    private static function schema(): array
    {
        $text = ['type' => 'string'];

        return [
            'type' => 'object',
            'properties' => [
                'parts' => ['type' => 'array', 'items' => $text],
                'svg' => $text,
                'crops' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'viewBox' => $text,
                            'key' => ['type' => 'string', 'enum' => LineArt::CROP_KEYS],
                        ],
                        'required' => ['viewBox', 'key'],
                        'additionalProperties' => false,
                    ],
                ],
                'note' => $text,
                'problem' => $text,
            ],
            'required' => ['parts', 'svg', 'crops', 'note', 'problem'],
            'additionalProperties' => false,
        ];
    }
}
