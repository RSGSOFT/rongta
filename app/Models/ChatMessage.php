<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One chat message. Visitor rows (role "user") carry the "could not answer" flag;
 * assistant rows carry the reply status and the Claude token usage.
 */
class ChatMessage extends Model
{
    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_unanswered' => 'boolean',
            'tool_calls' => 'array',
        ];
    }

    /** Questions the bot could not answer and nobody has turned into an article yet. */
    public function scopeOpenQuestions(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_USER)
            ->where('is_unanswered', true)
            ->whereNull('knowledge_article_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'knowledge_article_id');
    }
}
