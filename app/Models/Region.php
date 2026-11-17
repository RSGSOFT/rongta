<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'short' => 'array',
        ];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class)->orderBy('sort');
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class)->orderBy('sort');
    }

    public function partnerRequests(): HasMany
    {
        return $this->hasMany(PartnerRequest::class);
    }
}
