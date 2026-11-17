<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqTopic extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(FaqItem::class)->orderBy('sort');
    }
}
