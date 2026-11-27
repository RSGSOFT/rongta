<?php

namespace App\Livewire\Admin\HeroItems;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\HeroItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Create (/admin/hero-items/create) and edit (/admin/hero-items/{item}/edit) a sample product.
 * Weight is typed in kilograms and stored in grams; the total is calculated, never typed.
 */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public ?int $itemId = null;

    public array $form = [];

    public function mount(?HeroItem $item = null): void
    {
        $item = $item?->exists ? $item : null;
        $this->itemId = $item?->id;

        $this->form = [
            'name' => $this->transFill($item?->name),
            'brand' => (string) ($item->brand ?? ''),
            'weight_kg' => $item ? $item->weightKg() : '',
            'unit_price' => $item->unit_price ?? '',
            'sort' => $item->sort ?? ((int) HeroItem::max('sort') + 1),
            'is_published' => $item->is_published ?? true,
        ];
    }

    protected function rules(): array
    {
        return [
            ...$this->transRules('form.name', max: 60),
            'form.brand' => ['nullable', 'string', 'max:40'],
            'form.weight_kg' => ['required', 'numeric', 'decimal:0,3', 'min:'.(HeroItem::MIN_WEIGHT_G / 1000), 'max:'.(HeroItem::MAX_WEIGHT_G / 1000)],
            'form.unit_price' => ['required', 'integer', 'min:'.HeroItem::MIN_UNIT_PRICE, 'max:'.HeroItem::MAX_UNIT_PRICE],
            'form.sort' => ['required', 'integer', 'min:0', 'max:100000'],
            'form.is_published' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            ...$this->russianMessages(),
            'form.weight_kg.*' => 'Вес: от 0,040 до 30 кг, не больше трёх знаков после запятой. Например 1.250.',
            'form.unit_price.*' => 'Число на табло «за кг»: целое, от 10 до 5 000 000.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.brand' => 'Бренд',
            'form.sort' => 'Порядок',
            ...$this->transAttributes('form.name', 'Название'),
        ];
    }

    /** Staff may type "1,25": a comma is read as the decimal point. */
    public function updatedFormWeightKg(mixed $value): void
    {
        $this->form['weight_kg'] = str_replace(',', '.', trim((string) $value));
    }

    /** Total for the preview line, or null while the two numbers are not valid yet. */
    public function previewTotal(): ?string
    {
        $weight = $this->form['weight_kg'] ?? '';
        $unitPrice = $this->form['unit_price'] ?? '';
        if (! is_numeric($weight) || ! is_numeric($unitPrice)) {
            return null;
        }

        return HeroItem::spaced((int) round((float) $weight * (float) $unitPrice));
    }

    public function save()
    {
        $this->updatedFormWeightKg($this->form['weight_kg'] ?? '');
        $this->validate();

        $item = $this->itemId ? HeroItem::findOrFail($this->itemId) : new HeroItem;
        $item->fill([
            'name' => $this->transClean($this->form['name']),
            'brand' => trim((string) $this->form['brand']) ?: null,
            'weight_g' => (int) round((float) $this->form['weight_kg'] * 1000),
            'unit_price' => (int) $this->form['unit_price'],
            'sort' => (int) $this->form['sort'],
            'is_published' => (bool) $this->form['is_published'],
        ])->save();

        $this->logSaved($item);
        session()->flash('status', 'Образец сохранён.');

        return redirect()->route('admin.hero-items.index');
    }

    public function render(): View
    {
        $title = $this->itemId ? 'Образец для весов' : 'Новый образец для весов';

        return view('livewire.admin.hero-items.form', ['title' => $title, 'total' => $this->previewTotal()])
            ->layout('components.layouts.admin', ['title' => $title]);
    }
}
