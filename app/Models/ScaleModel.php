<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One scale model (table scale_models). "Model" is taken by Eloquent, hence the name.
 */
class ScaleModel extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'illustration' => 'array',
            'conn' => 'array',
            'type' => 'array',
            'headline' => 'array',
            'intro' => 'array',
            'gallery' => 'array',
            'drawing_views' => 'array',
            'is_aurora' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(ModelFeature::class)->orderBy('sort');
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ModelSpec::class)->orderBy('sort');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->orderBy('sort');
    }

    public function chooserOptions(): BelongsToMany
    {
        return $this->belongsToMany(ChooserOption::class, 'chooser_option_scale_model')
            ->withPivot(['note', 'sort']);
    }
}
