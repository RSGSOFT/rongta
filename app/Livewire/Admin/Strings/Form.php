<?php

namespace App\Livewire\Admin\Strings;

use App\Livewire\Admin\Concerns\HandlesTranslations;
use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\UiString;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * /admin/strings/{string}/edit: one interface string in Russian, Uzbek and English side by side.
 * Keys are fixed by the site code, so strings are edited here but not created or deleted.
 */
class Form extends Component
{
    use HandlesTranslations, LogsActivity, RequiresStaff, RussianValidation;

    #[Locked]
    public int $stringId;

    #[Locked]
    public string $key = '';

    public array $form = [];

    public function mount(UiString $string): void
    {
        $this->stringId = $string->id;
        $this->key = $string->key;
        $this->form = ['text' => $this->transFill(['ru' => $string->ru, 'uz' => $string->uz, 'en' => $string->en])];
    }

    /** {name} placeholders of a text, sorted. */
    public static function placeholders(string $text): array
    {
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $text, $m);
        $names = array_values(array_unique($m[1]));
        sort($names);

        return $names;
    }

    protected function rules(): array
    {
        $samePlaceholders = function (string $locale) {
            return function ($attribute, $value, $fail) use ($locale) {
                $value = trim((string) $value);
                if ($value === '') {
                    return;
                }
                $expected = self::placeholders((string) ($this->form['text']['ru'] ?? ''));
                if (self::placeholders($value) !== $expected) {
                    $fail('Подстановки в фигурных скобках должны совпадать с русским текстом: '
                        .($expected ? implode(', ', array_map(fn ($n) => '{'.$n.'}', $expected)) : 'их там нет')
                        .'. Сайт заменяет их на числа и названия. ('.strtoupper($locale).')');
                }
            };
        };

        return [
            'form.text' => ['array'],
            'form.text.ru' => ['required', 'string', 'max:5000'],
            'form.text.uz' => ['nullable', 'string', 'max:5000', $samePlaceholders('uz')],
            'form.text.en' => ['nullable', 'string', 'max:5000', $samePlaceholders('en')],
        ];
    }

    protected function validationAttributes(): array
    {
        return $this->transAttributes('form.text', 'Текст');
    }

    public function save()
    {
        $this->validate();

        $text = $this->transClean($this->form['text']);

        $string = UiString::findOrFail($this->stringId);
        $string->fill([
            'ru' => $text['ru'],
            'uz' => $text['uz'] !== '' ? $text['uz'] : null,
            'en' => $text['en'] !== '' ? $text['en'] : null,
        ])->save(); // the model flushes the ui() cache

        $this->logSaved($string);
        session()->flash('status', "Текст «{$string->key}» сохранён.");

        return redirect()->route('admin.strings.index');
    }

    public function render(): View
    {
        $title = 'Текст: '.$this->key;

        return view('livewire.admin.strings.form', [
            'title' => $title,
            'placeholders' => self::placeholders((string) ($this->form['text']['ru'] ?? '')),
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
