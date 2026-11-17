<?php

namespace App\Livewire\Admin\Chooser;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\ChooserOption;
use App\Models\UiString;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** /admin/chooser: answers of "Which scale do you need?" on the home page. */
class Index extends Component
{
    use LogsActivity, RequiresStaff;

    public function delete(int $id): void
    {
        $option = ChooserOption::findOrFail($id);

        if (ChooserOption::count() <= 1) {
            session()->flash('error', 'Нельзя удалить последний вариант: блок подбора на главной останется пустым. Чтобы убрать блок, скройте его в настройках сайта.');

            return;
        }

        $option->delete(); // recommended models are detached by the database
        $this->logDeleted($option);
        session()->flash('status', 'Вариант подбора удалён.');
    }

    public function render(): View
    {
        return view('livewire.admin.chooser.index', [
            'options' => ChooserOption::with(['family', 'scaleModels'])->orderBy('sort')->orderBy('id')->get(),
            // Button labels live in the interface strings "ch.{key}".
            'labels' => UiString::where('key', 'like', 'ch.%')->get()->keyBy('key'),
        ])->layout('components.layouts.admin', ['title' => 'Подбор весов']);
    }
}
