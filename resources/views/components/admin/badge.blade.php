{{-- Small status pill. tone: neutral | ok | warn | danger | accent --}}
@props(['tone' => 'neutral'])
<span {{ $attributes->class([
    'inline-flex items-center whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold',
    'bg-paper-2 text-steel' => $tone === 'neutral',
    'bg-ok-soft text-ok' => $tone === 'ok',
    'bg-warn-soft text-warn' => $tone === 'warn',
    'bg-danger-soft text-danger' => $tone === 'danger',
    'bg-accent-soft text-accent-ink' => $tone === 'accent',
]) }}>{{ $slot }}</span>
