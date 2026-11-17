{{-- Switch-style checkbox. Label from the "label" prop or the slot; attributes (wire:model ...) go to the input. --}}
@props(['label' => null, 'hint' => null])
<label class="inline-flex cursor-pointer items-start gap-2.5 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60">
    <span class="relative mt-0.5 inline-flex h-5 w-9 flex-none">
        <input type="checkbox" {{ $attributes->class('peer sr-only') }}>
        <span class="absolute inset-0 rounded-full bg-line transition-colors peer-checked:bg-ok peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-accent-ink" aria-hidden="true"></span>
        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-label shadow transition-transform peer-checked:translate-x-4" aria-hidden="true"></span>
    </span>
    <span class="min-w-0">
        <span class="block font-medium leading-5">{{ $label ?? $slot }}</span>
        @if ($hint)
            <span class="block text-xs text-steel">{{ $hint }}</span>
        @endif
    </span>
</label>
