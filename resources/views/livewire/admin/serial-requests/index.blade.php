@php
    $statuses = \App\Models\SerialDetailRequest::STATUSES;
    $tones = ['new' => 'warn', 'granted' => 'ok', 'refused' => 'neutral'];
@endphp
<x-admin.page title="Запросы по весам" subtitle="Сервисный центр видит полную историю только тех весов, которые сам принимал. По остальным он просит доступ здесь. Новых: {{ $new }}.">
    <x-admin.table :count="$rows->count()" empty="Запросов нет.">
        <x-slot:toolbar>
            <div class="w-full sm:w-52">
                <label class="sr-only" for="filter-status">Статус</label>
                <x-admin.select id="filter-status" wire:model.live="filterStatus" placeholder="Все статусы" :options="$statuses" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th>Получен</x-admin.th>
            <x-admin.th>Сервисный центр</x-admin.th>
            <x-admin.th>Весы</x-admin.th>
            <x-admin.th>Зачем</x-admin.th>
            <x-admin.th>Статус</x-admin.th>
            <x-admin.th>Решение</x-admin.th>
        </x-slot:head>

        @foreach ($rows as $request)
            <tr wire:key="request-{{ $request->id }}">
                <td class="whitespace-nowrap tabular-nums text-steel">{{ $request->created_at->format('d.m.Y H:i') }}</td>
                <td>
                    <div class="font-medium">{{ $request->partner->tr('name', 'ru') }}</div>
                    @if ($request->cabinetAccount)
                        <a class="text-xs text-steel hover:text-accent-ink" href="{{ route('admin.cabinet-accounts.show', $request->cabinetAccount) }}">{{ $request->cabinetAccount->contact_name }}, {{ $request->cabinetAccount->phone }}</a>
                    @endif
                </td>
                <td>
                    <a class="font-mono text-xs font-semibold hover:text-accent-ink" href="{{ route('admin.serials.numbers.show', $request->serialNumber) }}">{{ $request->serialNumber->serial }}</a>
                    <div class="text-xs text-steel">{{ $request->serialNumber->scaleModel->name }}</div>
                </td>
                <td class="max-w-xs text-sm">{{ $request->note ?: '—' }}</td>
                <td>
                    <x-admin.badge :tone="$tones[$request->status] ?? 'neutral'">{{ $statuses[$request->status] ?? $request->status }}</x-admin.badge>
                    @if ($request->decided_at)
                        <div class="mt-0.5 text-xs text-steel">{{ $request->decided_at->format('d.m.Y') }}{{ $request->decidedBy ? ', '.$request->decidedBy->name : '' }}</div>
                    @endif
                </td>
                <td class="min-w-64">
                    @if ($request->status === 'new')
                        <label class="sr-only" for="answer-{{ $request->id }}">Ответ сервисному центру</label>
                        <x-admin.input id="answer-{{ $request->id }}" wire:model="answers.{{ $request->id }}" maxlength="500" placeholder="Ответ центру (необязательно)" autocomplete="off" :invalid="$errors->has('answers.'.$request->id)" />
                        @error('answers.'.$request->id) <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        <div class="mt-1.5 inline-flex gap-1.5">
                            <x-admin.btn size="sm" variant="primary" wire:click="decide({{ $request->id }}, 'granted')">Открыть доступ</x-admin.btn>
                            <x-admin.btn size="sm" wire:click="decide({{ $request->id }}, 'refused')">Отказать</x-admin.btn>
                        </div>
                    @else
                        @if ($request->answer)
                            <div class="text-sm">{{ $request->answer }}</div>
                        @endif
                        <x-admin.btn size="sm" class="mt-1" wire:click="decide({{ $request->id }}, '{{ $request->status === 'granted' ? 'refused' : 'granted' }}')"
                                     confirm="{{ $request->status === 'granted' ? 'Закрыть этому центру историю весов?' : 'Открыть этому центру историю весов?' }}">{{ $request->status === 'granted' ? 'Закрыть доступ' : 'Открыть доступ' }}</x-admin.btn>
                    @endif
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
