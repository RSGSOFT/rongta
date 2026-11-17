<?php

namespace App\Livewire\Admin\Chatbot\Articles;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\ChatMessage;
use App\Models\Family;
use App\Models\KnowledgeArticle;
use App\Models\ScaleModel;
use App\Services\Chatbot\PriceGuard;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create (/admin/chatbot/articles/create) and edit (/admin/chatbot/articles/{article}/edit). */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public ?int $articleId = null;

    public array $form = [];

    public function mount(?KnowledgeArticle $article = null): void
    {
        $article = $article?->exists ? $article : null;
        $this->articleId = $article?->id;

        $this->form = [
            'title' => $this->transFill($article?->title),
            'body' => $this->transFill($article?->body),
            'scale_model_id' => $article->scale_model_id ?? '',
            'family_id' => $article->family_id ?? '',
            'tags' => implode(', ', $article->tags ?? []),
            'is_published' => (bool) ($article->is_published ?? false),
        ];
    }

    protected function rules(): array
    {
        $noPrice = function (string $attribute, mixed $value, Closure $fail) {
            if (PriceGuard::hasAmount((string) $value)) {
                $fail('На сайте нет цен: уберите сумму из поля «:attribute».');
            }
        };
        // A published article is shown to visitors in all three languages.
        $publish = (bool) ($this->form['is_published'] ?? false);
        $other = [$publish ? 'required' : 'nullable', 'string'];

        return [
            'form.title' => ['array'],
            'form.title.ru' => ['required', 'string', 'max:200', $noPrice],
            'form.title.uz' => [...$other, 'max:200', $noPrice],
            'form.title.en' => [...$other, 'max:200', $noPrice],
            'form.body' => ['array'],
            'form.body.ru' => [$publish ? 'required' : 'nullable', 'string', 'max:20000', $noPrice],
            'form.body.uz' => [...$other, 'max:20000', $noPrice],
            'form.body.en' => [...$other, 'max:20000', $noPrice],
            'form.scale_model_id' => ['nullable', 'integer', Rule::exists('scale_models', 'id')],
            'form.family_id' => ['nullable', 'integer', Rule::exists('families', 'id')],
            'form.tags' => ['nullable', 'string', 'max:300'],
            'form.is_published' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            ...$this->russianMessages(),
            'form.title.uz.required' => 'Чтобы опубликовать, заполните поле «:attribute».',
            'form.title.en.required' => 'Чтобы опубликовать, заполните поле «:attribute».',
            'form.body.*.required' => 'Чтобы опубликовать, заполните поле «:attribute».',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            ...$this->transAttributes('form.title', 'Заголовок'),
            ...$this->transAttributes('form.body', 'Текст'),
            'form.scale_model_id' => 'Модель',
            'form.family_id' => 'Семейство',
            'form.tags' => 'Метки',
        ];
    }

    public function save()
    {
        $this->validate();

        $article = $this->articleId ? KnowledgeArticle::findOrFail($this->articleId) : new KnowledgeArticle(['source' => 'manual', 'created_by' => auth()->id()]);
        $model = $this->form['scale_model_id'] ? ScaleModel::find($this->form['scale_model_id']) : null;

        $article->fill([
            'title' => $this->transClean($this->form['title']),
            'body' => $this->transClean($this->form['body']),
            'scale_model_id' => $model?->id,
            // A model implies its family.
            'family_id' => $model?->family_id ?? ($this->form['family_id'] ?: null),
            'tags' => collect(explode(',', (string) $this->form['tags']))->map(fn ($t) => mb_strtolower(trim($t)))
                ->filter()->unique()->values()->all(),
            'is_published' => (bool) $this->form['is_published'],
        ])->save();

        $this->logSaved($article);
        session()->flash('status', $article->is_published
            ? 'Статья сохранена и опубликована: бот может по ней отвечать.'
            : 'Черновик сохранён. Бот увидит статью после публикации.');

        return redirect()->route('admin.chatbot.articles.index');
    }

    public function render(): View
    {
        $title = $this->articleId ? 'Статья' : 'Новая статья';
        $article = $this->articleId ? KnowledgeArticle::find($this->articleId) : null;

        return view('livewire.admin.chatbot.articles-form', [
            'title' => $title,
            'article' => $article,
            'question' => $article ? ChatMessage::with('conversation')->where('knowledge_article_id', $article->id)->oldest('id')->first() : null,
            'models' => ScaleModel::orderBy('sort')->pluck('name', 'id'),
            'families' => Family::orderBy('sort')->get()->mapWithKeys(fn ($f) => [$f->id => $f->tr('name', 'ru')]),
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
