<?php

namespace App\Services\Chatbot;

use App\Models\Setting;

/**
 * Chatbot configuration, stored as one JSON value under the "chatbot" setting and
 * edited in the admin panel. Anything not stored falls back to DEFAULTS.
 */
class ChatbotSettings
{
    public const KEY = 'chatbot';

    public const EFFORTS = ['low', 'medium', 'high'];

    public const DEFAULTS = [
        'enabled' => true,
        // Empty texts fall back to the ui strings bot.greeting / bot.fallback.
        'greeting' => ['ru' => '', 'uz' => '', 'en' => ''],
        'fallback' => ['ru' => '', 'uz' => '', 'en' => ''],
        'rules_extra' => '',          // staff additions to tone and rules
        'off_limits' => '',           // one topic per line
        'lead_daily_limit' => 40,     // visitor messages per lead per day
        'ip_daily_limit' => 80,       // visitor messages per IP address per day
        'max_message_chars' => 600,
        'history_messages' => 12,     // earlier messages sent to Claude with each question
        'max_output_tokens' => 4000,
        'effort' => 'low',
    ];

    /** @return array<string, mixed> */
    public static function all(): array
    {
        $stored = setting(self::KEY, []);
        $merged = array_merge(self::DEFAULTS, is_array($stored) ? array_intersect_key($stored, self::DEFAULTS) : []);

        foreach (['greeting', 'fallback'] as $key) {
            $merged[$key] = array_merge(self::DEFAULTS[$key], is_array($merged[$key]) ? $merged[$key] : []);
        }
        if (! in_array($merged['effort'], self::EFFORTS, true)) {
            $merged['effort'] = self::DEFAULTS['effort'];
        }

        return $merged;
    }

    public static function get(string $key): mixed
    {
        return self::all()[$key] ?? null;
    }

    public static function enabled(): bool
    {
        return (bool) self::get('enabled');
    }

    public static function int(string $key, int $min = 1): int
    {
        return max($min, (int) self::get($key));
    }

    /** @param array<string, mixed> $values */
    public static function save(array $values): void
    {
        Setting::write(self::KEY, array_merge(self::all(), array_intersect_key($values, self::DEFAULTS)));
    }

    /** Opening message of the chat, in the visitor's language. */
    public static function greeting(?string $locale = null): string
    {
        $text = trim((string) (self::get('greeting')[$locale ?? app()->getLocale()] ?? ''));

        return $text !== '' ? $text : ui('bot.greeting', [], $locale);
    }

    /** "I cannot answer that, contact a dealer or RSG" in the visitor's language. */
    public static function fallback(?string $locale = null): string
    {
        $text = trim((string) (self::get('fallback')[$locale ?? app()->getLocale()] ?? ''));

        return $text !== '' ? $text : ui('bot.fallback', [], $locale);
    }

    /** @return list<string> */
    public static function offLimits(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) self::get('off_limits')) ?: [])));
    }
}
