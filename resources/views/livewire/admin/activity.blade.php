@php $labels = \App\Livewire\Admin\Activity\Labels::class; @endphp
<x-admin.page title="Журнал действий" subtitle="Кто и что менял в панели управления. Журнал только для чтения.">
    <x-admin.table :count="$rows->count()" empty="Записей не найдено.">
        <x-slot:toolbar>
            <x-admin.field label="Сотрудник" for="a-user" class="w-full sm:w-48">
                <x-admin.select id="a-user" wire:model.live="filterUser" placeholder="Все" :options="$users" />
            </x-admin.field>
            <x-admin.field label="Действие" for="a-action" class="w-full sm:w-44">
                <x-admin.select id="a-action" wire:model.live="filterAction" placeholder="Все" :options="$actions" />
            </x-admin.field>
            <x-admin.field label="Объект" for="a-subject" class="w-full sm:w-48">
                <x-admin.select id="a-subject" wire:model.live="filterSubject" placeholder="Все" :options="$subjects" />
            </x-admin.field>
            <x-admin.field label="С даты" for="a-from" class="w-[calc(50%-0.375rem)] sm:w-40">
                <x-admin.input type="date" id="a-from" wire:model.live="filterFrom" />
            </x-admin.field>
            <x-admin.field label="По дату" for="a-to" class="w-[calc(50%-0.375rem)] sm:w-40">
                <x-admin.input type="date" id="a-to" wire:model.live="filterTo" />
            </x-admin.field>
            @if ($filtered)
                <x-admin.btn wire:click="clearFilters">Сбросить</x-admin.btn>
            @endif
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th>Когда</x-admin.th>
            <x-admin.th>Сотрудник</x-admin.th>
            <x-admin.th>Действие</x-admin.th>
            <x-admin.th>Объект</x-admin.th>
            <x-admin.th>Изменения</x-admin.th>
        </x-slot:head>

        @foreach ($rows as $log)
            <tr wire:key="log-{{ $log->id }}" class="align-top">
                <td class="whitespace-nowrap tabular-nums">{{ $log->created_at?->format('d.m.Y H:i:s') }}</td>
                <td>{{ $log->user?->name ?? 'система' }}</td>
                <td>
                    <x-admin.badge :tone="match ($log->action) { 'created', 'published', 'activated' => 'ok', 'deleted', 'deactivated', 'file_removed' => 'danger', 'login', 'logout' => 'neutral', default => 'accent' }">{{ $labels::action($log->action) }}</x-admin.badge>
                </td>
                <td class="whitespace-nowrap">{{ $labels::describe($log) ?: '—' }}</td>
                <td class="min-w-56">
                    @if ($log->changes)
                        <details>
                            <summary class="cursor-pointer text-xs font-semibold text-accent-ink">{{ implode(', ', array_slice(array_keys($log->changes), 0, 6)) }}{{ count($log->changes) > 6 ? '…' : '' }}</summary>
                            <pre class="mt-1 max-h-64 max-w-md overflow-auto whitespace-pre-wrap break-words rounded-m bg-paper p-2 text-[11px] leading-snug">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    @else
                        <span class="text-steel">—</span>
                    @endif
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
