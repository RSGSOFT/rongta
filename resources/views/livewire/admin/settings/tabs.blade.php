{{-- Tabs of the site settings screens (all admins only). @include('livewire.admin.settings.tabs', ['current' => 'admin.settings.trust']) --}}
@php
    $tabs = [
        'admin.settings' => 'Основные',
        'admin.settings.trust' => 'Доверие',
        'admin.settings.maker' => 'О производителе',
    ];
@endphp
<nav class="mb-5 flex flex-wrap gap-1.5" aria-label="Разделы настроек">
    @foreach ($tabs as $route => $label)
        @if (Route::has($route))
            @php $active = ($current ?? null) === $route; @endphp
            <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
               @class([
                   'inline-flex min-h-9 items-center rounded-m border px-3 text-sm font-medium',
                   'border-accent bg-accent-soft text-ink' => $active,
                   'border-line bg-label hover:bg-paper' => ! $active,
               ])>{{ $label }}</a>
        @endif
    @endforeach
</nav>
