<?php

namespace App\Livewire\Admin\Items\Documents;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\Document;
use App\Models\ScaleModel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    use LogsActivity, RequiresStaff, WithTable;

    protected array $searchable = ['title', 'slug', 'version', 'format'];

    protected array $sortable = ['type', 'version', 'released_on', 'sort', 'updated_at'];

    protected string $defaultSort = 'sort';

    #[Url(as: 'model', except: '')]
    public string $filterModel = '';

    #[Url(as: 'type', except: '')]
    public string $filterType = '';

    /** '' | missing | present */
    #[Url(as: 'file', except: '')]
    public string $filterFile = '';

    public function delete(int $id): void
    {
        $document = Document::findOrFail($id);
        $document->delete();

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $this->logDeleted($document);
        session()->flash('status', 'Документ удалён.');
    }

    public function render(): View
    {
        $query = Document::query()
            ->with('scaleModel')
            ->when($this->filterValue($this->filterModel), fn ($q, $id) => $q->where('scale_model_id', $id))
            ->when($this->filterValue($this->filterType), fn ($q, $type) => $q->where('type', $type))
            ->when($this->filterFile === 'missing', fn ($q) => $q->where(fn ($w) => $w->whereNull('file_path')->orWhere('file_path', '')))
            ->when($this->filterFile === 'present', fn ($q) => $q->whereNotNull('file_path')->where('file_path', '!=', ''));

        return view('livewire.admin.items.documents-index', [
            'rows' => $this->tableRows($query),
            'models' => ScaleModel::orderBy('sort')->pluck('name', 'id'),
        ])->layout('components.layouts.admin', ['title' => 'Документы и файлы']);
    }
}
