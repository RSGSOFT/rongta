{{--
  Admin panel layout. Data: title (page title), bare (true = no sidebar, used by the login page).
  Livewire pages: ->layout('components.layouts.admin', ['title' => '...']).
--}}
@php
    $title = $title ?? null;
    $bare = $bare ?? false;

    // Admin-editable brand colours (same settings as the public site). Only well-formed hex values are written.
    $cssVars = collect([
        '--accent' => setting('accent'),
        '--accent-ink' => setting('accent_ink'),
        '--accent-soft' => setting('accent_soft'),
    ])->filter(fn ($v) => is_string($v) && preg_match('/^#[0-9A-Fa-f]{6}$/', $v));

    $user = auth()->user();
    $isAdmin = (bool) $user?->isAdmin();

    /*
     | Sidebar: [group label, [[route name, label, admins only], ...]].
     | A name resolves to "{name}.index" when that route exists, otherwise to "{name}".
     | Links whose route is not defined yet are hidden, and so are empty groups.
     */
    $groups = [
        [null, [
            ['admin.dashboard', 'Обзор', false],
        ]],
        ['Товары', [
            ['admin.families', 'Семейства', false],
            ['admin.models', 'Модели весов', false],
            ['admin.documents', 'Документы и файлы', false],
            ['admin.videos', 'Видео', false],
        ]],
        ['Регионы и партнёры', [
            ['admin.regions', 'Регионы', false],
            ['admin.cities', 'Города', false],
            ['admin.partners', 'Партнёры', false],
            ['admin.partner-requests', 'Заявки на партнёрство', false],
        ]],
        ['Контент', [
            ['admin.strings', 'Тексты интерфейса', false],
            ['admin.faq', 'Вопросы и ответы', false],
            ['admin.guide', 'Гид по выбору', false],
            ['admin.chooser', 'Подбор весов', false],
        ]],
        ['Чат-бот', [
            ['admin.leads', 'Лиды', true],
            ['admin.chatbot.conversations', 'Диалоги', true],
            ['admin.chatbot.articles', 'База ответов', false],
            ['admin.chatbot.settings', 'Настройки бота', true],
        ]],
        ['Система', [
            ['admin.settings', 'Настройки сайта', true],
            ['admin.staff', 'Сотрудники', true],
            ['admin.activity', 'Журнал действий', true],
        ]],
    ];

    $nav = [];
    foreach ($groups as [$groupLabel, $links]) {
        $items = [];
        foreach ($links as [$name, $label, $adminOnly]) {
            if ($adminOnly && ! $isAdmin) {
                continue;
            }
            $route = Route::has($name.'.index') ? $name.'.index' : (Route::has($name) ? $name : null);
            if (! $route) {
                continue;
            }
            $items[] = [
                'url' => route($route),
                'label' => $label,
                'active' => request()->routeIs($name, $name.'.*'),
            ];
        }
        if ($items) {
            $nav[] = [$groupLabel, $items];
        }
    }
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ filled($title) ? $title.' · ' : '' }}Панель управления · Rongta Uzbekistan</title>

    <link rel="icon" type="image/png" href="{{ asset('images/rongta-symbol.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700&display=swap">

    @vite(['resources/css/admin.css'])

    @if ($cssVars->isNotEmpty())
        <style>:root{ @foreach ($cssVars as $name => $value){{ $name }}:{{ $value }};@endforeach }</style>
    @endif
</head>
<body class="min-h-screen bg-paper font-sans text-sm text-ink antialiased">
@if ($bare)
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        {{ $slot }}
    </main>
