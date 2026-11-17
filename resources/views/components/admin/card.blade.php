{{-- White panel. Optional title / hint header, "tools" slot (header right) and "footer" slot (e.g. form buttons). --}}
@props(['title' => null, 'hint' => null, 'flush' => false])
<section {{ $attributes->class('mb-5 rounded-l border border-line bg-label shadow-card') }}>
    @if ($title || isset($tools))
        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3 sm:px-5">
            <div class="min-w-0">
                <h2 class="font-semibold">{{ $title }}</h2>
                @if ($hint)
                    <p class="mt-0.5 text-xs text-steel">{{ $hint }}</p>
                @endif
            </div>
            @isset($tools)
                <div class="flex flex-wrap items-center gap-2">{{ $tools }}</div>
            @endisset
        </header>
    @endif
    <div @class(['p-4 sm:p-5' => ! $flush])>
        {{ $slot }}
    </div>
    @isset($footer)
        <footer class="flex flex-wrap items-center gap-2 rounded-b-l border-t border-line bg-paper/60 px-4 py-3 sm:px-5">{{ $footer }}</footer>
    @endisset
</section>
