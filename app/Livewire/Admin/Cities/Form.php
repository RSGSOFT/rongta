<?php

namespace App\Livewire\Admin\Cities;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Partners\Options;
use App\Models\City;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create (/admin/cities/create?region=ID) and edit (/admin/cities/{city}/edit). */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public ?int $cityId = null;

    public array $form = [];

    public function mount(?City $city = null): void
    {
        $city = $city?->exists ? $city : null;
        $this->cityId = $city?->id;

        $preset = request()->query('region');

        $this->form = [
            'slug' => $city->slug ?? '',
            'name' => $this->transFill($city?->name),
            'region_id' => $city ? (string) ($city->region_id ?? '') : (is_numeric($preset) && Region::whereKey($preset)->exists() ? (string) $preset : ''),
            'sort' => $city->sort ?? ((int) City::max('sort') + 1),
        ];
    }

    protected function rules(): array
    {
        return [
            'form.slug' => ['required', 'string', 'max:60', 'regex:'.Options::SLUG_REGEX, Rule::unique('cities', 'slug')->ignore($this->cityId)],
            ...$this->transRules('form.name', max: 120),
            'form.region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.slug' => 'Код',
            'form.region_id' => 'Регион',
            'form.sort' => 'Порядок',
            ...$this->transAttributes('form.name', 'Название'),
        ];
    }

    public function save()
    {
        $this->validate();

        $city = $this->cityId ? City::findOrFail($this->cityId) : new City;
        $city->fill([
            'slug' => $this->form['slug'],
            'name' => $this->transClean($this->form['name']),
            'region_id' => (int) $this->form['region_id'],
            'sort' => (int) $this->form['sort'],
        ])->save();

        // Partners of this city follow it to its region, so the Dealers page stays consistent.
        $moved = $city->partners()->where(fn ($q) => $q->whereNull('region_id')->orWhere('region_id', '!=', $city->region_id))->update(['region_id' => $city->region_id]);

        $this->logSaved($city, $moved && ! $city->wasRecentlyCreated ? ['partners_moved' => $moved] : []);
        session()->flash('status', 'Город сохранён.'.($moved ? ' Партнёров перенесено в новый регион: '.$moved.'.' : ''));

        return redirect()->route('admin.cities.index');
    }

    public function render(): View
    {
        $title = $this->cityId ? 'Город: '.trv($this->form['name'], 'ru') : 'Новый город';

        return view('livewire.admin.cities.form', [
            'title' => $title,
            'regions' => Region::orderBy('sort')->get()->mapWithKeys(fn (Region $r) => [$r->id => $r->tr('name', 'ru')]),
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