@else
    <a href="#admin-main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-m focus:bg-label focus:px-3 focus:py-2 focus:shadow-card">К содержимому</a>

    <div x-data="{ nav: false }" x-on:keydown.escape.window="nav = false" class="min-h-screen lg:pl-64">
        {{-- Phone top bar --}}
        <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-line bg-label px-4 lg:hidden">
            <button type="button" x-on:click="nav = true" class="-ml-2 inline-flex h-10 w-10 items-center justify-center rounded-m text-ink hover:bg-paper" aria-label="Открыть меню" x-bind:aria-expanded="nav.toString()" aria-controls="admin-nav">
                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <img src="{{ asset('images/rongta-symbol.png') }}" alt="" class="h-6 w-auto">
            <span class="min-w-0 truncate font-semibold">{{ $title ?: 'Панель управления' }}</span>
        </header>

        {{-- Backdrop (phones) --}}
        <div x-cloak x-show="nav" x-transition.opacity x-on:click="nav = false" class="fixed inset-0 z-40 bg-ink/40 lg:hidden" aria-hidden="true"></div>

        <aside id="admin-nav"
               class="fixed inset-y-0 left-0 z-50 flex w-64 max-w-[85vw] -translate-x-full flex-col border-r border-line bg-label transition-transform duration-200 lg:translate-x-0"
               x-bind:class="nav ? '!translate-x-0' : ''">
            <div class="flex h-14 flex-none items-center gap-2.5 border-b border-line px-4">
                <img src="{{ asset('images/rongta-symbol.png') }}" alt="" class="h-7 w-auto">
                <div class="min-w-0 leading-tight">
                    <div class="truncate font-semibold">Rongta Uzbekistan</div>
                    <div class="truncate text-xs text-steel">Панель управления RSG</div>
                </div>
                <button type="button" x-on:click="nav = false" class="ml-auto inline-flex h-9 w-9 items-center justify-center rounded-m text-steel hover:bg-paper lg:hidden" aria-label="Закрыть меню">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-2 py-3" aria-label="Разделы панели">
                {{-- Groups fold: all start closed except the one holding the current page. --}}
                @foreach ($nav as $groupIndex => [$groupLabel, $items])
                    @php $groupOpen = collect($items)->contains('active', true); @endphp
                    <div class="mb-1" @if ($groupLabel) x-data="{ open: {{ $groupOpen ? 'true' : 'false' }} }" data-nav-group="{{ $groupOpen ? 'open' : 'closed' }}" @endif>
                        @if ($groupLabel)
                            <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()"
                                    aria-expanded="{{ $groupOpen ? 'true' : 'false' }}" aria-controls="nav-group-{{ $groupIndex }}"
                                    class="flex w-full items-center justify-between rounded-m px-3 pb-1 pt-2 text-left text-[11px] font-semibold uppercase tracking-wider text-steel hover:text-ink">
                                <span>{{ $groupLabel }}</span>
                                <svg viewBox="0 0 24 24" class="h-4 w-4 flex-none transition-transform motion-reduce:transition-none" x-bind:class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                            </button>
                        @endif
                        <ul @if ($groupLabel) id="nav-group-{{ $groupIndex }}" x-show="open" @unless ($groupOpen) style="display:none" @endunless @endif class="mb-2">
                            @foreach ($items as $item)
                                <li>
                                    <a href="{{ $item['url'] }}"
                                       @if ($item['active']) aria-current="page" @endif
                                       @class([
                                           'flex min-h-9 items-center rounded-m border-l-[3px] px-3 py-1.5 transition-colors',
                                           'border-accent bg-accent-soft font-semibold text-ink' => $item['active'],
                                           'border-transparent text-ink hover:bg-paper' => ! $item['active'],
                                       ])>{{ $item['label'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>

            <div class="flex-none border-t border-line p-3">
                @if ($user)
                    <div class="px-1">
                        <div class="truncate font-medium">{{ $user->name }}</div>
                        <div class="truncate text-xs text-steel">{{ $user->isAdmin() ? 'Администратор' : 'Редактор' }} · {{ $user->email }}</div>
                    </div>
                @endif
                <div class="mt-2 flex items-center gap-2">
                    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="inline-flex min-h-9 flex-1 items-center justify-center rounded-m border border-line px-2 text-xs font-medium hover:bg-paper">Открыть сайт</a>
                    @if (Route::has('admin.logout'))
                        <form method="POST" action="{{ route('admin.logout') }}" class="flex-1">
                            @csrf
                            <button type="submit" class="inline-flex min-h-9 w-full items-center justify-center rounded-m border border-line px-2 text-xs font-medium hover:bg-paper">Выйти</button>
                        </form>
                    @endif
                </div>
            </div>
        </aside>

        <main id="admin-main" class="mx-auto w-full max-w-6xl px-4 py-5 sm:px-6 lg:py-8">
            {{ $slot }}
        </main>
    </div>
@endif
</body>
</html>
