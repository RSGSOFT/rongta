<?php

namespace App\Livewire\Admin\Chatbot\Conversations;

use App\Livewire\Admin\Chatbot\Concerns\TurnsQuestionsIntoArticles;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\ChatConversation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** /admin/chatbot/conversations/{conversation}: the lead and the full exchange. */
class Show extends Component
{
    use LogsActivity, RequiresStaff, TurnsQuestionsIntoArticles;

    #[Locked]
    public int $conversationId;

    public function mount(ChatConversation $conversation): void
    {
        $this->conversationId = $conversation->id;
    }

    public function render(): View
    {
        $conversation = ChatConversation::with(['lead', 'messages.article'])->findOrFail($this->conversationId);
        $assistant = $conversation->messages->where('role', 'assistant');

        return view('livewire.admin.chatbot.conversations-show', [
            'conversation' => $conversation,
            'usage' => [
                'input' => (int) $assistant->sum('input_tokens'),
                'output' => (int) $assistant->sum('output_tokens'),
                'cache_read' => (int) $assistant->sum('cache_read_tokens'),
                'cache_write' => (int) $assistant->sum('cache_write_tokens'),
            ],
        ])->layout('components.layouts.admin', ['title' => 'Диалог']);
    }
}
