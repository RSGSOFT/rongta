<?php

namespace App\Livewire\Admin\Chatbot\Concerns;

use App\Models\ChatMessage;
use App\Models\KnowledgeArticle;

/**
 * One-click "turn into an article" for a question the bot could not answer: makes an
 * unpublished draft titled with the question and opens it for editing.
 * The component must also use LogsActivity.
 */
trait TurnsQuestionsIntoArticles
{
    public function makeArticle(int $messageId)
    {
        $question = ChatMessage::with('conversation')->where('role', ChatMessage::ROLE_USER)->findOrFail($messageId);

        if (! $question->knowledge_article_id) {
            $locale = in_array($question->conversation->locale, ['ru', 'uz', 'en'], true) ? $question->conversation->locale : 'ru';
            $title = ['ru' => '', 'uz' => '', 'en' => ''];
            $title[$locale] = mb_substr(trim(preg_replace('/\s+/u', ' ', $question->content) ?? ''), 0, 200);

            $article = KnowledgeArticle::create([
                'title' => $title,
                'body' => ['ru' => '', 'uz' => '', 'en' => ''],
                'is_published' => false,
                'source' => 'conversation',
                'created_by' => auth()->id(),
            ]);
            $question->update(['knowledge_article_id' => $article->id]);
            // No longer an open question: it has a draft waiting for review.
            $question->conversation()->where('unanswered_count', '>', 0)->decrement('unanswered_count');
            $this->logActivity('created', $article, ['from_question' => $question->id]);
        }

        session()->flash('status', 'Черновик создан из вопроса. Напишите ответ на трёх языках и опубликуйте.');

        return redirect()->route('admin.chatbot.articles.edit', $question->knowledge_article_id);
    }

    /** The question needs no article (spam, off-topic, already covered). */
    public function dismissQuestion(int $messageId): void
    {
        $question = ChatMessage::where('role', ChatMessage::ROLE_USER)->findOrFail($messageId);
        $question->update(['is_unanswered' => false]);
        $question->conversation()->where('unanswered_count', '>', 0)->decrement('unanswered_count');

        $this->logActivity('updated', $question, ['is_unanswered' => false]);
        session()->flash('status', 'Вопрос убран из списка.');
    }
}
