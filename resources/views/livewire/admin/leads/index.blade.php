@php $opt = \App\Livewire\Admin\Partners\Options::class; @endphp
<x-admin.page title="Лиды" subtitle="Контакты, которые посетители оставили перед разговором с чат-ботом. Всего: {{ $total }}.">
    <x-slot:actions>
        <x-admin.btn wire:click="export" wire:loading.attr="disabled" wire:target="export" :disabled="$rows->total() === 0">
            Скачать CSV ({{ $rows->total() }})
        </x-admin.btn>
    </x-slot:actions>

    <x-admin.table :count="$rows->count()" empty="Лидов не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Имя, фамилия или телефон" />
            <div class="w-full sm:w-40">
                <label class="sr-only" for="filter-locale">Язык</label>
                <x-admin.select id="filter-locale" wire:model.live="filterLocale" placeholder="Все языки" :options="$opt::LOCALES" />
            </div>
            <div class="w-[calc(50%-0.375rem)] sm:w-40">
                <label class="mb-1 block text-xs font-semibold" for="filter-from">С даты</label>
                <x-admin.input type="date" id="filter-from" wire:model.live="filterFrom" />
            </div>
            <div class="w-[calc(50%-0.375rem)] sm:w-40">
                <label class="mb-1 block text-xs font-semibold" for="filter-to">По дату</label>
                <x-admin.input type="date" id="filter-to" wire:model.live="filterTo" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="created_at" :by="$sortBy" :dir="$sortDir">Дата</x-admin.th>
            <x-admin.th sort="last_name" :by="$sortBy" :dir="$sortDir">Имя и фамилия</x-admin.th>
            <x-admin.th>Телефон</x-admin.th>
            <x-admin.th>Язык</x-admin.th>
            <x-admin.th>Источник</x-admin.th>
            <x-admin.th>Диалогов</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $lead)
            <tr wire:key="lead-{{ $lead->id }}">
                <td class="whitespace-nowrap tabular-nums text-steel">{{ $lead->created_at?->format('d.m.Y H:i') }}</td>
                <td><a href="{{ route('admin.leads.show', $lead) }}" class="font-semibold hover:text-accent-ink">{{ $lead->first_name }} {{ $lead->last_name }}</a></td>
                <td class="whitespace-nowrap"><a href="tel:{{ preg_replace('/[^\d+]/', '', $lead->phone) }}" class="tabular-nums hover:text-accent-ink">{{ $lead->phone }}</a></td>
                <td class="uppercase text-steel">{{ $lead->locale ?: '—' }}</td>
                <td class="text-steel">{{ $lead->source ?: '—' }}</td>
                <td class="tabular-nums">{{ $lead->conversations_count }}</td>
                <td class="text-right"><x-admin.btn size="sm" :href="route('admin.leads.show', $lead)">Открыть</x-admin.btn></td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
