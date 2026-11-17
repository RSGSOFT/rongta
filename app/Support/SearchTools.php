<?php

namespace App\Support;

use App\Models\ScaleModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Search engines and analytics, set in admin settings ("search" setting):
 *   {google_verification, yandex_verification, ga4_id, metrica_id, indexnow, indexnow_key}
 * Verification codes are meta tags on every public page. Analytics scripts and IndexNow pings
 * run only in production, so local work and tests never count as visits or reach the engines.
 * These are public identifiers, not secrets: they end up in the page source anyway.
 */
class SearchTools
{
    public const KEY = 'search';

    public const GOOGLE_PATTERN = '/^[A-Za-z0-9_-]{20,100}$/';

    public const YANDEX_PATTERN = '/^[a-f0-9]{8,64}$/';

    public const GA4_PATTERN = '/^G-[A-Z0-9]{4,20}$/';

    public const METRICA_PATTERN = '/^\d{4,12}$/';

    /** One endpoint shares submissions with every IndexNow engine (Yandex, Bing and others). */
    public const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';

    /** @return array{google_verification: string, yandex_verification: string, ga4_id: string, metrica_id: string, indexnow: bool, indexnow_key: string} */
    public static function all(): array
    {
        $stored = (array) setting(self::KEY, []);
        $value = fn (string $key, string $pattern) => is_string($stored[$key] ?? null) && preg_match($pattern, $stored[$key]) ? $stored[$key] : '';

        return [
            'google_verification' => $value('google_verification', self::GOOGLE_PATTERN),
            'yandex_verification' => $value('yandex_verification', self::YANDEX_PATTERN),
            'ga4_id' => $value('ga4_id', self::GA4_PATTERN),
            'metrica_id' => $value('metrica_id', self::METRICA_PATTERN),
            'indexnow' => (bool) ($stored['indexnow'] ?? false),
            'indexnow_key' => is_string($stored['indexnow_key'] ?? null) && preg_match('/^[a-f0-9]{32}$/', $stored['indexnow_key']) ? $stored['indexnow_key'] : '',
        ];
    }

    /**
     * A verification code from what staff paste: the code itself, or the whole meta tag.
     * Returns the value inside content="..." when a tag is pasted.
     */
    public static function codeFrom(string $input): string
    {
        $input = trim($input);

        return preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $input, $m) ? trim($m[1]) : $input;
    }

    public static function trackingEnabled(): bool
    {
        return app()->environment('production');
    }

    /** Tell IndexNow engines that a model's pages changed (published, edited or unpublished). */
    public static function modelChanged(ScaleModel $model): void
    {
        $settings = self::all();
        if (! $settings['indexnow'] || $settings['indexnow_key'] === '' || ! self::trackingEnabled()) {
            return;
        }
        if (! $model->is_published && ! $model->wasChanged('is_published')) {
            return;
        }

        $urls = array_map(fn (string $locale) => lroute('model', ['slug' => $model->slug], $locale), Locales::ALL);
        dispatch(fn () => self::submit($urls))->afterResponse();
    }

    /** @param list<string> $urls */
    public static function submit(array $urls): bool
    {
        $settings = self::all();
        if ($urls === [] || $settings['indexnow_key'] === '') {
            return false;
        }

        try {
            $response = Http::timeout(5)->post(self::INDEXNOW_ENDPOINT, [
                'host' => parse_url($urls[0], PHP_URL_HOST),
                'key' => $settings['indexnow_key'],
                'keyLocation' => route('indexnow-key'),
                'urlList' => array_values($urls),
            ]);

            return $response->successful();
        } catch (Throwable $e) {
            Log::warning('IndexNow submission failed', ['message' => $e->getMessage()]);

            return false;
        }
    }
}
