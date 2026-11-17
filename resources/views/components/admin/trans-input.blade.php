{{--
  Trilingual single-line input bound to {model}.ru / .uz / .en.
  A language with no text is marked in amber and listed next to the label ("нет перевода: UZ, EN").
  Validation errors for {model}, {model}.ru, {model}.uz and {model}.en are shown under the rows.
--}}
@props(['label' => null, 'model', 'hint' => null, 'required' => false, 'live' => false, 'multiline' => false, 'rows' => 3])
@php
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $locales = ['ru' => 'Русский', 'uz' => 'Oʻzbekcha', 'en' => 'English'];
    $id = 'tr-'.trim(preg_replace('/[^a-z0-9]+/i', '-', $model), '-');
    $bind = $live ? 'wire:model.live.debounce.400ms' : 'wire:model';
    $missingList = "['ru','uz','en'].filter(l => !String(\$wire.\$get('{$model}.' + l) ?? '').trim()).map(l => l.toUpperCase())";
@endphp
<div {{ $attributes->class('min-w-0') }} role="group" @if ($label) aria-labelledby="{{ $id }}-label" @endif>
    <div class="mb-1 flex flex-wrap items-baseline justify-between gap-x-3">
        @if ($label)
            <span id="{{ $id }}-label" class="text-xs font-semibold text-ink">
                {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
            </span>
        @endif
        <span x-data x-cloak x-show="{{ $missingList }}.length" class="text-[11px] font-semibold text-warn">
            нет перевода: <span x-text="{{ $missingList }}.join(', ')"></span>
        </span>
    </div>
    <div class="space-y-1.5">
        @foreach ($locales as $l => $languageName)
            <div class="adm-lang" x-data x-bind:class="{ 'is-missing': !String($wire.$get('{{ $model }}.{{ $l }}') ?? '').trim() }">
                <label for="{{ $id }}-{{ $l }}" class="adm-lang-tag" title="{{ $languageName }}">{{ strtoupper($l) }}<span class="sr-only"> {{ $label }}</span></label>
                @if ($multiline)
                    <textarea id="{{ $id }}-{{ $l }}" lang="{{ $l }}" rows="{{ $rows }}" {{ $bind }}="{{ $model }}.{{ $l }}" class="adm-control" @error($model.'.'.$l) aria-invalid="true" @enderror></textarea>
                @else
                    <input type="text" id="{{ $id }}-{{ $l }}" lang="{{ $l }}" {{ $bind }}="{{ $model }}.{{ $l }}" class="adm-control" @error($model.'.'.$l) aria-invalid="true" @enderror>
                @endif
            </div>
        @endforeach
    </div>
    @if ($hint)
        <p class="mt-1 text-xs text-steel">{{ $hint }}</p>
    @endif
    @foreach ([$model, $model.'.ru', $model.'.uz', $model.'.en'] as $errorKey)
        @error($errorKey)
            <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p>
        @enderror
    @endforeach
</div>
