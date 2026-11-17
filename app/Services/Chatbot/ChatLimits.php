<?php

namespace App\Services\Chatbot;

use App\Models\ChatConversation;
use App\Models\ChatMessage;

/** Daily message limits per lead and per IP address, and the message length limit. */
class ChatLimits
{
    /** Returns the ui string key of the limit that was hit, or null when the message may be sent. */
    public function check(ChatConversation $conversation, string $text): ?string
    {
        if (mb_strlen($text) > ChatbotSettings::int('max_message_chars')) {
            return 'bot.err.long';
        }

        $today = ChatMessage::query()
            ->where('role', ChatMessage::ROLE_USER)
            ->where('created_at', '>=', now()->startOfDay());

        $byLead = (clone $today)->whereHas('conversation', fn ($q) => $q->where('lead_id', $conversation->lead_id))->count();
        if ($byLead >= ChatbotSettings::int('lead_daily_limit')) {
            return 'bot.err.limit';
        }

        if ($conversation->ip) {
            $byIp = (clone $today)->whereHas('conversation', fn ($q) => $q->where('ip', $conversation->ip))->count();
            if ($byIp >= ChatbotSettings::int('ip_daily_limit')) {
                return 'bot.err.limit';
            }
        }

        return null;
    }
}
