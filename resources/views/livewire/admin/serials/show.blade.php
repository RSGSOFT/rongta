<x-admin.page title="Партия № {{ $batch->id }}" :back="route('admin.serials.index')" backLabel="Назад к серийным номерам"
              subtitle="{{ $batch->file_name }} · загружена {{ $batch->created_at->format('d.m.Y H:i') }}{{ $batch->user ? ' · '.$batch->user->name : '' }}">
    <x-slot:actions>
        <x-admin.btn variant="danger" wire:click="delete"
                     confirm="Удалить партию и все её серийные номера ({{ $batch->rows_added }})? Это действие нельзя отменить.">Удалить партию</x-admin.btn>
    </x-slot:actions>

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <x-admin.badge>Строк в файле: {{ $batch->rows_total }}</x-admin.badge>
        <x-admin.badge tone="ok">Загружено: {{ $batch->rows_added }}</x-admin.badge>
        @if ($batch->rows_skipped)
            <x-admin.badge tone="warn">Пропущено: {{ $batch->rows_skipped }}</x-admin.badge>
        @endif
    </div>

    <x-admin.table :count="$items->total()" empty="В этой партии нет серийных номеров.">
        <x-slot:toolbar>
            <h2 class="font-semibold">Серийные номера партии</h2>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th>Серийный номер</x-admin.th>
            <x-admin.th>Модель</x-admin.th>
            <x-admin.th>Модель в файле</x-admin.th>
            <x-admin.th>Дата импорта</x-admin.th>
        </x-slot:head>
        @foreach ($items as $item)
            <tr wire:key="item-{{ $item->id }}">
                <td class="font-mono text-xs font-semibold"><a class="hover:text-accent-ink" href="{{ route('admin.serials.numbers.show', $item) }}">{{ $item->serial }}</a></td>
                <td>{{ $item->scaleModel->name }}</td>
                <td class="text-steel">{{ $item->model_text }}</td>
                <td class="tabular-nums">{{ $item->imported_on->format('d.m.Y') }}</td>
            </tr>
        @endforeach
        <x-slot:footer>{{ $items->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>

    @if ($batch->skipped)
        <x-admin.table>
            <x-slot:toolbar>
                <div>
                    <h2 class="font-semibold">Строки, которые не загрузились</h2>
                    <p class="text-xs text-steel">@if ($batch->rows_skipped > count($batch->skipped)) Сохранены первые {{ count($batch->skipped) }} из {{ $batch->rows_skipped }}.@else Всего: {{ $batch->rows_skipped }}.@endif</p>
                </div>
            </x-slot:toolbar>
            <x-slot:head>
                <x-admin.th>Строка</x-admin.th>
                <x-admin.th>Серийный номер</x-admin.th>
                <x-admin.th>Причина</x-admin.th>
            </x-slot:head>
            @foreach ($batch->skipped as $problem)
                <tr wire:key="skipped-{{ $problem['line'] }}">
                    <td class="w-20 tabular-nums text-steel">{{ $problem['line'] }}</td>
                    <td class="font-mono text-xs">{{ $problem['serial'] ?: '—' }}</td>
                    <td class="text-warn">{{ $problem['reason'] }}</td>
                </tr>
            @endforeach
        </x-admin.table>
    @endif
</x-admin.page>
