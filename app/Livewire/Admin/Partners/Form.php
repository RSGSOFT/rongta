<?php

namespace App\Livewire\Admin\Partners;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\City;
use App\Models\Partner;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create (/admin/partners/create) and edit (/admin/partners/{partner}/edit). */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public ?int $partnerId = null;

    /** The entry was a sample when the form was opened. */
    #[Locked]
    public bool $wasSample = false;

    public array $form = [];

    public function mount(?Partner $partner = null): void
    {
        $partner = $partner?->exists ? $partner : null;
        $this->partnerId = $partner?->id;
        $this->wasSample = (bool) $partner?->is_placeholder;

        $kind = request()->query('kind');

        $this->form = [
            'kind' => $partner->kind ?? (in_array($kind, Partner::KINDS, true) ? $kind : 'reseller'),
            'name' => $this->transFill($partner?->name),
            'slug' => $partner->slug ?? '',
            'region_id' => (string) ($partner->region_id ?? ''),
            'city_id' => (string) ($partner->city_id ?? ''),
            'offers' => array_values(array_intersect(array_keys(Options::OFFERS), (array) ($partner->offers ?? []))),
            'phone' => $partner->phone ?? '',
            'telegram' => $partner->telegram ?? '',
            'address' => $this->transFill($partner?->address),
            'is_published' => $partner ? (bool) $partner->is_published : true,
            'is_placeholder' => (bool) $partner?->is_placeholder,
            'sort' => $partner->sort ?? ((int) Partner::max('sort') + 1),
        ];
    }

    /** A city of another region cannot stay selected. */
    public function updatedFormRegionId(): void
    {
        $city = $this->form['city_id'] !== '' ? City::find($this->form['city_id']) : null;
        if ($city && $city->region_id !== null && (string) $city->region_id !== (string) $this->form['region_id']) {
            $this->form['city_id'] = '';
        }
    }

    protected function rules(): array
    {
        return [
            'form.kind' => ['required', Rule::in(Partner::KINDS)],
            ...$this->transRules('form.name', max: 160),
            'form.slug' => ['nullable', 'string', 'max:80', 'regex:'.Options::SLUG_REGEX, Rule::unique('partners', 'slug')->ignore($this->partnerId)],
            'form.region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
            'form.city_id' => ['nullable', 'integer', Rule::exists('cities', 'id'), function ($attribute, $value, $fail) {
                $city = City::find($value);
                if ($city && $city->region_id !== null && (string) $city->region_id !== (string) $this->form['region_id']) {
                    $fail('Выбранный город относится к другому региону.');
                }
            }],
            'form.offers' => ['array', 'max:'.count(Options::OFFERS)],
            'form.offers.*' => ['string', Rule::in(array_keys(Options::OFFERS))],
            'form.phone' => ['nullable', 'string', 'max:30', 'regex:'.Options::PHONE_REGEX],
            'form.telegram' => ['nullable', 'string', 'max:40', 'regex:'.Options::TELEGRAM_REGEX],
            ...$this->transRules('form.address', required: false, max: 255),
            'form.is_published' => ['boolean'],
            'form.is_placeholder' => ['boolean'],
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function messages(): array
    {
        return [
            ...$this->russianMessages(),
            'form.phone.regex' => 'Телефон: только цифры, пробелы, скобки и дефис, можно с «+» в начале. Например +998 71 200 00 00.',
            'form.telegram.regex' => 'Telegram: имя пользователя латиницей, например @rongta_uz.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.kind' => 'Тип',
            'form.slug' => 'Код',
            'form.region_id' => 'Регион',
            'form.city_id' => 'Город',
            'form.offers' => 'Услуги',
            'form.offers.*' => 'Услуги',
            'form.phone' => 'Телефон',
            'form.telegram' => 'Telegram',
            'form.sort' => 'Порядок',
            ...$this->transAttributes('form.name', 'Название'),
            ...$this->transAttributes('form.address', 'Адрес'),
        ];
    }

    public function save()
    {
        $this->form['phone'] = trim((string) $this->form['phone']);
        $this->form['telegram'] = trim((string) $this->form['telegram']);
        $this->form['slug'] = trim((string) $this->form['slug']);
        $this->validate();

        $telegram = $this->form['telegram'] !== '' ? '@'.ltrim($this->form['telegram'], '@') : null;
        $address = $this->transClean($this->form['address']);

        $partner = $this->partnerId ? Partner::findOrFail($this->partnerId) : new Partner;
        $partner->fill([
            'kind' => $this->form['kind'],
            'name' => $this->transClean($this->form['name']),
            'slug' => $this->form['slug'] ?: null,
            'region_id' => (int) $this->form['region_id'],
            'city_id' => $this->form['city_id'] !== '' && $this->form['city_id'] !== null ? (int) $this->form['city_id'] : null,
            // Stored in the fixed display order.
            'offers' => array_values(array_intersect(array_keys(Options::OFFERS), $this->form['offers'])),
            'phone' => $this->form['phone'] ?: null,
            'telegram' => $telegram,
            'address' => $this->transBlank($address) ? null : $address,
            'is_published' => (bool) $this->form['is_published'],
            'is_placeholder' => (bool) $this->form['is_placeholder'],
            'sort' => (int) $this->form['sort'],
        ])->save();

        $this->logSaved($partner);
        session()->flash('status', 'Партнёр сохранён.');

        return redirect()->route('admin.partners.index');
    }

    public function render(): View
    {
        $title = $this->partnerId ? 'Партнёр: '.trv($this->form['name'], 'ru') : 'Новый партнёр';
        $regionId = $this->form['region_id'] ?? '';

        $cities = City::query()
            ->when($regionId !== '' && $regionId !== null, fn ($q) => $q->where(fn ($w) => $w->where('region_id', $regionId)->orWhereNull('region_id')))
            ->orderBy('sort')
            ->get()
            ->mapWithKeys(fn (City $c) => [$c->id => $c->tr('name', 'ru')]);

        return view('livewire.admin.partners.form', [
            'title' => $title,
            'regions' => Region::orderBy('sort')->get()->mapWithKeys(fn (Region $r) => [$r->id => $r->tr('name', 'ru')]),
            'cities' => $cities,
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
