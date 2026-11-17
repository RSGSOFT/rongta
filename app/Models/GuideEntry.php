<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Buying guide: one row per family. "cost" is a running-cost level in words, not money.
 */
class GuideEntry extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'does' => 'array',
            'fits' => 'array',
            'limit' => 'array',
            'cost' => 'array',
            'peak' => 'array',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
