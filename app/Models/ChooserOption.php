<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * "Which scale do you need?" answer on the home page: shop | supermarket | market.
 */
class ChooserOption extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'why' => 'array',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** Recommended models. Pivot: note ({ru,uz,en} array), sort. Read a note with trv($model->pivot->note). */
    public function scaleModels(): BelongsToMany
    {
        return $this->belongsToMany(ScaleModel::class, 'chooser_option_scale_model')
            ->using(ChooserOptionScaleModel::class)
            ->withPivot(['note', 'sort'])
            ->orderBy('chooser_option_scale_model.sort');
    }
}
