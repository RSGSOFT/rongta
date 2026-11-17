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

/** /admin/partners: resellers and integrators of the Dealers page, with tools to retire the sample entries. */
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
        $partner = Partner::withCount('users')->findOrFail($id);

        if ($partner->users_count > 0) {
            session()->flash('error', 'У этого партнёра есть доступы в кабинет. Сначала удалите их или отвяжите от партнёра (раздел «Доступы партнёров»).');

            return;
        }

        $partner->delete();
        $this->logDeleted($partner);
        session()->flash('status', 'Партнёр удалён.');
    }

    /** Take every sample entry off the public site in one step (they stay in the list). */
    public function hideSamples(): void
    {
        $count = 0;
        foreach (Partner::where('is_placeholder', true)->where('is_published', true)->get() as $partner) {
            $partner->update(['is_published' => false]);
            $this->logActivity('unpublished', $partner, ['is_published' => false]);
            $count++;
        }

        session()->flash('status', $count ? "Образцы скрыты с сайта: {$count}." : 'Опубликованных образцов нет.');
    }

    /** Delete every sample entry. Samples linked to a partner login are kept. */
    public function deleteSamples(): void
    {
        $deleted = 0;
        $kept = 0;
        foreach (Partner::where('is_placeholder', true)->withCount('users')->get() as $partner) {
            if ($partner->users_count > 0) {
                $kept++;

                continue;
            }
            $partner->delete();
            $this->logDeleted($partner);
            $deleted++;
        }

        session()->flash('status', "Образцов удалено: {$deleted}.".($kept ? " Оставлено (есть доступы в кабинет): {$kept}." : ''));
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Partner::query()
            ->with(['region', 'city'])
            ->withCount('users')
            ->when($this->filterValue($this->filterKind), fn ($q, $kind) => $q->where('kind', $kind))
            ->when($this->filterValue($this->filterRegion), fn ($q, $id) => $q->where('region_id', $id))
            ->when($this->filterStatus === 'published', fn ($q) => $q->where('is_published', true))
            ->when($this->filterStatus === 'hidden', fn ($q) => $q->where('is_published', false))
            ->when($this->filterStatus === 'sample', fn ($q) => $q->where('is_placeholder', true))
            ->when($this->filterStatus === 'real', fn ($q) => $q->where('is_placeholder', false));

        return view('livewire.admin.partners.index', [
            'rows' => $this->tableRows($query),
            'regions' => Region::orderBy('sort')->get()->mapWithKeys(fn (Region $r) => [$r->id => $r->tr('name', 'ru')]),
            'samples' => Partner::where('is_placeholder', true)->count(),
            'samplesLive' => Partner::where('is_placeholder', true)->where('is_published', true)->count(),
            'real' => Partner::where('is_placeholder', false)->count(),
        ])->layout('components.layouts.admin', ['title' => 'Партнёры']);
    }
}
