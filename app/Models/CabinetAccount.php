<?php

namespace App\Models;

use Database\Factories\CabinetAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A dealer's or service centre's login to the partner cabinet. Guard "cabinet"; never RSG staff.
 * It works only when an admin has approved it and linked it to a partner; what it may do
 * follows that partner's offers: "sales" opens the dealer section, "service" the service one.
 */
class CabinetAccount extends Authenticatable
{
    /** @use HasFactory<CabinetAccountFactory> */
    use HasFactory;

    /** Status => Russian label for the admin panel. */
    public const STATUSES = [
        'pending' => 'Ждёт одобрения',
        'approved' => 'Одобрен',
        'blocked' => 'Заблокирован',
    ];

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'wants_sales' => 'boolean',
            'wants_service' => 'boolean',
            'decided_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** Approved and linked to a partner: the only state in which the cabinet opens. */
    public function canEnter(): bool
    {
        return $this->status === 'approved' && $this->partner !== null;
    }

    public function sells(): bool
    {
        return $this->canEnter() && $this->partner->offersSales();
    }

    public function services(): bool
    {
        return $this->canEnter() && $this->partner->offersService();
    }
}
