<?php

namespace App\Livewire\Admin\PartnerPoints;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\PartnerPoint;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** /admin/partner-points: advantages and terms listed on the public "For partners" page. */
class Index extends Component
{
    use LogsActivity, RequiresStaff;

    /** Kind => [list heading, "add" button, empty text]. */
    public const KINDS = [
        'advantage' => ['Преимущества партнёрства', 'Добавить преимущество', 'Преимуществ пока нет: этот блок на сайте скрыт.'],
        'term' => ['Условия партнёрства', 'Добавить условие', 'Условий пока нет: этот блок на сайте скрыт.'],
    ];

    public function delete(int $id): void
    {
        $point = PartnerPoint::findOrFail($id);
        $point->delete();

        $this->logDeleted($point);
        session()->flash('status', 'Пункт удалён.');
    }

    public function render(): View
    {
        return view('livewire.admin.partner-points.index', [
            'points' => PartnerPoint::orderBy('sort')->orderBy('id')->get()->groupBy('kind'),
        ])->layout('components.layouts.admin', ['title' => 'Страница «Партнёрам»']);
    }
}
