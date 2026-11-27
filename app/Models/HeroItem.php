<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\HeroItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A sample product weighed on the home page hero: the scale display and the printed label
 * show its weight, price per kilogram and total. One published item is picked at random
 * for each page load.
 *
 * Owner-approved exception to the "no prices" rule (2026-11-23): these numbers are an
 * illustration of what a scale shows, never a product price, and no currency is printed.
 */
class HeroItem extends Model
{
    /** @use HasFactory<HeroItemFactory> */
    use HasFactory, HasTranslations;

    public const MIN_WEIGHT_G = 40;

    public const MAX_WEIGHT_G = 30000;

    public const MIN_UNIT_PRICE = 10;

    public const MAX_UNIT_PRICE = 5000000;

    /** Shown on the label when an item has no brand of its own. */
    public const DEFAULT_BRAND = 'Rongta Uzbekistan';

    /** Digit cells of the three display windows: 30.000, 5000000 and 150000000 all fit. */
    public const WEIGHT_CELLS = 5;

    public const UNIT_CELLS = 7;

    public const TOTAL_CELLS = 9;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'weight_g' => 'integer',
            'unit_price' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** The illustration used while staff have published no items: the original oranges label. */
    public static function fallback(): static
    {
        return new static([
            'name' => ['ru' => ui('lbl.product', [], 'ru'), 'uz' => ui('lbl.product', [], 'uz'), 'en' => ui('lbl.product', [], 'en')],
            'weight_g' => 1250,
            'unit_price' => 12000,
        ]);
    }

    public function brandLine(): string
    {
        return filled($this->brand) ? (string) $this->brand : self::DEFAULT_BRAND;
    }

    /** Weight times price per kilogram, rounded to a whole number as a scale does. */
    public function total(): int
    {
        return (int) round($this->weight_g * $this->unit_price / 1000);
    }

    /** Weight in kilograms with three decimals: "1.250". */
    public function weightKg(): string
    {
        return number_format($this->weight_g / 1000, 3, '.', '');
    }

    /** A whole number with non-breaking thousands spaces: "12 000". */
    public static function spaced(int $number): string
    {
        return number_format($number, 0, '.', "\u{00A0}");
    }

    /**
     * Right-aligned strings for the seven-segment windows (the decimal point shares a cell).
     *
     * @return array{weight: string, unit: string, total: string}
     */
    public function readout(): array
    {
        return [
            'weight' => str_pad($this->weightKg(), self::WEIGHT_CELLS + 1, ' ', STR_PAD_LEFT),
            'unit' => str_pad((string) $this->unit_price, self::UNIT_CELLS, ' ', STR_PAD_LEFT),
            'total' => str_pad((string) $this->total(), self::TOTAL_CELLS, ' ', STR_PAD_LEFT),
        ];
    }
}
