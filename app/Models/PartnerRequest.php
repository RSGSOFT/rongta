<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Become a partner" form submission.
 */
class PartnerRequest extends Model
{
    protected $guarded = ['id'];

    public const STATUSES = ['new', 'in_progress', 'done', 'rejected'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
