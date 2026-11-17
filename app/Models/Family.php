<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Scale family: label | ai | comm.
 */
class Family extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'line' => 'array',
        ];
    }

    public function scaleModels(): HasMany
    {
        return $this->hasMany(ScaleModel::class)->orderBy('sort');
    }

    /**
     * The AURORA AI family, shown on dark sections. Decided by its models (is_aurora), not by
     * the slug, so renaming the slug in the admin panel keeps the styling.
     */
    public function isAurora(): bool
    {
        if (array_key_exists('has_aurora', $this->attributes)) {
            return (bool) $this->attributes['has_aurora'];
        }

        return $this->scaleModels()->where('is_aurora', true)->exists();
    }

    /** Loads the isAurora() answer with the families, instead of one query per family. */
    public function scopeWithAuroraFlag(Builder $query): void
    {
        $query->withExists(['scaleModels as has_aurora' => fn (Builder $models) => $models->where('is_aurora', true)]);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class)->orderBy('sort');
    }

    public function guideEntry(): HasOne
    {
        return $this->hasOne(GuideEntry::class);
    }

    public function chooserOptions(): HasMany
    {
        return $this->hasMany(ChooserOption::class)->orderBy('sort');
    }
}
