{{--
  Button or link. variant: primary | ghost | danger. size: md | sm.
  With href it renders <a>; otherwise <button type="button"> (pass type="submit" in forms).
  confirm="..." asks before running the wire:click action (wire:confirm).
--}}
@props(['variant' => 'ghost', 'size' => 'md', 'href' => null, 'type' => 'button', 'confirm' => null])
@php
    $classes = 'inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-m border font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50 '
        .($size === 'sm' ? 'min-h-8 px-2.5 text-xs ' : 'min-h-10 px-4 text-sm ')
        .match ($variant) {
            'primary' => 'border-accent bg-accent text-on-accent hover:border-accent-ink hover:bg-accent-ink hover:text-white',
            'danger' => 'border-danger/40 bg-label text-danger hover:bg-danger hover:text-white',
            default => 'border-line bg-label text-ink hover:bg-paper',
        };
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" @if ($confirm) wire:confirm="{{ $confirm }}" @endif {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
