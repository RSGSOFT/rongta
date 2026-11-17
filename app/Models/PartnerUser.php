<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Partner-area login (guard "partner"). Signs in with "login" + password:
 * Auth::guard('partner')->attempt(['login' => ..., 'password' => ..., 'is_active' => true]).
 */
class PartnerUser extends Authenticatable
{
    public const TYPES = ['reseller', 'integrator'];

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function isReseller(): bool
    {
        return $this->type === 'reseller';
    }

    public function isIntegrator(): bool
    {
        return $this->type === 'integrator';
    }
}
