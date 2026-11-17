@php $opt = \App\Livewire\Admin\Partners\Options::class; @endphp
<x-admin.page title="Партнёры" subtitle="Реселлеры и интеграторы ПО на странице дилеров.">
    <x-slot:actions>
        <x-admin.btn variant="primary" :href="route('admin.partners.create', array_filter(['kind' => $filterKind]))">Добавить партнёра</x-admin.btn>
    </x-slot:actions>

    <x-admin.table :count="$rows->count()" empty="Партнёров не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Название, телефон или Telegram" />
            <div class="w-full sm:w-44">
                <label class="sr-only" for="filter-kind">Тип</label>
                <x-admin.select id="filter-kind" wire:model.live="filterKind" placeholder="Все типы" :options="$opt::KINDS" />
            </div>
            <div class="w-full sm:w-56">
                <label class="sr-only" for="filter-region">Регион</label>
                <x-admin.select id="filter-region" wire:model.live="filterRegion" placeholder="Все регионы" :options="$regions" />
            </div>
            <div class="w-full sm:w-48">
                <label class="sr-only" for="filter-status">Статус</label>
                <x-admin.select id="filter-status" wire:model.live="filterStatus" placeholder="Статус: любой"
                                :options="['published' => 'Опубликованные', 'hidden' => 'Скрытые', 'sample' => 'Образцы', 'real' => 'Настоящие']" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="sort" :by="$sortBy ?: 'sort'" :dir="$sortDir">№</x-admin.th>
            <x-admin.th>Партнёр</x-admin.th>
            <x-admin.th sort="kind" :by="$sortBy" :dir="$sortDir">Тип</x-admin.th>
            <x-admin.th>Регион, город</x-admin.th>
            <x-admin.th>Контакты</x-admin.th>
            <x-admin.th>На сайте</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $partner)
            @php
                $missing = collect(['ru', 'uz', 'en'])->filter(fn ($l) => blank($partner->name[$l] ?? null))->map(fn ($l) => strtoupper($l));
                $where = collect([$partner->region?->tr('name', 'ru'), $partner->city?->tr('name', 'ru')])->filter()->implode(', ');
            @endphp
            <tr wire:key="partner-{{ $partner->id }}">
                <td class="w-12 tabular-nums text-steel">{{ $partner->sort }}</td>
                <td>
                    <a href="{{ route('admin.partners.edit', $partner) }}" class="font-semibold hover:text-accent-ink">{{ $partner->tr('name', 'ru') }}</a>
                    @if ($partner->is_placeholder)
                        <x-admin.badge tone="warn">образец</x-admin.badge>
                    @endif
                    @if ($missing->isNotEmpty())
                        <x-admin.badge tone="warn">нет: {{ $missing->implode(', ') }}</x-admin.badge>
                    @endif
                    @if ($partner->offers)
                        <div class="text-xs text-steel">{{ collect($partner->offers)->map(fn ($o) => $opt::OFFERS[$o] ?? $o)->implode(' · ') }}</div>
                    @endif
                </td>
                <td class="whitespace-nowrap">{{ $opt::KINDS[$partner->kind] ?? $partner->kind }}</td>
                <td>
                    @if ($where !== '')
                        {{ $where }}
                    @else
                        <x-admin.badge tone="warn">регион не указан</x-admin.badge>
                    @endif
                </td>
                <td class="whitespace-nowrap text-xs">
                    @if ($partner->phone || $partner->telegram)
                        <div class="tabular-nums">{{ $partner->phone }}</div>
                        <div class="text-steel">{{ $partner->telegram }}</div>
                    @else
                        <span class="text-steel">нет</span>
                    @endif
                </td>
                <td>
                    <button type="button" wire:click="togglePublished({{ $partner->id }})" class="rounded-full"
                            title="{{ $partner->is_published ? 'Скрыть с сайта' : 'Опубликовать' }}"
                            aria-label="{{ $partner->tr('name', 'ru') }}: {{ $partner->is_published ? 'опубликован, скрыть с сайта' : 'скрыт, опубликовать' }}">
                        <x-admin.badge :tone="$partner->is_published ? 'ok' : 'neutral'">{{ $partner->is_published ? 'опубликован' : 'скрыт' }}</x-admin.badge>
                    </button>
                </td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" :href="route('admin.partners.edit', $partner)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $partner->id }})"
                                     confirm="Удалить партнёра «{{ $partner->tr('name', 'ru') }}»? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
