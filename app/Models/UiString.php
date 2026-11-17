<?php

namespace App\Models;

use App\Support\UiStrings;
use Illuminate\Database\Eloquent\Model;

/** One interface string in three languages. Read through ui('key'). */
class UiString extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(fn () => UiStrings::flush());
        static::deleted(fn () => UiStrings::flush());
    }
}
