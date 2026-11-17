<?php

namespace App\Livewire\Admin\Chatbot\Conversations;

use App\Livewire\Admin\Chatbot\Concerns\TurnsQuestionsIntoArticles;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\ChatMessage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/** /admin/chatbot/conversations/unanswered: questions the bot could not answer, waiting for an article. */
class Unanswered extends Component
{
    use LogsActivity, RequiresStaff, TurnsQuestionsIntoArticles, WithPagination;

    protected bool $adminOnly = true;

    public function render(): View
    {
        return view('livewire.admin.chatbot.conversations-unanswered', [
            'rows' => ChatMessage::openQuestions()->with('conversation.lead')->latest('id')->paginate(25),
        ])->layout('components.layouts.admin', ['title' => 'Вопросы без ответа']);
    }
}
