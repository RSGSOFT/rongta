<x-admin.page title="Обзор" subtitle="Что сейчас требует внимания на сайте Rongta Uzbekistan.">
    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($cards as $card)
            @php $tag = $card['url'] ? 'a' : 'div'; @endphp
            <{{ $tag }} @if ($card['url']) href="{{ $card['url'] }}" @endif
                @class([
                    'block rounded-l border bg-label p-4 shadow-card transition-colors',
                    'border-line' => ! $card['attention'],
                    'border-[#E2B93B]' => $card['attention'],
                    'hover:border-accent-ink' => $card['url'],
                ])>
                <div class="flex items-start justify-between gap-2">
                    <span class="text-xs font-semibold text-steel">{{ $card['label'] }}</span>
                    @if ($card['attention'])
                        <x-admin.badge tone="warn">проверить</x-admin.badge>
                    @endif
                </div>
                <div class="mt-2 text-3xl font-semibold tabular-nums leading-none">{{ $card['value'] }}</div>
                <div class="mt-2 text-xs text-steel">{{ $card['note'] }}</div>
                @if ($card['url'])
                    <div class="mt-3 text-xs font-semibold text-accent-ink">Открыть →</div>
                @endif
            </{{ $tag }}>
        @endforeach
    </div>

    <x-admin.card :title="auth()->user()->isAdmin() ? 'Последние действия' : 'Мои последние действия'" flush>
        @if (auth()->user()->isAdmin() && Route::has('admin.activity'))
            <x-slot:tools>
                <x-admin.btn size="sm" :href="route('admin.activity')">Весь журнал</x-admin.btn>
            </x-slot:tools>
        @endif
        @if ($recent->isEmpty())
            <p class="px-4 py-6 text-center text-steel">Действий пока нет.</p>
        @else
            <ul class="divide-y divide-paper-2">
                @foreach ($recent as $log)
                    <li class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5 px-4 py-2.5 sm:px-5">
                        <span class="font-medium">{{ \App\Livewire\Admin\Activity\Labels::action($log->action) }}</span>
                        <span class="text-steel">{{ \App\Livewire\Admin\Activity\Labels::describe($log) }}</span>
                        <span class="ml-auto text-xs text-steel">{{ $log->user?->name ?? 'система' }} · {{ $log->created_at?->format('d.m.Y H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
</x-admin.page>
