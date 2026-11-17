<?php

namespace App\Livewire\Admin\Partners;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\Partner;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/** /admin/partners: resellers and integrators of the Dealers page */
class Index extends Component
{
    use LogsActivity, RequiresStaff, WithTable;

    protected array $searchable = ['name', 'slug', 'phone', 'telegram'];

    protected array $sortable = ['kind', 'sort', 'updated_at'];

    protected string $defaultSort = 'sort';

    #[Url(as: 'kind', except: '')]
    public string $filterKind = '';

    #[Url(as: 'region', except: '')]
    public string $filterRegion = '';

    /** '' | published | hidden | sample | real */
    #[Url(as: 'status', except: '')]
    public string $filterStatus = '';

    public function togglePublished(int $id): void
    {
        $partner = Partner::findOrFail($id);
        $partner->update(['is_published' => ! $partner->is_published]);

        $this->logActivity($partner->is_published ? 'published' : 'unpublished', $partner, ['is_published' => $partner->is_published]);
    }

    public function delete(int $id): void
    {
        $partner = Partner::findOrFail($id);
        $partner->delete();
        $this->logDeleted($partner);
        session()->flash('status', 'Партнёр удалён.');
    }

    public function render(): View
    {
        $query = Partner::query()
            ->with(['region', 'city'])
            ->when($this->filterValue($this->filterKind), fn ($q, $kind) => $q->where('kind', $kind))
            ->when($this->filterValue($this->filterRegion), fn ($q, $id) => $q->where('region_id', $id))
            ->when($this->filterStatus === 'published', fn ($q) => $q->where('is_published', true))
            ->when($this->filterStatus === 'hidden', fn ($q) => $q->where('is_published', false))
            ->when($this->filterStatus === 'sample', fn ($q) => $q->where('is_placeholder', true))
            ->when($this->filterStatus === 'real', fn ($q) => $q->where('is_placeholder', false));

        return view('livewire.admin.partners.index', [
            'rows' => $this->tableRows($query),
            'regions' => Region::orderBy('sort')->get()->mapWithKeys(fn (Region $r) => [$r->id => $r->tr('name', 'ru')]),
        ])->layout('components.layouts.admin', ['title' => 'Партнёры']);
    }
}
