@php
    $state = $serial->warrantyState();
    $start = $serial->warrantyStart();
    $end = $serial->warrantyEnd();
@endphp
<x-admin.page title="Весы {{ $serial->serial }}" :back="route('admin.serials.index')" backLabel="Назад к серийным номерам"
              subtitle="{{ $serial->scaleModel->name }} (в файле: {{ $serial->model_text }})">
    <x-admin.card title="Гарантия" hint="Отсчёт идёт с первой передачи дилеру. Срок: {{ $months }} мес., общий для всех моделей.">
        <div class="flex flex-wrap items-center gap-2" id="serial-warranty">
            @if ($state === 'none')
                <x-admin.badge tone="warn">Не начата: весы ещё не переданы дилеру</x-admin.badge>
            @else
                <x-admin.badge :tone="$state === 'active' ? 'ok' : 'danger'">{{ $state === 'active' ? 'Действует' : 'Закончилась' }}</x-admin.badge>
                <span class="tabular-nums">{{ $start->format('d.m.Y') }} – {{ $end->format('d.m.Y') }}</span>
            @endif
        </div>
    </x-admin.card>

    <x-admin.table>
        <x-slot:toolbar>
            <h2 class="font-semibold">История</h2>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th>Дата</x-admin.th>
            <x-admin.th>Событие</x-admin.th>
            <x-admin.th>Подробности</x-admin.th>
            <x-admin.th>Кто внёс</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>
        <tr>
            <td class="w-28 tabular-nums">{{ $serial->imported_on->format('d.m.Y') }}</td>
            <td class="font-medium">Импортирован RSG</td>
            <td><a class="hover:text-accent-ink" href="{{ route('admin.serials.batches.show', $serial->serial_batch_id) }}">Партия № {{ $serial->serial_batch_id }}, {{ $serial->batch->file_name }}</a></td>
            <td class="text-steel">{{ $serial->batch->user?->name ?? '—' }}</td>
            <td></td>
        </tr>
        @foreach ($serial->movements as $movement)
            <tr wire:key="movement-{{ $movement->id }}">
                <td class="w-28 tabular-nums">{{ $movement->happened_on->format('d.m.Y') }}</td>
                <td class="font-medium">{{ $movement->label() }}</td>
                <td>
                    @if ($movement->partner)
                        <div>{{ $movement->partner->tr('name', 'ru') }}</div>
                    @endif
                    @if ($movement->serviceCase)
                        <div>{{ $movement->serviceCase->tr('title', 'ru') }}</div>
                    @endif
                    @if ($movement->note)
                        <div class="text-xs text-steel">{{ $movement->note }}</div>
                    @endif
                </td>
                <td class="text-steel">{{ $movement->user?->name ?? ($movement->cabinetAccount ? $movement->cabinetAccount->contact_name.' (кабинет партнёра)' : ($movement->partner_id && $movement->type !== \App\Models\SerialMovement::DEALER ? 'Кабинет партнёра' : '—')) }}</td>
                <td class="text-right">
                    <x-admin.btn size="sm" variant="danger" wire:click="deleteMovement({{ $movement->id }})"
                                 confirm="Удалить эту запись из истории? Это действие нельзя отменить.">Удалить</x-admin.btn>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <form wire:submit="addMovement" novalidate>
        <x-admin.card title="Добавить запись">
            <div class="grid gap-4 md:grid-cols-3">
                <x-admin.field label="Событие" for="mv-type" required :error="$errors->first('form.type')">
                    <x-admin.select id="mv-type" wire:model.live="form.type" :options="\App\Models\SerialMovement::TYPES" :invalid="$errors->has('form.type')" />
                </x-admin.field>
                <x-admin.field label="Дата" for="mv-date" required :error="$errors->first('form.happened_on')">
                    <x-admin.input type="date" id="mv-date" wire:model="form.happened_on" max="{{ now()->format('Y-m-d') }}" :invalid="$errors->has('form.happened_on')" />
                </x-admin.field>
                @if ($form['type'] === \App\Models\SerialMovement::DEALER)
                    <x-admin.field label="Дилер" for="mv-partner" required :error="$errors->first('form.partner_id')"
                                   :hint="$dealers ? 'С этой даты идёт гарантия.' : 'Дилеров пока нет: добавьте партнёра с услугой «Продажа» в разделе «Партнёры».'">
                        <x-admin.select id="mv-partner" wire:model="form.partner_id" :options="$dealers" placeholder="Выберите дилера" :invalid="$errors->has('form.partner_id')" />
                    </x-admin.field>
                @else
                    <x-admin.field label="Работа сервиса" for="mv-case" :error="$errors->first('form.service_case_id')">
                        <x-admin.select id="mv-case" wire:model="form.service_case_id" :options="$cases" placeholder="Не указана" :invalid="$errors->has('form.service_case_id')" />
                    </x-admin.field>
                @endif
                <div class="md:col-span-3">
                    <x-admin.field label="Примечание" for="mv-note" :error="$errors->first('form.note')">
                        <x-admin.input id="mv-note" wire:model="form.note" maxlength="500" autocomplete="off" :invalid="$errors->has('form.note')" />
                    </x-admin.field>
                </div>
            </div>
            <x-slot:footer>
                <x-admin.btn type="submit" variant="primary">Добавить в историю</x-admin.btn>
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
