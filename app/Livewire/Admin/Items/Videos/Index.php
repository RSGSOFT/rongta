<?php

namespace App\Livewire\Admin\Items\Videos;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\Family;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    use LogsActivity, RequiresStaff, WithTable;

    protected array $searchable = ['title', 'slug', 'url'];

    protected array $sortable = ['sort', 'updated_at'];

    protected string $defaultSort = 'sort';

    #[Url(as: 'family', except: '')]
    public string $filterFamily = '';

    public function delete(int $id): void
    {
        $video = Video::findOrFail($id);
        $video->delete();

        $this->logDeleted($video);
        session()->flash('status', 'Видео удалено.');
    }

    public function render(): View
    {
        $query = Video::query()
            ->with('family')
            ->when($this->filterValue($this->filterFamily), fn ($q, $id) => $q->where('family_id', $id));

        return view('livewire.admin.items.videos-index', [
            'rows' => $this->tableRows($query),
            'families' => Family::orderBy('sort')->get()->mapWithKeys(fn ($f) => [$f->id => $f->tr('name', 'ru')]),
        ])->layout('components.layouts.admin', ['title' => 'Видео']);
    }
}
