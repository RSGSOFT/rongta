<?php

namespace App\Models;

use Database\Factories\SerialNumberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** One imported scale: its serial number, model and import date. An item of a SerialBatch. */
class SerialNumber extends Model
{
    /** @use HasFactory<SerialNumberFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['imported_on' => 'date'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SerialBatch::class, 'serial_batch_id');
    }

    public function scaleModel(): BelongsTo
    {
        return $this->belongsTo(ScaleModel::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SerialMovement::class)->orderBy('happened_on')->orderBy('id');
    }

    /** Warranty length in months: one period for every model, set by an admin (12 by default). */
    public static function warrantyMonths(): int
    {
        $months = (int) setting('warranty_months', 12);

        return $months >= 1 && $months <= 120 ? $months : 12;
    }

    /** The warranty starts on the day RSG first handed the scale to a dealer; null until that is recorded. */
    public function warrantyStart(): ?Carbon
    {
        $first = $this->movements->firstWhere('type', SerialMovement::DEALER);

        return $first?->happened_on->copy();
    }

    /** The last day of the warranty. */
    public function warrantyEnd(): ?Carbon
    {
        return $this->warrantyStart()?->addMonthsNoOverflow(self::warrantyMonths())->subDay();
    }

    /** 'none' (not handed over yet), 'active' or 'expired'. */
    public function warrantyState(): string
    {
        $end = $this->warrantyEnd();

        return match (true) {
            $end === null => 'none',
            $end->endOfDay()->isPast() => 'expired',
            default => 'active',
        };
    }

    /** "rt 1530-0042 " => "RT1530-0042": how a serial number is stored and compared. */
    public static function normalize(string $serial): string
    {
        return mb_strtoupper(preg_replace('/[\s\x{00A0}]+/u', '', $serial));
    }
}
