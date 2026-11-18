<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Support\SearchTools;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One scale model (table scale_models). "Model" is taken by Eloquent, hence the name.
 */
class ScaleModel extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    /** Published pages that change are reported to IndexNow engines (when enabled in admin settings). */
    protected static function booted(): void
    {
        static::saved(fn (ScaleModel $model) => SearchTools::modelChanged($model));
    }

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

    /**
     * A free page address made from a model name: "RLS1000/RLS1100A" → "rls1000-rls1100a".
     * Taken addresses get "-2", "-3"… appended; $ignoreId is the model being edited.
     */
    public static function slugFrom(string $name, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug(str_replace(['/', '\\', '+', '.'], ' ', $name)), 72, '') ?: 'model';
        $base = rtrim($base, '-');

        $slug = $base;
        for ($n = 2; static::where('slug', $slug)->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))->exists(); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
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
