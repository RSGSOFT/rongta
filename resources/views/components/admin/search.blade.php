{{-- Search box for tables. Binds wire:model.live.debounce to "search" (trait WithTable) unless a wire:model is passed. --}}
@props(['placeholder' => 'Поиск…', 'label' => 'Поиск'])
@php
    $bound = $attributes->whereStartsWith('wire:model')->isNotEmpty();
@endphp
<div class="relative w-full sm:w-64">
    <label class="sr-only" for="admin-search">{{ $label }}</label>
    <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-steel" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/></svg>
    <input type="search" id="admin-search" placeholder="{{ $placeholder }}"
           @unless ($bound) wire:model.live.debounce.300ms="search" @endunless
           {{ $attributes->class('adm-control !pl-8') }}>
</div>
