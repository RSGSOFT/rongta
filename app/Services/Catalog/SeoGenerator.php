<?php

namespace App\Services\Catalog;

use App\Livewire\Admin\Items\Catalog;
use App\Models\ModelSpec;
use App\Models\ScaleModel;
use App\Services\Chatbot\Claude\ClaudeClient;
use App\Services\Chatbot\Claude\ClaudeFactory;
use App\Services\Chatbot\Claude\ClaudeReply;
use App\Services\Chatbot\Claude\ClaudeUnavailable;
use App\Services\Chatbot\PriceGuard;
use App\Support\UzbekText;
use RuntimeException;

/**
 * Admin "Generate SEO": Claude writes the search result title and description of a model page
 * in Russian, Uzbek and English, from the model's own facts only (name, family, page copy,
 * confirmed specs). The result fills the form; staff read it and save it themselves.
 */
class SeoGenerator
{
    public const LOCALES = ['ru', 'uz', 'en'];

    /** The layout adds " · Rongta Uzbekistan" (about 20 characters) after the title. */
    public const TITLE_MAX = 60;

    public const DESCRIPTION_MAX = 180;

    /** Words that offer or announce a price; the site never shows one. */
    private const PRICE_WORDS = '/(?<![\p{L}])(?:цен(?:а|ы|у|е|ой|ам|ами|ах)|стоимост\p{L}*|скидк\p{L}*|дёшев\p{L}*|дешев\p{L}*|недорог\p{L}*|prices?|pricing|cheap\p{L}*|discount\p{L}*|narx\p{L}*|arzon\p{L}*|chegirma\p{L}*)(?![\p{L}])/iu';

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
     * @return array<string, mixed>
     */
    public function request(ScaleModel $model): array
    {
        $titleMax = self::TITLE_MAX;
        $descriptionMax = self::DESCRIPTION_MAX;
        $system = <<<TXT
        You write search result snippets (the <title> and the meta description) for model pages of rongta.uz, the "Rongta Uzbekistan" site of RSG, the official distributor of Rongta commercial scales in Uzbekistan. The site helps shop owners, supermarkets and market sellers pick a scale and find a dealer near them.

        Write one title and one description in each of three languages: Russian (ru), Uzbek in Latin script (uz), English (en). Each language is written for people searching in that language, not translated word for word.

        Rules:
        - Use only the facts given below. Never add a spec, feature, number, certificate, warranty, delivery promise, stock status or claim that is not in them. If a fact is missing, leave it out.
        - No prices, costs, discounts, currencies, "cheap" or "buy at a good price" wording, in any language. The site shows no prices.
        - Title: at most {$titleMax} characters. Start with the model name exactly as given, then what the scale is or its main use (for example label printing scale, AI scale for fresh produce). Do not add the site name: it is appended automatically.
        - Description: 120 to {$descriptionMax} characters. One or two plain sentences: what the scale does for a shop, one or two key confirmed facts (such as maximum capacity, label printing, connections), and that it is available from dealers in Uzbekistan. No exclamation marks, no capital-letter shouting, no emoji, no keyword lists.
        - Uzbek: modern Latin script. Write oʻ and gʻ with the ʻ character (U+02BB) and the tutuq belgisi as ʼ (U+02BC).
        - Keep the model name, the brand Rongta and the family name AURORA in their original Latin spelling in all three languages.
        TXT;

        return [
            'model' => ClaudeFactory::model(),
            'maxTokens' => 4000,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $this->facts($model)."\n\nWrite the title and description for this model page."]],
            'thinking' => ['type' => 'adaptive'],
            'outputConfig' => [
                'effort' => 'medium',
                'format' => ['type' => 'json_schema', 'schema' => self::schema()],
            ],
            ...ClaudeFactory::fallbackOptions(),
            'requestOptions' => ['timeout' => 120.0, 'maxRetries' => 1],
        ];
    }

    /** The model's facts as plain text: the only source Claude may use. */
    public function facts(ScaleModel $model): string
    {
        $lines = ['Model name: '.$model->name];
        if ($model->family) {
            $lines[] = 'Family: '.$model->family->tr('name', 'en').' (ru: '.$model->family->tr('name', 'ru').')';
        }
        $lines[] = 'AURORA AI family: '.($model->is_aurora ? 'yes' : 'no');
        $lines[] = 'Printing: '.match ($model->label_print) {
            'yes' => 'prints labels',
            'receipt' => 'prints receipts only',
            default => 'no printer',
        };

        $conn = [];
        foreach ((array) $model->conn as $key => $state) {
            if (isset(Catalog::CONN[$key])) {
                $conn[] = Catalog::CONN[$key].($state === 'opt' ? ' (optional)' : '');
            }
        }
        if ($conn) {
            $lines[] = 'Connections: '.implode(', ', $conn);
        }

        foreach (['type' => 'Kind of scale', 'headline' => 'Page headline', 'intro' => 'Page introduction'] as $field => $label) {
            foreach (self::LOCALES as $locale) {
                $text = trim((string) ($model->{$field}[$locale] ?? ''));
                if ($text !== '') {
                    $lines[] = "{$label} ({$locale}): {$text}";
                }
            }
        }

        if ($model->exists) {
            $features = $model->features->map(fn ($f) => trim($f->tr('title', 'en')))->filter()->values();
            if ($features->isNotEmpty()) {
                $lines[] = 'Feature blocks: '.$features->implode('; ');
            }

            $specs = $model->specs->map(function (ModelSpec $spec) {
                $value = trim($spec->text('en'));

                return $value === '' ? null : '- '.ui('spec.'.$spec->key, [], 'en').': '.$value;
            })->filter();
            if ($specs->isNotEmpty()) {
                $lines[] = "Confirmed specs:\n".$specs->implode("\n");
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{title: array{ru: string, uz: string, en: string}, description: array{ru: string, uz: string, en: string}}
     *
     * @throws RuntimeException with a message for staff (in Russian)
     */
    public function generate(ScaleModel $model): array
    {
        if (trim((string) $model->name) === '') {
            throw new RuntimeException('Сначала введите название модели.');
        }

        try {
            $reply = $this->lastReply = $this->claude->send($this->request($model));
        } catch (ClaudeUnavailable $e) {
            throw new RuntimeException($e->reason === 'missing_key'
                ? 'Ключ Claude API не задан. Добавьте ANTHROPIC_API_KEY в файл .env.'
                : 'Не удалось обратиться к Claude ('.$e->reason.'). Попробуйте позже. Подробности записаны в журнал ошибок.', 0, $e);
        }

        if ($reply->stopReason === 'refusal') {
            throw new RuntimeException('Claude отказался писать текст для этой модели. Проверьте название и тексты страницы.');
        }

        $data = json_decode($reply->text, true);
        if ($reply->stopReason === 'max_tokens' || ! is_array($data)) {
            throw new RuntimeException('Claude вернул ответ в неожиданном виде. Попробуйте ещё раз.');
        }

        $result = ['title' => [], 'description' => []];
        foreach (['title' => self::TITLE_MAX, 'description' => self::DESCRIPTION_MAX] as $field => $max) {
            foreach (self::LOCALES as $locale) {
                $text = trim((string) preg_replace('/\s+/u', ' ', (string) ($data[$field][$locale] ?? '')));
                if ($locale === 'uz') {
                    $text = UzbekText::normalize($text);
                }
                if ($text === '') {
                    throw new RuntimeException('Claude не написал текст на одном из языков. Попробуйте ещё раз.');
                }
                if (PriceGuard::hasAmount($text) || preg_match(self::PRICE_WORDS, $text)) {
                    throw new RuntimeException('В ответе Claude оказалось упоминание цены, такой текст не подходит. Попробуйте ещё раз.');
                }
                $result[$field][$locale] = mb_substr($text, 0, $max + 20);
            }
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private static function schema(): array
    {
        $byLocale = [
            'type' => 'object',
            'properties' => array_fill_keys(self::LOCALES, ['type' => 'string']),
            'required' => self::LOCALES,
            'additionalProperties' => false,
        ];

        return [
            'type' => 'object',
            'properties' => ['title' => $byLocale, 'description' => $byLocale],
            'required' => ['title', 'description'],
            'additionalProperties' => false,
        ];
    }
}
