<?php

namespace App\Livewire\Admin\Chatbot\Articles;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\KnowledgeArticle;
use App\Services\Chatbot\PriceGuard;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/** /admin/chatbot/articles: the knowledge the bot answers from. */
class Index extends Component
{
    use LogsActivity, RequiresStaff, WithTable;

    protected array $searchable = ['title', 'body', 'tags', 'source_ref'];

    protected array $sortable = ['id', 'updated_at', 'is_published'];

    protected string $defaultSort = 'updated_at';

    protected string $defaultDir = 'desc';

    #[Url(as: 'status', except: '')]
    public string $filterStatus = '';

    #[Url(as: 'source', except: '')]
    public string $filterSource = '';

    /** Why an article cannot be published yet, or null when it can. */
    public static function publishBlocker(KnowledgeArticle $article): ?string
    {
        foreach (['ru', 'uz', 'en'] as $locale) {
            if (trim((string) ($article->title[$locale] ?? '')) === '' || trim((string) ($article->body[$locale] ?? '')) === '') {
                return 'Чтобы опубликовать, заполните заголовок и текст на всех трёх языках (нет: '.strtoupper($locale).').';
            }
        }
        if (PriceGuard::hasAmount(implode(' ', (array) $article->title).' '.implode(' ', (array) $article->body))) {
            return 'На сайте нет цен: уберите сумму из статьи перед публикацией.';
        }

        return null;
    }

    public function togglePublished(int $id): void
    {
        $article = KnowledgeArticle::findOrFail($id);

        if (! $article->is_published && ($blocker = self::publishBlocker($article))) {
            session()->flash('error', $blocker);

            return;
        }

        $article->update(['is_published' => ! $article->is_published]);
        $this->logSaved($article);
        session()->flash('status', $article->is_published ? 'Статья опубликована: бот может по ней отвечать.' : 'Статья снята с публикации.');
    }

    public function delete(int $id): void
    {
        $article = KnowledgeArticle::findOrFail($id);
        $article->delete();

        $this->logDeleted($article);
        session()->flash('status', 'Статья удалена.');
    }

    public function render(): View
    {
        $query = KnowledgeArticle::query()
            ->with(['scaleModel', 'family'])
            ->when($this->filterStatus === 'published', fn ($q) => $q->where('is_published', true))
            ->when($this->filterStatus === 'draft', fn ($q) => $q->where('is_published', false))
            ->when($this->filterValue($this->filterSource), fn ($q, $source) => $q->where('source', $source));

        return view('livewire.admin.chatbot.articles-index', [
            'rows' => $this->tableRows($query),
            'drafts' => KnowledgeArticle::where('is_published', false)->count(),
        ])->layout('components.layouts.admin', ['title' => 'База ответов']);
    }
}
