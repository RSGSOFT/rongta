<?php

namespace App\Models\Concerns;

/**
 * For models whose trilingual text lives in JSON columns shaped {ru, uz, en}
 * (cast to array). Usage: $model->tr('name') or $model->tr('name', 'uz').
 */
trait HasTranslations
{
    public function tr(string $attribute, ?string $locale = null): string
    {
        return trv($this->getAttribute($attribute), $locale);
    }
}
