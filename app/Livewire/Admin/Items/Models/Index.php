<?php

namespace App\Livewire\Admin\Items\Models;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\WithTable;
use App\Models\Family;
use App\Models\ScaleModel;
use App\Models\SerialNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    use LogsActivity, RequiresStaff, WithTable;

    protected array $searchable = ['name', 'slug', 'type'];

    protected array $sortable = ['name', 'sort', 'is_published', 'updated_at'];

    protected string $defaultSort = 'sort';

    #[Url(as: 'family', except: '')]
    public string $filterFamily = '';

    #[Url(as: 'published', except: '')]
    public string $filterPublished = '';

    public function togglePublished(int $id): void
    {
        $model = ScaleModel::findOrFail($id);
        $model->update(['is_published' => ! $model->is_published]);

        $this->logActivity($model->is_published ? 'published' : 'unpublished', $model, ['is_published' => $model->is_published]);
        session()->flash('status', $model->is_published
            ? "Модель {$model->name} опубликована на сайте."
            : "Модель {$model->name} скрыта с сайта.");
    }

    public function delete(int $id): void
    {
        $model = ScaleModel::with('documents')->findOrFail($id);

        // Imported scales of this model are on record: the model stays.
        if (SerialNumber::where('scale_model_id', $model->id)->exists()) {
            session()->flash('error', "Модель {$model->name} нельзя удалить: к ней загружены серийные номера. Снимите её с публикации.");

            return;
        }

        $files = array_filter([
            $model->image,
            ...($model->gallery ?? []),
            ...$model->documents->pluck('file_path')->all(),
        ]);

        // Features, specs, documents and chooser links go with the model (database cascade).
        $model->delete();
        Storage::disk('public')->delete($files);

        $this->logDeleted($model);
        session()->flash('status', "Модель {$model->name} удалена вместе с характеристиками и документами.");
    }

    public function render(): View
    {
        $query = ScaleModel::query()
            ->with('family')
            ->withCount(['specs', 'features', 'documents'])
            ->when($this->filterValue($this->filterFamily), fn ($q, $id) => $q->where('family_id', $id))
            ->when($this->filterPublished !== '', fn ($q) => $q->where('is_published', $this->filterPublished === '1'));

        return view('livewire.admin.items.models-index', [
            'rows' => $this->tableRows($query),
            'families' => Family::orderBy('sort')->get()->mapWithKeys(fn ($f) => [$f->id => $f->tr('name', 'ru')]),
        ])->layout('components.layouts.admin', ['title' => 'Модели весов']);
    }
}
