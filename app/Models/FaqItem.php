<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaqItem extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'question' => 'array',
            'answer' => 'array',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(FaqTopic::class, 'faq_topic_id');
    }
}
