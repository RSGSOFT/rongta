<x-admin.page title="Сервис и ремонт" subtitle="Работы сервиса и тариф на каждую, в сумах. На сайте (База знаний) видны только опубликованные работы с тарифом. Сейчас на сайте: {{ $listed }} из {{ $total }}.">
    @foreach ($groups as $group)
        <x-admin.table :count="$group['cases']->count()" empty="В этой группе работ нет." wire:key="group-{{ $group['id'] }}">
            <x-slot:toolbar>
                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold">{{ $group['name'] }}</h2>
                    <p class="text-xs text-steel">Впишите тариф в строке: он сохраняется сразу. Пустое поле убирает работу с сайта.</p>
                </div>
                <x-admin.btn size="sm" variant="primary" :href="route('admin.service-cases.create', $group['id'] ? ['family' => $group['id']] : [])">Добавить работу</x-admin.btn>
            </x-slot:toolbar>
            <x-slot:head>
                <x-admin.th>№</x-admin.th>
                <x-admin.th>Работа</x-admin.th>
                <x-admin.th>Тариф, сум</x-admin.th>
                <x-admin.th>Показ</x-admin.th>
                <x-admin.th>На сайте</x-admin.th>
                <x-admin.th>Переводы</x-admin.th>
                <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
            </x-slot:head>

            @foreach ($group['cases'] as $case)
                @php
                    $hasNote = filled($case->note['ru'] ?? null);
                    $missing = collect(['ru', 'uz', 'en'])
                        ->filter(fn ($l) => blank($case->title[$l] ?? null) || ($hasNote && blank($case->note[$l] ?? null)))
                        ->map(fn ($l) => strtoupper($l));
                @endphp
                <tr wire:key="case-{{ $case->id }}">
                    <td class="w-12 tabular-nums text-steel">{{ $case->sort }}</td>
                    <td>
                        <a href="{{ route('admin.service-cases.edit', $case) }}" class="font-medium hover:text-accent-ink">{{ $case->tr('title', 'ru') }}</a>
                        @if ($hasNote)
                            <div class="text-xs text-steel">{{ $case->tr('note', 'ru') }}</div>
                        @endif
                    </td>
                    <td class="w-44">
                        <div class="flex items-center gap-1.5">
                            @if ($case->is_from)
                                <span class="text-xs text-steel">от</span>
                            @endif
                            <x-admin.input inputmode="numeric" class="text-right tabular-nums" id="amount-{{ $case->id }}"
                                           aria-label="Тариф, сум: {{ $case->tr('title', 'ru') }}" autocomplete="off"
                                           value="{{ $case->amount }}" wire:change="setAmount({{ $case->id }}, $event.target.value)"
                                           wire:key="amount-{{ $case->id }}-{{ $case->amount }}" :invalid="$errors->has('amount.'.$case->id)" />
                        </div>
                        @error('amount.'.$case->id)
                            <p class="mt-1 text-xs text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </td>
                    <td>
                        <x-admin.toggle wire:click="togglePublished({{ $case->id }})" :checked="$case->is_published" wire:key="pub-{{ $case->id }}-{{ (int) $case->is_published }}">
                            <span class="sr-only">Опубликована: {{ $case->tr('title', 'ru') }}</span>
                        </x-admin.toggle>
                    </td>
                    <td>
                        @if ($case->isListed())
                            <x-admin.badge tone="ok">Видна</x-admin.badge>
                        @elseif ($case->amount === null)
                            <x-admin.badge tone="warn">Нет тарифа</x-admin.badge>
                        @else
                            <x-admin.badge tone="warn">Скрыта</x-admin.badge>
                        @endif
                    </td>
                    <td>
                        @if ($missing->isEmpty())
                            <x-admin.badge tone="ok">RU · UZ · EN</x-admin.badge>
                        @else
                            <x-admin.badge tone="warn">нет: {{ $missing->implode(', ') }}</x-admin.badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="inline-flex gap-1.5">
                            <x-admin.btn size="sm" :href="route('admin.service-cases.edit', $case)">Изменить</x-admin.btn>
                            <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $case->id }})"
                                         confirm="Удалить эту работу? Это действие нельзя отменить.">Удалить</x-admin.btn>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>
    @endforeach
</x-admin.page>
