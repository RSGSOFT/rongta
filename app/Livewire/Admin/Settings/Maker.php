<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\Setting;
use App\Support\Maker as MakerFigures;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * /admin/settings/maker: the "About the manufacturer" figures of the trust block, admins only.
 * Stored in the "maker" setting (shape in App\Support\Maker). A hidden row stays in the list;
 * the card disappears from the site when no row is visible.
 */
class Maker extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    protected bool $adminOnly = true;

    public const MAX_ROWS = 12;

    public array $rows = [];

    public array $source = [];

    public function mount(): void
    {
        $maker = MakerFigures::all();
        foreach ($maker['rows'] as $row) {
            // A label kept as an interface-string key is shown as its text, so it can be edited here.
            $label = $row['label'];
            if (implode('', $label) === '' && $row['label_key']) {
                $label = ['ru' => ui($row['label_key'], [], 'ru'), 'uz' => ui($row['label_key'], [], 'uz'), 'en' => ui($row['label_key'], [], 'en')];
            }
            $this->rows[] = ['label' => $this->transFill($label), 'value' => $this->transFill($row['value']), 'visible' => $row['visible']];
        }
        $this->source = $this->transFill($maker['source']);
    }

    public function addRow(): void
    {
        if (count($this->rows) < self::MAX_ROWS) {
            $this->rows[] = ['label' => $this->transEmpty(), 'value' => $this->transEmpty(), 'visible' => true];
        }
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function moveRow(int $index, int $step): void
    {
        $target = $index + ($step < 0 ? -1 : 1);
        if (isset($this->rows[$index], $this->rows[$target])) {
            [$this->rows[$index], $this->rows[$target]] = [$this->rows[$target], $this->rows[$index]];
        }
    }

    protected function rules(): array
    {
        return [
            'rows' => ['array', 'max:'.self::MAX_ROWS],
            ...$this->transRules('rows.*.label', max: 120),
            ...$this->transRules('rows.*.value', max: 120),
            'rows.*.visible' => ['boolean'],
            ...$this->transRules('source', required: false, max: 200),
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            ...$this->transAttributes('rows.*.label', 'Название строки'),
            ...$this->transAttributes('rows.*.value', 'Значение'),
            ...$this->transAttributes('source', 'Источник'),
        ];
    }

    public function save(): void
    {
        $this->rows = array_values($this->rows);
        $this->validate();

        $value = MakerFigures::normalise([
            'rows' => array_map(fn (array $row) => [
                'label' => $this->transClean($row['label'] ?? []),
                'label_key' => null,
                'value' => $this->transClean($row['value'] ?? []),
                'visible' => (bool) ($row['visible'] ?? false),
            ], $this->rows),
            'source' => $this->transClean($this->source),
        ]);

        $row = Setting::firstOrNew(['key' => MakerFigures::KEY]);
        $changed = ! $row->exists || $row->value != $value;
        if ($changed) {
            $row->value = $value;
            $row->save();
            $this->logActivity($row->wasRecentlyCreated ? 'created' : 'updated', $row, ['key' => MakerFigures::KEY, 'value' => $value]);
        }
        Setting::flush();

        session()->flash('status', $changed ? 'Сведения о производителе сохранены.' : 'Изменений нет.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.maker', [
            'visibleCount' => collect($this->rows)->filter(fn ($r) => ! empty($r['visible']))->count(),
        ])->layout('components.layouts.admin', ['title' => 'Настройки сайта: о производителе']);
    }
}
