<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One spec row. "value" keeps the source shape:
 *   text  {ru, uz, en}
 *   parts {parts: [[{ru,uz,en} label, {ru,uz,en} value], ...]}   (e.g. 15 kg / 30 kg versions)
 *   note  {v: {ru,uz,en}, note: {ru,uz,en}}                      (value with a caveat)
 * The row label is ui('spec.'.$key); the group label is ui('grp.'.$group).
 */
class ModelSpec extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /** Group order used on model and compare pages. */
    public const GROUPS = ['weighing', 'display', 'system', 'printer', 'conn', 'power', 'physical', 'compliance', 'box'];

    public function scaleModel(): BelongsTo
    {
        return $this->belongsTo(ScaleModel::class);
    }

    /** text | parts | note */
    public function shape(): string
    {
        $value = $this->value ?? [];

        return isset($value['parts']) ? 'parts' : (isset($value['v']) ? 'note' : 'text');
    }

    /** Main value as one line of text in a locale (parts joined as "label: value; ..."). */
    public function text(?string $locale = null): string
    {
        $value = $this->value ?? [];

        return match ($this->shape()) {
            'parts' => collect($value['parts'])
                ->map(fn ($part) => trv($part[0] ?? null, $locale).': '.trv($part[1] ?? null, $locale))
                ->implode('; '),
            'note' => trv($value['v'], $locale),
            default => trv($value, $locale),
        };
    }

    /** Caveat attached to the value, or null. */
    public function note(?string $locale = null): ?string
    {
        return $this->shape() === 'note' ? trv($this->value['note'] ?? null, $locale) : null;
    }
}
