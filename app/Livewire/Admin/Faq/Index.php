<?php

namespace App\Livewire\Admin\Faq;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\FaqItem;
use App\Models\FaqTopic;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/** /admin/faq: topics of the knowledge base FAQ, each with its questions. */
class Index extends Component
{
    use LogsActivity, RequiresStaff;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function deleteTopic(int $id): void
    {
        $topic = FaqTopic::withCount('items')->findOrFail($id);

        if ($topic->items_count > 0) {
            session()->flash('error', 'Нельзя удалить тему, в которой есть вопросы. Сначала перенесите или удалите вопросы.');

            return;
        }

        $topic->delete();
        $this->logDeleted($topic);
        session()->flash('status', 'Тема удалена.');
    }

    public function deleteItem(int $id): void
    {
        $item = FaqItem::findOrFail($id);
        $item->delete();

        $this->logDeleted($item);
        session()->flash('status', 'Вопрос удалён.');
    }

    public function render(): View
    {
        $term = mb_strtolower(trim($this->search));

        $topics = FaqTopic::with('items')->orderBy('sort')->orderBy('id')->get();
        if ($term !== '') {
            $topics = $topics
                ->each(fn (FaqTopic $topic) => $topic->setRelation('items', $topic->items->filter(
                    fn (FaqItem $item) => str_contains(mb_strtolower(implode(' ', array_merge((array) $item->question, (array) $item->answer))), $term)
                )->values()))
                ->filter(fn (FaqTopic $topic) => $topic->items->isNotEmpty())
                ->values();
        }

        return view('livewire.admin.faq.index', [
            'topics' => $topics,
            'searching' => $term !== '',
        ])->layout('components.layouts.admin', ['title' => 'Вопросы и ответы']);
    }
}
