{{-- Label + control + hint + error. --}}
@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null, 'required' => false])
<div {{ $attributes->class('min-w-0') }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="mb-1 block text-xs font-semibold text-ink">
            {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if ($hint)
        <p class="mt-1 text-xs text-steel">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $error }}</p>
    @endif
</div>
