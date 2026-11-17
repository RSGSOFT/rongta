<?php

namespace App\Livewire\Admin\Regions;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    use LogsActivity, RequiresStaff, WithTable;

    protected array $searchable = ['name', 'short', 'slug'];

    protected array $sortable = ['slug', 'sort'];

    protected string $defaultSort = 'sort';

    public function delete(int $id): void
    {
        $region = Region::withCount(['partners', 'cities'])->findOrFail($id);

        if ($region->partners_count > 0) {
            session()->flash('error', 'Нельзя удалить регион, в котором есть партнёры. Сначала перенесите или удалите партнёров.');

            return;
        }
        if ($region->cities_count > 0) {
            session()->flash('error', 'Нельзя удалить регион, в котором есть города. Сначала перенесите или удалите города.');

            return;
        }

        $region->delete();
        $this->logDeleted($region);
        session()->flash('status', 'Регион удалён.');
    }

    public function render(): View
    {
        return view('livewire.admin.regions.index', [
            'rows' => $this->tableRows(Region::query()->withCount(['partners', 'cities'])),
            'map' => Region::orderBy('sort')->get(),
        ])->layout('components.layouts.admin', ['title' => 'Регионы']);
    }
}
