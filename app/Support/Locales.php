<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Throwable;

/**
 * Russian is served at "/", Uzbek at "/uz/...", English at "/en/...".
 * Route names: "catalog" (ru), "uz.catalog", "en.catalog".
 */
class Locales
{
    public const DEFAULT = 'ru';

    public const ALL = ['ru', 'uz', 'en'];

    /** Locales that carry a URL prefix (everything except the default). */
    public static function prefixed(): array
    {
        return array_values(array_diff(self::ALL, [self::DEFAULT]));
    }

    public static function isSupported(?string $locale): bool
    {
        return in_array($locale, self::ALL, true);
    }

    /** Full route name for a base name in a locale: ("catalog", "uz") => "uz.catalog". */
    public static function routeName(string $name, ?string $locale = null): string
    {
        $locale = self::isSupported($locale) ? $locale : app()->getLocale();
        if (! self::isSupported($locale)) {
            $locale = self::DEFAULT;
        }

        return $locale === self::DEFAULT ? $name : $locale.'.'.$name;
    }

    /** Route name without its locale prefix: "uz.catalog" => "catalog". */
    public static function baseName(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }
        foreach (self::prefixed() as $locale) {
            if (str_starts_with($routeName, $locale.'.')) {
                return substr($routeName, strlen($locale) + 1);
            }
        }

        return $routeName;
    }

    /** Base name of the public page being viewed ("catalog", "model", ...), or null. */
    public static function currentPage(): ?string
    {
        $route = self::pageRoute();

        return $route ? self::baseName($route->getName()) : null;
    }

    /**
     * The page being viewed, in a locale. With $withQuery off the query string is dropped:
     * that is the canonical address used by the canonical link, hreflang and the sitemap.
     */
    public static function currentUrlIn(string $locale, bool $withQuery = true): string
    {
        $route = self::pageRoute();
        if (! $route) {
            return lroute('home', [], $locale);
        }

        [$pageRoute, $query] = [$route, $withQuery ? self::pageQuery() : []];

        return lroute(self::baseName($pageRoute->getName()), $pageRoute->parameters() + $query, $locale);
    }

    /**
     * The public page route for this request. During a Livewire update request the
     * current route is Livewire's own endpoint, so the page is recovered from the Referer.
     */
    protected static function pageRoute(): ?Route
    {
        $route = request()->route();
        if ($route && self::isPublicPage($route)) {
            return $route;
        }

        $referer = request()->headers->get('referer');
        if (! $referer) {
            return null;
        }
        try {
            $matched = app('router')->getRoutes()->match(Request::create($referer));

            return self::isPublicPage($matched) ? $matched : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected static function pageQuery(): array
    {
        $route = request()->route();
        if ($route && self::isPublicPage($route)) {
            return request()->query();
        }
        parse_str((string) parse_url((string) request()->headers->get('referer'), PHP_URL_QUERY), $query);

        return $query;
    }

    protected static function isPublicPage(Route $route): bool
    {
        return (bool) ($route->getAction('site_page') ?? false);
    }
}
