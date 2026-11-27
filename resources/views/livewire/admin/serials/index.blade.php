<x-admin.page title="Серийные номера" subtitle="Весы, которые импортировала RSG. Каждая загрузка файла — партия. Всего серийных номеров: {{ number_format($total, 0, '', ' ') }}.">
    <x-slot:actions>
        <x-admin.btn :href="route('admin.serials.handover')">Передать дилеру</x-admin.btn>
        <x-admin.btn variant="primary" :href="route('admin.serials.import')">Загрузить файл</x-admin.btn>
    </x-slot:actions>

    <form wire:submit="saveWarranty" novalidate>
        <x-admin.card title="Срок гарантии" hint="Один для всех моделей. Отсчёт идёт с даты передачи весов дилеру.">
            <div class="flex flex-wrap items-end gap-3">
                <x-admin.field label="Месяцев" for="warranty-months" required :error="$errors->first('warrantyMonths')">
                    <x-admin.input type="number" min="1" max="120" step="1" id="warranty-months" class="w-28" wire:model="warrantyMonths" :invalid="$errors->has('warrantyMonths')" />
                </x-admin.field>
                <x-admin.btn type="submit">Сохранить</x-admin.btn>
            </div>
        </x-admin.card>
    </form>

    <x-admin.table :count="$needle === '' ? null : $found->count()" empty="Такого серийного номера в базе нет.">
        <x-slot:toolbar>
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold">Найти серийный номер</h2>
                <p class="text-xs text-steel">Введите номер или его часть.</p>
            </div>
            <x-admin.search wire:model.live.debounce.300ms="search" placeholder="Серийный номер" label="Серийный номер" maxlength="64" />
        </x-slot:toolbar>
        @if ($found->isNotEmpty())
            <x-slot:head>
                <x-admin.th>Серийный номер</x-admin.th>
                <x-admin.th>Модель</x-admin.th>
                <x-admin.th>Дата импорта</x-admin.th>
                <x-admin.th>Гарантия</x-admin.th>
                <x-admin.th>Партия</x-admin.th>
            </x-slot:head>
            @foreach ($found as $item)
                <tr wire:key="found-{{ $item->id }}">
                    <td class="font-mono text-xs font-semibold"><a class="hover:text-accent-ink" href="{{ route('admin.serials.numbers.show', $item) }}">{{ $item->serial }}</a></td>
                    <td>{{ $item->scaleModel->name }} <span class="text-xs text-steel">({{ $item->model_text }})</span></td>
                    <td class="tabular-nums">{{ $item->imported_on->format('d.m.Y') }}</td>
                    <td>
                        @php $state = $item->warrantyState(); @endphp
                        @if ($state === 'none')
                            <x-admin.badge>Не начата</x-admin.badge>
                        @else
                            <x-admin.badge :tone="$state === 'active' ? 'ok' : 'danger'">до {{ $item->warrantyEnd()->format('d.m.Y') }}</x-admin.badge>
                        @endif
                    </td>
                    <td><a class="hover:text-accent-ink" href="{{ route('admin.serials.batches.show', $item->serial_batch_id) }}">№ {{ $item->serial_batch_id }}, {{ $item->batch->file_name }}</a></td>
                </tr>
            @endforeach
        @endif
    </x-admin.table>

    <x-admin.table :count="$batches->total()" empty="Партий пока нет. Загрузите первый файл.">
        <x-slot:toolbar>
            <h2 class="font-semibold">Партии</h2>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th>№</x-admin.th>
            <x-admin.th>Загружена</x-admin.th>
            <x-admin.th>Файл</x-admin.th>
            <x-admin.th>Кто загрузил</x-admin.th>
            <x-admin.th align="right">Номеров</x-admin.th>
            <x-admin.th align="right">Пропущено строк</x-admin.th>
        </x-slot:head>
        @foreach ($batches as $batch)
            <tr wire:key="batch-{{ $batch->id }}">
                <td class="w-14 tabular-nums text-steel">{{ $batch->id }}</td>
                <td class="tabular-nums">{{ $batch->created_at->format('d.m.Y H:i') }}</td>
                <td><a href="{{ route('admin.serials.batches.show', $batch) }}" class="font-medium hover:text-accent-ink">{{ $batch->file_name }}</a></td>
                <td>{{ $batch->user?->name ?? '—' }}</td>
                <td class="text-right font-medium tabular-nums">{{ $batch->rows_added }}</td>
                <td class="text-right tabular-nums">{{ $batch->rows_skipped }}</td>
            </tr>
        @endforeach
        <x-slot:footer>{{ $batches->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
