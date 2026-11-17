<?php

namespace App\Support;

/**
 * Trust material supplied by RSG in the admin panel (setting "trust"). The public trust block
 * shows only what is here: an item without material renders nothing, and the block disappears
 * when every item is empty. Stored shape (paths are under public/, as "storage/trust/..."):
 *
 *   certificate  {path, kind: image|pdf} | null
 *   service      {text: {ru,uz,en}, address: {ru,uz,en}}
 *   clients      [{path, name}]                  client logos
 *   pos          [{path, name}]                  POS software logos (also the model page "works with")
 *   metrology    [{path, ext, title: {ru,uz,en}}] Uzbek metrology documents
 *   photos       [{path, caption: {ru,uz,en}}]    shop photos
 */
class Trust
{
    public const KEY = 'trust';

    public const LISTS = ['clients', 'pos', 'metrology', 'photos'];

    /** The stored value, every part present and every malformed entry dropped. */
    public static function all(): array
    {
        $raw = setting(self::KEY, []);

        return self::normalise(is_array($raw) ? $raw : []);
    }

    public static function normalise(array $raw): array
    {
        $path = fn ($item) => is_array($item) && is_string($item['path'] ?? null) && $item['path'] !== '' ? $item['path'] : null;
        $trans = fn ($value) => collect(Locales::ALL)->mapWithKeys(fn ($l) => [$l => is_array($value) ? trim((string) ($value[$l] ?? '')) : ''])->all();

        $certificate = null;
        if ($p = $path($raw['certificate'] ?? null)) {
            $certificate = ['path' => $p, 'kind' => ($raw['certificate']['kind'] ?? '') === 'pdf' ? 'pdf' : 'image'];
        }

        $service = (array) ($raw['service'] ?? []);

        $list = function (string $key, callable $map) use ($raw, $path) {
            $out = [];
            foreach ((array) ($raw[$key] ?? []) as $item) {
                if ($p = $path($item)) {
                    $out[] = ['path' => $p] + $map($item);
                }
            }

            return $out;
        };

        return [
            'certificate' => $certificate,
            'service' => ['text' => $trans($service['text'] ?? null), 'address' => $trans($service['address'] ?? null)],
            'clients' => $list('clients', fn ($i) => ['name' => trim((string) ($i['name'] ?? ''))]),
            'pos' => $list('pos', fn ($i) => ['name' => trim((string) ($i['name'] ?? ''))]),
            'metrology' => $list('metrology', fn ($i) => ['ext' => strtolower((string) ($i['ext'] ?? pathinfo($i['path'], PATHINFO_EXTENSION))), 'title' => $trans($i['title'] ?? null)]),
            'photos' => $list('photos', fn ($i) => ['caption' => $trans($i['caption'] ?? null)]),
        ];
    }

    /** Whether the warranty and service item has anything to show. */
    public static function hasService(array $trust): bool
    {
        return trv($trust['service']['text']) !== '' || trv($trust['service']['address']) !== '';
    }

    /** Whether any item of the trust grid (everything except the shop photos) has material. */
    public static function hasGrid(array $trust): bool
    {
        return $trust['certificate'] !== null || self::hasService($trust)
            || $trust['clients'] !== [] || $trust['pos'] !== [] || $trust['metrology'] !== [];
    }

    public static function isEmpty(array $trust): bool
    {
        return ! self::hasGrid($trust) && $trust['photos'] === [];
    }
}
