<?php

namespace App\Livewire\Admin\HeroItems;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\HeroItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** /admin/hero-items: sample products shown on the home page scale display and label. */
class Index extends Component
{
    use LogsActivity, RequiresStaff;

    public function togglePublished(int $id): void
    {
        $item = HeroItem::findOrFail($id);
        $item->update(['is_published' => ! $item->is_published]);

        $this->logSaved($item);
    }

    public function delete(int $id): void
    {
        $item = HeroItem::findOrFail($id);
        $item->delete();

        $this->logDeleted($item);
        session()->flash('status', 'Образец удалён.');
    }

    public function render(): View
    {
        return view('livewire.admin.hero-items.index', [
            'items' => HeroItem::orderBy('sort')->orderBy('id')->get(),
        ])->layout('components.layouts.admin', ['title' => 'Образцы для весов']);
    }
}
