<?php

namespace App\Livewire\Admin\Guide;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\Family;
use App\Models\GuideCheck;
use App\Models\GuideEntry;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** /admin/guide: buying guide of the knowledge base: one column per family, plus the checklist. */
class Index extends Component
{
    use LogsActivity, RequiresStaff;

    public function deleteEntry(int $id): void
    {
        $entry = GuideEntry::findOrFail($id);
        $entry->delete();

        $this->logDeleted($entry);
        session()->flash('status', 'Раздел гида удалён.');
    }

    public function deleteCheck(int $id): void
    {
        $check = GuideCheck::findOrFail($id);
        $check->delete();

        $this->logDeleted($check);
        session()->flash('status', 'Пункт списка удалён.');
    }

    public function render(): View
    {
        return view('livewire.admin.guide.index', [
            'entries' => GuideEntry::with('family')->orderBy('sort')->orderBy('id')->get(),
            'checks' => GuideCheck::orderBy('sort')->orderBy('id')->get(),
            'freeFamilies' => Family::whereNotIn('id', GuideEntry::pluck('family_id'))->count(),
        ])->layout('components.layouts.admin', ['title' => 'Гид по выбору']);
    }
}
