<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\PartnerPointFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One line of the "For partners" page: an advantage or a term of becoming a partner.
 * Entered by staff; the page hides a block that has no published lines. Never carries a price.
 */
class PartnerPoint extends Model
{
    /** @use HasFactory<PartnerPointFactory> */
    use HasFactory, HasTranslations;

    public const KINDS = ['advantage', 'term'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
