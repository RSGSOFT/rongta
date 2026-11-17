<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelFeature extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'text' => 'array',
        ];
    }

    public function scaleModel(): BelongsTo
    {
        return $this->belongsTo(ScaleModel::class);
    }
}
