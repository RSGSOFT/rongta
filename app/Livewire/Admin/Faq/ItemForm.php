<?php

namespace App\Livewire\Admin\Faq;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\FaqItem;
use App\Models\FaqTopic;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create (/admin/faq/items/create?topic=ID) and edit (/admin/faq/items/{item}/edit) a question. */
class ItemForm extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public ?int $itemId = null;

    public array $form = [];

    public function mount(?FaqItem $item = null): void
    {
        $item = $item?->exists ? $item : null;
        $this->itemId = $item?->id;

        $preset = request()->query('topic');
        $topicId = $item->faq_topic_id ?? (is_numeric($preset) && FaqTopic::whereKey($preset)->exists() ? (int) $preset : null);

        $this->form = [
            'faq_topic_id' => (string) ($topicId ?? ''),
            'question' => $this->transFill($item?->question),
            'answer' => $this->transFill($item?->answer),
            'sort' => $item->sort ?? ((int) FaqItem::when($topicId, fn ($q) => $q->where('faq_topic_id', $topicId))->max('sort') + 1),
        ];
    }

    protected function rules(): array
    {
        return [
            'form.faq_topic_id' => ['required', 'integer', Rule::exists('faq_topics', 'id')],
            ...$this->transRules('form.question', max: 300),
            ...$this->transRules('form.answer', max: 5000),
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.faq_topic_id' => 'Тема',
            'form.sort' => 'Порядок',
            ...$this->transAttributes('form.question', 'Вопрос'),
            ...$this->transAttributes('form.answer', 'Ответ'),
        ];
    }

    public function save()
    {
        $this->validate();

        $item = $this->itemId ? FaqItem::findOrFail($this->itemId) : new FaqItem;
        $item->fill([
            'faq_topic_id' => (int) $this->form['faq_topic_id'],
            'question' => $this->transClean($this->form['question']),
            'answer' => $this->transClean($this->form['answer']),
            'sort' => (int) $this->form['sort'],
        ])->save();

        $this->logSaved($item);
        session()->flash('status', 'Вопрос сохранён.');

        return redirect()->route('admin.faq.index');
    }

    public function render(): View
    {
        $title = $this->itemId ? 'Вопрос: '.trv($this->form['question'], 'ru') : 'Новый вопрос';

        return view('livewire.admin.faq.item-form', [
            'title' => $title,
            'topics' => FaqTopic::orderBy('sort')->get()->mapWithKeys(fn (FaqTopic $t) => [$t->id => $t->tr('name', 'ru')]),
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
