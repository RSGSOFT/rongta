<?php

namespace App\Support;

use App\Models\UiString;
use Illuminate\Support\Facades\Cache;

/** Backing store for the global ui() helper. */
class UiStrings
{
    public const CACHE_KEY = 'ui_strings.all';

    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = Locales::isSupported($locale) ? $locale : app()->getLocale();
        $row = self::all()[$key] ?? null;

        $text = null;
        if ($row) {
            foreach ([$locale, Locales::DEFAULT, 'en'] as $try) {
                if (isset($row[$try]) && $row[$try] !== '') {
                    $text = $row[$try];
                    break;
                }
            }
        }
        if ($text === null) {
            return '['.$key.']';
        }

        foreach ($replace as $name => $value) {
            $text = str_replace('{'.$name.'}', (string) $value, $text);
        }

        return $text;
    }

    /** @return array<string, array{ru: ?string, uz: ?string, en: ?string}> */
    public static function all(): array
    {
        if (app()->bound(self::CACHE_KEY)) {
            return app(self::CACHE_KEY);
        }

        $all = Cache::rememberForever(self::CACHE_KEY, function () {
            return UiString::query()->get(['key', 'ru', 'uz', 'en'])
                ->mapWithKeys(fn (UiString $s) => [$s->key => ['ru' => $s->ru, 'uz' => $s->uz, 'en' => $s->en]])
                ->all();
        });
        app()->instance(self::CACHE_KEY, $all);

        return $all;
    }

    public static function flush(): void
    {
        app()->offsetUnset(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY);
    }
}
