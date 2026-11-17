<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/** Pivot between chooser options and recommended models; "note" is a {ru,uz,en} array. */
class ChooserOptionScaleModel extends Pivot
{
    protected $table = 'chooser_option_scale_model';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['note' => 'array'];
    }
}
