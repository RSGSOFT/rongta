<?php

namespace App\Services\Catalog;

use App\Models\ScaleModel;
use App\Services\Chatbot\Claude\ClaudeClient;
use App\Services\Chatbot\Claude\ClaudeFactory;
use App\Services\Chatbot\Claude\ClaudeReply;
use App\Services\Chatbot\Claude\ClaudeUnavailable;
use App\Services\Chatbot\PriceGuard;
use App\Support\UzbekText;
use RuntimeException;

/**
 * Admin "Describe photos": Claude looks at a model's photos and writes a short alt text for
 * each in Russian, Uzbek and English, saying only what the photo shows. The result fills the
 * form; staff read it and save it themselves.
 */
class AltTextGenerator
{
    public const LOCALES = ['ru', 'uz', 'en'];

    /** Photos per request (each up to 5 MB): the button can be pressed again for the rest. */
    public const MAX_PHOTOS = LineArtGenerator::MAX_PHOTOS;

    public const ALT_MAX = 125;

    private const PRICE_WORDS = '/(?<![\p{L}])(?:цен(?:а|ы|у|е|ой)|стоимост\p{L}*|скидк\p{L}*|prices?|discount\p{L}*|narx\p{L}*|chegirma\p{L}*)(?![\p{L}])/iu';

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
     * @param  list<array{mediaType: string, data: string}>  $photos  base64 photos
     * @return array<string, mixed>
     */
    public function request(ScaleModel $model, array $photos): array
    {
        $max = self::ALT_MAX;
        $system = <<<TXT
        You write alt texts for photos of commercial scales on rongta.uz, the "Rongta Uzbekistan" site of RSG, the official distributor of Rongta scales in Uzbekistan. Alt texts are read by screen readers and by search engines.

        For each attached photo, in the order given, write one alt text in Russian (ru), Uzbek in Latin script (uz) and English (en).

        Rules:
        - At most {$max} characters each. Begin with "Rongta" and the model name exactly as given, then say what this photo shows: the view (front, side, back, top), the visible parts (customer display, keyboard, label printer, camera, stainless platter, pole) and the setting if there is one (a shop counter, a market stall).
        - Describe only what is visible in the photo. Never add specs, numbers, features or claims that the photo does not show.
        - No prices, currencies, discounts or sales wording. No "image of" or "photo of" at the start.
        - Uzbek: modern Latin script, with oʻ and gʻ written with the ʻ character (U+02BB).
        - If a photo does not show a scale, still describe it plainly in one short line.
        TXT;

        $content = [];
        foreach ($photos as $i => $photo) {
            $content[] = ['type' => 'text', 'text' => 'Photo '.($i + 1).':'];
            $content[] = ['type' => 'image', 'source' => ['type' => 'base64', 'mediaType' => $photo['mediaType'], 'data' => $photo['data']]];
        }
        $type = trim($model->tr('type', 'en'));
        $content[] = ['type' => 'text', 'text' => "Model name: {$model->name}".($type !== '' ? "\nKind of scale: {$type}" : '')
            ."\nWrite the alt texts for these ".count($photos).' photos, in order.'];

        return [
            'model' => ClaudeFactory::model(),
            'maxTokens' => 4000,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $content]],
            'thinking' => ['type' => 'adaptive'],
            'outputConfig' => [
                'effort' => 'low',
                'format' => ['type' => 'json_schema', 'schema' => self::schema()],
            ],
            ...ClaudeFactory::fallbackOptions(),
            'requestOptions' => ['timeout' => 180.0, 'maxRetries' => 1],
        ];
    }

    /**
     * @param  list<string>  $paths  photo paths on the public disk
     * @return array<string, array{ru: string, uz: string, en: string}> path => alt texts
     *
     * @throws RuntimeException with a message for staff (in Russian)
     */
    public function generate(ScaleModel $model, array $paths): array
    {
        $paths = array_slice(array_values(array_unique($paths)), 0, self::MAX_PHOTOS);
        $photos = LineArtGenerator::photos($paths);

        try {
            $reply = $this->lastReply = $this->claude->send($this->request($model, $photos));
        } catch (ClaudeUnavailable $e) {
            throw new RuntimeException($e->reason === 'missing_key'
                ? 'Ключ Claude API не задан. Добавьте ANTHROPIC_API_KEY в файл .env.'
                : 'Не удалось обратиться к Claude ('.$e->reason.'). Попробуйте позже. Подробности записаны в журнал ошибок.', 0, $e);
        }

        if ($reply->stopReason === 'refusal') {
            throw new RuntimeException('Claude отказался описывать эти фото.');
        }

        $data = json_decode($reply->text, true);
        $items = is_array($data) ? array_values((array) ($data['photos'] ?? [])) : [];
        if ($reply->stopReason === 'max_tokens' || count($items) !== count($paths)) {
            throw new RuntimeException('Claude вернул ответ в неожиданном виде. Попробуйте ещё раз.');
        }

        $result = [];
        foreach ($paths as $i => $path) {
            foreach (self::LOCALES as $locale) {
                $text = trim((string) preg_replace('/\s+/u', ' ', (string) ($items[$i][$locale] ?? '')));
                if ($locale === 'uz') {
                    $text = UzbekText::normalize($text);
                }
                if ($text === '') {
                    throw new RuntimeException('Claude не написал описание на одном из языков. Попробуйте ещё раз.');
                }
                if (PriceGuard::hasAmount($text) || preg_match(self::PRICE_WORDS, $text)) {
                    throw new RuntimeException('В ответе Claude оказалось упоминание цены, такой текст не подходит. Попробуйте ещё раз.');
                }
                $result[$path][$locale] = mb_substr($text, 0, self::ALT_MAX + 25);
            }
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'photos' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => array_fill_keys(self::LOCALES, ['type' => 'string']),
                        'required' => self::LOCALES,
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['photos'],
            'additionalProperties' => false,
        ];
    }
}
