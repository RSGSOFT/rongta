<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A downloadable file for one model: manual | software | sdk.
 */
class Document extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'size_label' => 'array',
            'released_on' => 'date',
            'is_placeholder' => 'boolean',
        ];
    }

    public const TYPES = ['manual', 'software', 'sdk'];

    public function scaleModel(): BelongsTo
    {
        return $this->belongsTo(ScaleModel::class);
    }
}
