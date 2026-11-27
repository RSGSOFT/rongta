<?php

namespace App\Models;

use Database\Factories\SerialBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One upload of imported scales' serial numbers: who loaded which file, and what was skipped. */
class SerialBatch extends Model
{
    /** @use HasFactory<SerialBatchFactory> */
    use HasFactory;

    /** How many skipped rows are kept with the batch for the report. */
    public const SKIPPED_KEPT = 200;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['skipped' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(SerialNumber::class);
    }
}
