@php
    $statuses = \App\Models\CabinetAccount::STATUSES;
    $tones = ['pending' => 'warn', 'approved' => 'ok', 'blocked' => 'danger'];
@endphp
<x-admin.page title="Кабинеты партнёров" subtitle="Дилеры и сервисные центры регистрируются на сайте сами. Кабинет открывается после одобрения. Ждут одобрения: {{ $pending }}.">
    <x-admin.table :count="$rows->count()" empty="Кабинетов не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Компания, имя, телефон или почта" />
            <div class="w-full sm:w-52">
                <label class="sr-only" for="filter-status">Статус</label>
                <x-admin.select id="filter-status" wire:model.live="filterStatus" placeholder="Все статусы" :options="$statuses" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="created_at" :by="$sortBy" :dir="$sortDir">Регистрация</x-admin.th>
            <x-admin.th sort="company" :by="$sortBy" :dir="$sortDir">Компания</x-admin.th>
            <x-admin.th>Контакт</x-admin.th>
            <x-admin.th>Заявлено</x-admin.th>
            <x-admin.th>Партнёр</x-admin.th>
            <x-admin.th sort="status" :by="$sortBy" :dir="$sortDir">Статус</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $account)
            <tr wire:key="account-{{ $account->id }}">
                <td class="whitespace-nowrap tabular-nums text-steel">{{ $account->created_at?->format('d.m.Y H:i') }}</td>
                <td>
                    <a href="{{ route('admin.cabinet-accounts.show', $account) }}" class="font-semibold hover:text-accent-ink">{{ $account->company }}</a>
                    <div class="text-xs text-steel">{{ $account->email }}</div>
                </td>
                <td class="whitespace-nowrap">
                    <div>{{ $account->contact_name }}</div>
                    <a href="tel:{{ $account->phone }}" class="text-xs tabular-nums text-steel hover:text-accent-ink">{{ $account->phone }}</a>
                </td>
                <td class="whitespace-nowrap">{{ collect([$account->wants_sales ? 'Продажа' : null, $account->wants_service ? 'Сервис' : null])->filter()->implode(', ') ?: '—' }}</td>
                <td>{{ $account->partner?->tr('name', 'ru') ?: '—' }}</td>
                <td><x-admin.badge :tone="$tones[$account->status] ?? 'neutral'">{{ $statuses[$account->status] ?? $account->status }}</x-admin.badge></td>
                <td class="text-right">
                    <x-admin.btn size="sm" :href="route('admin.cabinet-accounts.show', $account)">Открыть</x-admin.btn>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
