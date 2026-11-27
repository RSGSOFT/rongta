<?php

namespace App\Models;

use Database\Factories\SerialMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One step in the life of an imported scale after the import. The handover to a dealer starts the warranty. */
class SerialMovement extends Model
{
    /** @use HasFactory<SerialMovementFactory> */
    use HasFactory;

    public const DEALER = 'dealer';

    /** Type => Russian label for the admin panel. */
    public const TYPES = [
        self::DEALER => 'Передан дилеру',
        'service_in' => 'Принят в сервис',
        'service_done' => 'Отремонтирован',
        'service_out' => 'Возвращён владельцу',
    ];

    public const SERVICE_TYPES = ['service_in', 'service_done', 'service_out'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['happened_on' => 'date'];
    }

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function serviceCase(): BelongsTo
    {
        return $this->belongsTo(ServiceCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The partner's cabinet account that recorded a service step; null when RSG staff did. */
    public function cabinetAccount(): BelongsTo
    {
        return $this->belongsTo(CabinetAccount::class);
    }

    public function label(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
