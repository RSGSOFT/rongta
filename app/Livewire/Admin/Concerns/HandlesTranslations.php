<?php

namespace App\Livewire\Admin\Concerns;

/**
 * Helpers for {ru, uz, en} form fields (used with <x-admin.trans-input model="form.name" />).
 *
 *   public array $form = ['name' => ['ru' => '', 'uz' => '', 'en' => '']];
 *   $this->form['name'] = $this->transFill($model->name);            // load
 *   $rules = [...$this->transRules('form.name', max: 255), ...];     // validate (Russian required)
 *   $model->name = $this->transClean($this->form['name']);            // save
 */
trait HandlesTranslations
{
    public const LOCALES = ['ru', 'uz', 'en'];

    /** Empty trilingual value. */
    protected function transEmpty(): array
    {
        return ['ru' => '', 'uz' => '', 'en' => ''];
    }

    /** Any stored value (array, string, null) as a full {ru, uz, en} array of strings. */
    protected function transFill(mixed $value): array
    {
        if (is_string($value)) {
            $value = ['ru' => $value];
        }
        $value = is_array($value) ? $value : [];

        $out = [];
        foreach (self::LOCALES as $locale) {
            $out[$locale] = is_scalar($value[$locale] ?? null) ? (string) $value[$locale] : '';
        }

        return $out;
    }

    /** Trimmed {ru, uz, en} ready to store. */
    protected function transClean(mixed $value): array
    {
        return array_map(fn (string $text) => trim($text), $this->transFill($value));
    }

    /** True when every language is empty. */
    protected function transBlank(mixed $value): bool
    {
        return implode('', $this->transClean($value)) === '';
    }

    /**
     * Validation rules for one trilingual field. Russian (the default site language) is required
     * unless $required is false; Uzbek and English may be saved empty and are flagged in the form.
     */
    protected function transRules(string $field, bool $required = true, int $max = 255): array
    {
        return [
            $field => ['array'],
            $field.'.ru' => [$required ? 'required' : 'nullable', 'string', 'max:'.$max],
            $field.'.uz' => ['nullable', 'string', 'max:'.$max],
            $field.'.en' => ['nullable', 'string', 'max:'.$max],
        ];
    }

    /** Attribute names for error messages: "Название (RU)". */
    protected function transAttributes(string $field, string $label): array
    {
        return [
            $field => $label,
            $field.'.ru' => $label.' (RU)',
            $field.'.uz' => $label.' (UZ)',
            $field.'.en' => $label.' (EN)',
        ];
    }
}
