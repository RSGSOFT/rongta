<?php

namespace App\Models;

use Database\Factories\SerialDetailRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A service centre asks RSG for the full history of a scale it has not serviced itself.
 * "granted" opens that scale's history to the whole centre.
 */
class SerialDetailRequest extends Model
{
    /** @use HasFactory<SerialDetailRequestFactory> */
    use HasFactory;

    public const GRANTED = 'granted';

    /** Status => Russian label for the admin panel. */
    public const STATUSES = [
        'new' => 'Новый',
        self::GRANTED => 'Доступ открыт',
        'refused' => 'Отказано',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function cabinetAccount(): BelongsTo
    {
        return $this->belongsTo(CabinetAccount::class);
    }

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
