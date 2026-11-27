<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reseller or a software integrator shown on the Dealers page.
 */
class Partner extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'offers' => 'array',
            'address' => 'array',
            'is_placeholder' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public const KINDS = ['reseller', 'integrator'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeResellers(Builder $query): Builder
    {
        return $query->where('kind', 'reseller');
    }

    public function scopeIntegrators(Builder $query): Builder
    {
        return $query->where('kind', 'integrator');
    }

    /** Partners that sell scales: the ones RSG hands scales to (the "sales" offer, whatever the kind). */
    public function scopeSellers(Builder $query): Builder
    {
        return $query->whereJsonContains('offers', 'sales');
    }

    public function offersSales(): bool
    {
        return in_array('sales', $this->offers ?? [], true);
    }

    public function offersService(): bool
    {
        return in_array('service', $this->offers ?? [], true);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
