<?php

namespace App\Livewire\Admin\Regions;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Partners\Options;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create (/admin/regions/create) and edit (/admin/regions/{region}/edit), with a live preview of the tile map. */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public ?int $regionId = null;

    public array $form = [];

    public function mount(?Region $region = null): void
    {
        $region = $region?->exists ? $region : null;
        $this->regionId = $region?->id;

        $this->form = [
            'slug' => $region->slug ?? '',
            'name' => $this->transFill($region?->name),
            'short' => $this->transFill($region?->short),
            'map_col' => $region->map_col ?? 1,
            'map_row' => $region->map_row ?? 1,
            'sort' => $region->sort ?? ((int) Region::max('sort') + 1),
        ];
    }

    /** The region already standing on the chosen tile, if any. */
    protected function occupant(): ?Region
    {
        $col = $this->form['map_col'] ?? null;
        $row = $this->form['map_row'] ?? null;
        if (! is_numeric($col) || ! is_numeric($row)) {
            return null;
        }

        return Region::query()
            ->where('map_col', (int) $col)
            ->where('map_row', (int) $row)
            ->when($this->regionId, fn ($q) => $q->whereKeyNot($this->regionId))
            ->first();
    }

    protected function rules(): array
    {
        return [
            'form.slug' => ['required', 'string', 'max:60', 'regex:'.Options::SLUG_REGEX, Rule::unique('regions', 'slug')->ignore($this->regionId)],
            ...$this->transRules('form.name', max: 120),
            ...$this->transRules('form.short', max: 24),
            'form.map_col' => ['required', 'integer', 'min:1', 'max:'.Options::MAP_COLS],
            'form.map_row' => ['required', 'integer', 'min:1', 'max:'.Options::MAP_ROWS, function ($attribute, $value, $fail) {
                if ($other = $this->occupant()) {
                    $fail('Эта клетка карты уже занята регионом «'.$other->tr('name', 'ru').'». Выберите другую колонку или ряд.');
                }
            }],
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.slug' => 'Код',
            'form.map_col' => 'Колонка',
            'form.map_row' => 'Ряд',
            'form.sort' => 'Порядок',
            ...$this->transAttributes('form.name', 'Название'),
            ...$this->transAttributes('form.short', 'Короткое название'),
        ];
    }

    public function save()
    {
        $this->validate();

        $region = $this->regionId ? Region::findOrFail($this->regionId) : new Region;
        $region->fill([
            'slug' => $this->form['slug'],
            'name' => $this->transClean($this->form['name']),
            'short' => $this->transClean($this->form['short']),
            'map_col' => (int) $this->form['map_col'],
            'map_row' => (int) $this->form['map_row'],
            'sort' => (int) $this->form['sort'],
        ])->save();

        $this->logSaved($region);
        session()->flash('status', 'Регион сохранён.');

        return redirect()->route('admin.regions.index');
    }

    public function render(): View
    {
        $title = $this->regionId ? 'Регион: '.trv($this->form['name'], 'ru') : 'Новый регион';

        $col = is_numeric($this->form['map_col'] ?? null) ? (int) $this->form['map_col'] : 0;
        $row = is_numeric($this->form['map_row'] ?? null) ? (int) $this->form['map_row'] : 0;

        return view('livewire.admin.regions.form', [
            'title' => $title,
            'others' => Region::query()->when($this->regionId, fn ($q) => $q->whereKeyNot($this->regionId))->orderBy('sort')->get(),
            'tile' => $col >= 1 && $col <= Options::MAP_COLS && $row >= 1 && $row <= Options::MAP_ROWS ? [$col, $row] : null,
            'occupant' => $this->occupant(),
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
