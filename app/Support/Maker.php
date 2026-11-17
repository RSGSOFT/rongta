<?php

namespace App\Support;

/**
 * "About the manufacturer" card of the trust block (setting "maker"), edited in the admin panel:
 *
 *   rows    [{label: {ru,uz,en}, label_key: ?string, value: {ru,uz,en}, visible: bool}, ...] in display order
 *   source  {ru,uz,en}  caption under the figures
 *
 * A row's label is its own text, or the ui() string label_key when the text is empty.
 * The card is hidden when no row is visible.
 */
class Maker
{
    public const KEY = 'maker';

    /**
     * Today's figures from rongtatech.com, checked by the owner. Trading in 9881.HK is suspended
     * according to Rongta's own filings, so the stock exchange row is kept but switched off.
     */
    public static function defaults(): array
    {
        $row = fn (array $label, array $value, bool $visible = true) => ['label' => $label, 'label_key' => null, 'value' => $value, 'visible' => $visible];
        $same = fn (string $v) => ['ru' => $v, 'uz' => $v, 'en' => $v];

        return [
            'rows' => [
                $row(['ru' => 'Год основания', 'uz' => 'Tashkil etilgan yili', 'en' => 'Founded'], $same('2010')),
                $row(['ru' => 'Расположение', 'uz' => 'Joylashuvi', 'en' => 'Location'], ['ru' => 'Сямынь, Китай', 'uz' => 'Syamen, Xitoy', 'en' => 'Xiamen, China']),
                $row(['ru' => 'Биржа', 'uz' => 'Birja', 'en' => 'Stock exchange'], ['ru' => 'Гонконг, 9881.HK', 'uz' => 'Gonkong, 9881.HK', 'en' => 'Hong Kong, 9881.HK'], false),
                $row(['ru' => 'Зарегистрированные патенты', 'uz' => 'Roʻyxatga olingan patentlar', 'en' => 'Registered patents'], $same('210+')),
                $row(['ru' => 'Клиенты', 'uz' => 'Mijozlar', 'en' => 'Customers'], ['ru' => "2\u{00A0}000+", 'uz' => "2\u{00A0}000+", 'en' => '2,000+']),
                $row(['ru' => 'Сотрудники R&D', 'uz' => 'R&D xodimlari', 'en' => 'R&D personnel'], $same('100+')),
            ],
            'source' => ['ru' => 'Данные с сайта rongtatech.com.', 'uz' => 'Maʼlumotlar rongtatech.com saytidan olingan.', 'en' => 'Figures from rongtatech.com.'],
        ];
    }

    /** The stored value with every row complete; the defaults when the setting is missing. */
    public static function all(): array
    {
        $raw = setting(self::KEY);
        if (! is_array($raw)) {
            return self::defaults();
        }

        return self::normalise($raw);
    }

    public static function normalise(array $raw): array
    {
        $trans = fn ($value) => collect(Locales::ALL)->mapWithKeys(fn ($l) => [$l => is_array($value) ? trim((string) ($value[$l] ?? '')) : ''])->all();

        $rows = [];
        foreach ((array) ($raw['rows'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rows[] = [
                'label' => $trans($row['label'] ?? null),
                'label_key' => is_string($row['label_key'] ?? null) && $row['label_key'] !== '' ? $row['label_key'] : null,
                'value' => $trans($row['value'] ?? null),
                'visible' => (bool) ($row['visible'] ?? true),
            ];
        }

        return ['rows' => $rows, 'source' => $trans($raw['source'] ?? null)];
    }

    /**
     * Rows to show in a language: [[label, value], ...]. A row without a label or a value is skipped.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function visibleRows(?string $locale = null): array
    {
        $out = [];
        foreach (self::all()['rows'] as $row) {
            if (! $row['visible']) {
                continue;
            }
            $label = trv($row['label'], $locale);
            if ($label === '' && $row['label_key']) {
                $label = ui($row['label_key'], [], $locale);
            }
            $value = trv($row['value'], $locale);
            if ($label !== '' && $value !== '') {
                $out[] = [$label, $value];
            }
        }

        return $out;
    }

    public static function source(?string $locale = null): string
    {
        return trv(self::all()['source'], $locale);
    }
}
