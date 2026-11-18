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
            'seo_title' => 'array',
            'seo_description' => 'array',
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

    /**
     * Title for search results in a language: the SEO title written for exactly that language,
     * else the model name. No fallback to another language: a Russian title on the Uzbek page
     * would be worse than the name.
     */
    public function seoTitle(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return trim((string) ($this->seo_title[$locale] ?? '')) ?: $this->name;
    }

    /** Search result description in a language, else the page's own intro or headline. */
    public function seoDescription(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return trim((string) ($this->seo_description[$locale] ?? '')) ?: ($this->tr('intro', $locale) ?: $this->tr('headline', $locale));
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
