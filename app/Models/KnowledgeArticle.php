<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Knowledge the chatbot may answer from, written or reviewed by RSG staff.
 * Drafts made from a PDF manual or from an unanswered question start unpublished.
 */
class KnowledgeArticle extends Model
{
    use HasTranslations;

    public const SOURCES = ['manual', 'pdf', 'conversation'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
            'tags' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scaleModel(): BelongsTo
    {
        return $this->belongsTo(ScaleModel::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
