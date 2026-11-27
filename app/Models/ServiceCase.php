<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\ServiceCaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of the service list: a service or repair job and what RSG charges for it, in soʻm.
 * Owner-approved exception to the "no prices" rule (2026-11-24): service fees only, never scale prices.
 * The amount is entered by staff; a job without an amount is not shown on the site.
 */
class ServiceCase extends Model
{
    /** @use HasFactory<ServiceCaseFactory> */
    use HasFactory, HasTranslations;

    public const MAX_AMOUNT = 100000000;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'note' => 'array',
            'amount' => 'integer',
            'is_from' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /** The scale family the job belongs to; none means it applies to every scale. */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** Jobs the public service list shows: published and with an amount. */
    public function scopeListed(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereNotNull('amount');
    }

    public function isListed(): bool
    {
        return $this->is_published && $this->amount !== null;
    }

    /** 150000 => "150 000" with non-breaking spaces. */
    public static function spaced(int $amount): string
    {
        return number_format($amount, 0, '', "\u{00A0}");
    }

    /** "150 000 soʻm" or "from 150 000 soʻm" in the given (or current) language; empty without an amount. */
    public function amountLabel(?string $locale = null): string
    {
        if ($this->amount === null) {
            return '';
        }

        return ui($this->is_from ? 'svc.amount.from' : 'svc.amount', ['amount' => self::spaced($this->amount)], $locale);
    }
}
