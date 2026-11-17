<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sales lead captured by the chatbot gate (first name, surname, mobile).
 */
class Lead extends Model
{
    protected $guarded = ['id'];

    /** Chatbot conversations started by this lead, newest first. */
    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class)->orderByDesc('id');
    }
}
