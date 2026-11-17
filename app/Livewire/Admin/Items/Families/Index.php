<?php

namespace App\Livewire\Admin\Items\Families;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\Family;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    use LogsActivity, RequiresStaff, WithTable;

    protected array $searchable = ['name', 'slug'];

    protected array $sortable = ['slug', 'sort'];

    protected string $defaultSort = 'sort';

    public function delete(int $id): void
    {
        $family = Family::withCount('scaleModels')->findOrFail($id);

        if ($family->scale_models_count > 0) {
            session()->flash('error', 'Нельзя удалить семейство, в котором есть модели. Сначала перенесите или удалите модели.');

            return;
        }

        $family->delete();
        $this->logDeleted($family);
        session()->flash('status', 'Семейство удалено.');
    }

    public function render(): View
    {
        return view('livewire.admin.items.families-index', [
            'rows' => $this->tableRows(Family::query()->withCount('scaleModels')),
        ])->layout('components.layouts.admin', ['title' => 'Семейства']);
    }
}
