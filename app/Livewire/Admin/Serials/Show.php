<?php

namespace App\Livewire\Admin\Serials;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\SerialBatch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/** /admin/serials/batches/{batch} (admins only): one upload, its serial numbers and the rows it skipped. */
class Show extends Component
{
    use LogsActivity, RequiresStaff, WithPagination;

    protected bool $adminOnly = true;

    #[Locked]
    public int $batchId;

    public function mount(SerialBatch $batch): void
    {
        $this->batchId = $batch->id;
    }

    /** Removes a batch loaded by mistake, together with its serial numbers. */
    public function delete()
    {
        $batch = SerialBatch::findOrFail($this->batchId);
        $batch->delete();

        $this->logDeleted($batch);
        session()->flash('status', "Партия «{$batch->file_name}» удалена вместе с её серийными номерами ({$batch->rows_added}).");

        return redirect()->route('admin.serials.index');
    }

    public function render(): View
    {
        $batch = SerialBatch::with('user')->findOrFail($this->batchId);

        return view('livewire.admin.serials.show', [
            'batch' => $batch,
            'items' => $batch->serialNumbers()->with('scaleModel')->orderBy('id')->paginate(50),
        ])->layout('components.layouts.admin', ['title' => 'Партия серийных номеров']);
    }
}
