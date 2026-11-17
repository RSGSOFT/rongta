<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Key/value site settings. Read through setting('key'), write through Setting::write(). */
class Setting extends Model
{
    public const CACHE_KEY = 'settings.all';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }

    /** setting('contacts') or, reaching into the JSON value, setting('contacts.phone'). */
    public static function read(string $key, mixed $default = null): mixed
    {
        // Kept on the app container for the rest of the request, so a page reads the cache once.
        if (app()->bound(self::CACHE_KEY)) {
            $all = app(self::CACHE_KEY);
        } else {
            $all = Cache::rememberForever(self::CACHE_KEY, function () {
                return static::query()->pluck('value', 'key')->all();
            });
            app()->instance(self::CACHE_KEY, $all);
        }

        if (array_key_exists($key, $all)) {
            return $all[$key] ?? $default;
        }

        // Longest stored key that prefixes the request, then dot-walk the rest.
        $parts = explode('.', $key);
        for ($i = count($parts) - 1; $i >= 1; $i--) {
            $head = implode('.', array_slice($parts, 0, $i));
            if (array_key_exists($head, $all)) {
                return data_get($all[$head], implode('.', array_slice($parts, $i)), $default);
            }
        }

        return $default;
    }

    public static function write(string $key, mixed $value): static
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function flush(): void
    {
        app()->offsetUnset(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY);
    }
}
