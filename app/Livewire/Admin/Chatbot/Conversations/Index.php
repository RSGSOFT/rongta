<?php

namespace App\Livewire\Admin\Chatbot\Conversations;

use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

/** /admin/chatbot/conversations: every chat, newest first, with its lead. */
class Index extends Component
{
    use RequiresStaff, WithTable;

    protected array $sortable = ['id', 'last_message_at', 'unanswered_count'];

    protected string $defaultSort = 'id';

    protected string $defaultDir = 'desc';

    #[Url(as: 'only', except: '')]
    public string $filterOnly = '';

    #[Url(as: 'lang', except: '')]
    public string $filterLocale = '';

    public function render(): View
    {
        $term = trim($this->search);
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($term)).'%';

        $query = ChatConversation::query()
            ->with('lead')
            ->withCount('messages')
            ->withSum('messages as tokens_in', 'input_tokens')
            ->withSum('messages as tokens_out', 'output_tokens')
            ->when($this->filterOnly === 'unanswered', fn (Builder $q) => $q->where('unanswered_count', '>', 0))
            ->when($this->filterValue($this->filterLocale), fn (Builder $q, $locale) => $q->where('locale', $locale))
            ->when($term !== '', fn (Builder $q) => $q->whereHas('lead', fn (Builder $lead) => $lead
                ->whereRaw('LOWER(first_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                ->orWhere('phone', 'like', '%'.preg_replace('/[^\d+]/', '', $term).'%')));

        return view('livewire.admin.chatbot.conversations-index', [
            'rows' => $this->applySort($query)->paginate($this->perPage),
            'open' => ChatMessage::openQuestions()->count(),
        ])->layout('components.layouts.admin', ['title' => 'Диалоги']);
    }
}
