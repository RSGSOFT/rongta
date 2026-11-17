<?php

namespace App\Livewire\Admin\Cities;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\City;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    use LogsActivity, RequiresStaff, WithTable;

    protected array $searchable = ['name', 'slug'];

    protected array $sortable = ['slug', 'sort'];

    protected string $defaultSort = 'sort';

    /** Region id, or "none" for cities without a region. */
    #[Url(as: 'region', except: '')]
    public string $filterRegion = '';

    public function delete(int $id): void
    {
        $city = City::withCount('partners')->findOrFail($id);

        if ($city->partners_count > 0) {
            session()->flash('error', 'Нельзя удалить город, который указан у партнёров. Сначала измените город у этих партнёров.');

            return;
        }

        $city->delete();
        $this->logDeleted($city);
        session()->flash('status', 'Город удалён.');
    }

    public function render(): View
    {
        $query = City::query()
            ->with('region')
            ->withCount('partners')
            ->when($this->filterRegion === 'none', fn ($q) => $q->whereNull('region_id'))
            ->when($this->filterRegion !== 'none' ? $this->filterValue($this->filterRegion) : null, fn ($q, $id) => $q->where('region_id', $id));

        return view('livewire.admin.cities.index', [
            'rows' => $this->tableRows($query),
            'regions' => Region::orderBy('sort')->get()->mapWithKeys(fn (Region $r) => [$r->id => $r->tr('name', 'ru')]),
        ])->layout('components.layouts.admin', ['title' => 'Города']);
    }
}
