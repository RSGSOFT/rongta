{{-- Standard form footer: Save + Cancel, with a "saving" state. Use inside <form wire:submit="save">. --}}
@props(['cancel' => null, 'label' => 'Сохранить', 'target' => 'save'])
@php $errors ??= new \Illuminate\Support\ViewErrorBag; @endphp
<div {{ $attributes->class('flex flex-wrap items-center gap-2') }}>
    <x-admin.btn variant="primary" type="submit" wire:loading.attr="disabled" wire:target="{{ $target }}">
        <span wire:loading.remove wire:target="{{ $target }}">{{ $label }}</span>
        <span wire:loading wire:target="{{ $target }}">Сохранение…</span>
    </x-admin.btn>
    @if ($cancel)
        <x-admin.btn :href="$cancel">Отмена</x-admin.btn>
    @endif
    {{ $slot }}
    @if ($errors->any())
        <span class="text-xs font-medium text-danger" role="alert">Есть ошибки в полях: {{ $errors->count() }}. Исправьте их и сохраните снова.</span>
    @endif
</div>
