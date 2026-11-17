<?php

use App\Models\Setting;
use App\Support\Locales;
use App\Support\UiStrings;

if (! function_exists('ui')) {
    /**
     * Interface string for the current locale, from the ui_strings table (cached forever,
     * flushed when a UiString is saved or deleted). Placeholders are written {name}:
     * ui('cat.count', ['n' => 3, 'all' => 11]). A missing key returns "[key]".
     */
    function ui(string $key, array $replace = [], ?string $locale = null): string
    {
        return UiStrings::get($key, $replace, $locale);
    }
}

if (! function_exists('trv')) {
    /**
     * Pick one language out of a {ru, uz, en} value. Falls back to Russian, then to any
     * non-empty language. A plain string is returned as is.
     */
    function trv(mixed $value, ?string $locale = null): string
    {
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }
        if (! is_array($value)) {
            return '';
        }
        $locale ??= app()->getLocale();
        foreach ([$locale, Locales::DEFAULT] as $try) {
            if (isset($value[$try]) && $value[$try] !== '') {
                return (string) $value[$try];
            }
        }
        foreach ($value as $text) {
            if (is_string($text) && $text !== '') {
                return $text;
            }
        }

        return '';
    }
}

if (! function_exists('lroute')) {
    /**
     * Locale-aware URL for a public route. Names: home, catalog, compare, model, dealers,
     * knowledge. lroute('model', ['slug' => 'c1']), lroute('catalog', [], 'uz').
     */
    function lroute(string $name, array $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        return route(Locales::routeName($name, $locale), $parameters, $absolute);
    }
}

if (! function_exists('locale_url')) {
    /**
     * URL of the page being viewed, in another locale (for the language switcher).
     * Keeps route parameters and the query string. Falls back to that locale's home page.
     */
    function locale_url(string $locale): string
    {
        return Locales::currentUrlIn($locale);
    }
}

if (! function_exists('setting')) {
    /**
     * Site setting from the settings table (cached forever, flushed on save).
     * Dot notation reaches into the JSON value: setting('contacts.phone').
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::read($key, $default);
    }
}
