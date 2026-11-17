@php $opt = \App\Livewire\Admin\Partners\Options::class; @endphp
<x-admin.page title="Заявки на партнёрство" subtitle="Отправлены через форму «Стать партнёром» на странице дилеров. Новых: {{ $new }}.">
    <x-admin.table :count="$rows->count()" empty="Заявок не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Компания, имя или телефон" />
            <div class="w-full sm:w-44">
                <label class="sr-only" for="filter-status">Статус</label>
                <x-admin.select id="filter-status" wire:model.live="filterStatus" placeholder="Все статусы" :options="$opt::REQUEST_STATUSES" />
            </div>
            <div class="w-full sm:w-44">
                <label class="sr-only" for="filter-type">Тип</label>
                <x-admin.select id="filter-type" wire:model.live="filterType" placeholder="Все типы" :options="$opt::KINDS" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="created_at" :by="$sortBy" :dir="$sortDir">Получена</x-admin.th>
            <x-admin.th sort="company" :by="$sortBy" :dir="$sortDir">Компания</x-admin.th>
            <x-admin.th>Контакт</x-admin.th>
            <x-admin.th>Тип</x-admin.th>
            <x-admin.th>Регион</x-admin.th>
            <x-admin.th sort="status" :by="$sortBy" :dir="$sortDir">Статус</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $request)
            <tr wire:key="request-{{ $request->id }}">
                <td class="whitespace-nowrap tabular-nums text-steel">{{ $request->created_at?->format('d.m.Y H:i') }}</td>
                <td>
                    <a href="{{ route('admin.partner-requests.show', $request) }}" class="font-semibold hover:text-accent-ink">{{ $request->company }}</a>
                    @if ($request->message)
                        <div class="max-w-xs truncate text-xs text-steel">{{ $request->message }}</div>
                    @endif
                </td>
                <td class="whitespace-nowrap">
                    <div>{{ $request->contact_name }}</div>
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $request->phone) }}" class="text-xs tabular-nums text-steel hover:text-accent-ink">{{ $request->phone }}</a>
                </td>
                <td class="whitespace-nowrap">{{ $opt::KINDS[$request->type] ?? $request->type }}</td>
                <td>{{ $request->region?->tr('name', 'ru') ?: '—' }}</td>
                <td><x-admin.badge :tone="$opt::REQUEST_TONES[$request->status] ?? 'neutral'">{{ $opt::REQUEST_STATUSES[$request->status] ?? $request->status }}</x-admin.badge></td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        @if ($request->status === 'new')
                            <x-admin.btn size="sm" wire:click="setStatus({{ $request->id }}, 'in_progress')">В работу</x-admin.btn>
                        @elseif ($request->status === 'in_progress')
                            <x-admin.btn size="sm" wire:click="setStatus({{ $request->id }}, 'done')">Обработана</x-admin.btn>
                        @endif
                        <x-admin.btn size="sm" :href="route('admin.partner-requests.show', $request)">Открыть</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
