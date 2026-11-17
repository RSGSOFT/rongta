<?php

namespace App\Livewire\Admin\Faq;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Partners\Options;
use App\Models\FaqTopic;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create (/admin/faq/topics/create) and edit (/admin/faq/topics/{topic}/edit) a FAQ topic. */
class TopicForm extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public ?int $topicId = null;

    public array $form = [];

    public function mount(?FaqTopic $topic = null): void
    {
        $topic = $topic?->exists ? $topic : null;
        $this->topicId = $topic?->id;

        $this->form = [
            'slug' => $topic->slug ?? '',
            'name' => $this->transFill($topic?->name),
            'sort' => $topic->sort ?? ((int) FaqTopic::max('sort') + 1),
        ];
    }

    protected function rules(): array
    {
        return [
            'form.slug' => ['required', 'string', 'max:60', 'regex:'.Options::SLUG_REGEX, Rule::unique('faq_topics', 'slug')->ignore($this->topicId)],
            ...$this->transRules('form.name', max: 160),
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.slug' => 'Код',
            'form.sort' => 'Порядок',
            ...$this->transAttributes('form.name', 'Название темы'),
        ];
    }

    public function save()
    {
        $this->validate();

        $topic = $this->topicId ? FaqTopic::findOrFail($this->topicId) : new FaqTopic;
        $topic->fill([
            'slug' => $this->form['slug'],
            'name' => $this->transClean($this->form['name']),
            'sort' => (int) $this->form['sort'],
        ])->save();

        $this->logSaved($topic);
        session()->flash('status', 'Тема сохранена.');

        return redirect()->route('admin.faq.index');
    }

    public function render(): View
    {
        $title = $this->topicId ? 'Тема: '.trv($this->form['name'], 'ru') : 'Новая тема вопросов';

        return view('livewire.admin.faq.topic-form', ['title' => $title])
            ->layout('components.layouts.admin', ['title' => $title]);
    }
}
