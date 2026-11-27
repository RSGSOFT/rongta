<?php

namespace App\Livewire\Admin\ServiceCases;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Models\Family;
use App\Models\ServiceCase;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** /admin/service-cases: service and repair jobs with the amount RSG charges, typed right in the list. */
class Index extends Component
{
    use LogsActivity, RequiresStaff;

    /** Saves the amount typed in a row; an empty field clears it and takes the job off the site. */
    public function setAmount(int $id, mixed $value): void
    {
        $case = ServiceCase::findOrFail($id);
        $value = preg_replace('/[\s\x{00A0}]+/u', '', (string) $value);
        $this->resetErrorBag("amount.{$id}");

        if ($value !== '' && (! ctype_digit($value) || (int) $value < 1 || (int) $value > ServiceCase::MAX_AMOUNT)) {
            $this->addError("amount.{$id}", 'Целое число от 1 до '.ServiceCase::spaced(ServiceCase::MAX_AMOUNT).', без копеек.');

            return;
        }

        $case->update(['amount' => $value === '' ? null : (int) $value]);
        $this->logSaved($case);
    }

    public function togglePublished(int $id): void
    {
        $case = ServiceCase::findOrFail($id);
        $case->update(['is_published' => ! $case->is_published]);

        $this->logSaved($case);
    }

    public function delete(int $id): void
    {
        $case = ServiceCase::findOrFail($id);
        $case->delete();

        $this->logDeleted($case);
        session()->flash('status', 'Работа удалена.');
    }

    public function render(): View
    {
        $cases = ServiceCase::orderBy('sort')->orderBy('id')->get()->groupBy(fn (ServiceCase $case) => (int) $case->family_id);

        // "Every scale" first, then the families in their site order.
        $groups = collect([[0, 'Для всех весов']])
            ->concat(Family::orderBy('sort')->get()->map(fn (Family $family) => [$family->id, $family->tr('name', 'ru')]))
            ->map(fn (array $group) => ['id' => $group[0], 'name' => $group[1], 'cases' => $cases->get($group[0], collect())]);

        return view('livewire.admin.service-cases.index', [
            'groups' => $groups,
            'total' => $cases->flatten()->count(),
            'listed' => $cases->flatten()->filter->isListed()->count(),
        ])->layout('components.layouts.admin', ['title' => 'Сервис и ремонт']);
    }
}
