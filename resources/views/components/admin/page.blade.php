{{-- Page shell: title, optional subtitle / back link / actions, flash message, then the content. --}}
@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'Назад к списку'])
<div {{ $attributes }}>
    @if ($back)
        <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-xs font-medium text-steel hover:text-ink">
            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
            {{ $backLabel }}
        </a>
    @endif
    <div class="mb-5 flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold leading-tight sm:text-2xl">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-steel">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    <x-admin.flash />

    {{ $slot }}
</div>
