<x-admin.page title="Гид по выбору" subtitle="Раздел базы знаний: сравнение семейств весов и список «Проверьте перед покупкой».">
    <x-admin.table :count="$entries->count()" empty="Разделов гида пока нет.">
        <x-slot:toolbar>
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold">Семейства в гиде</h2>
                <p class="text-xs text-steel">По одному разделу на семейство. Семейств без раздела: {{ $freeFamilies }}.</p>
            </div>
            @if ($freeFamilies > 0)
                <x-admin.btn size="sm" variant="primary" :href="route('admin.guide.entries.create')">Добавить раздел</x-admin.btn>
            @endif
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th>№</x-admin.th>
            <x-admin.th>Семейство</x-admin.th>
            <x-admin.th>Переводы</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($entries as $entry)
            @php
                $missing = collect(['ru', 'uz', 'en'])->filter(fn ($l) => collect(['does', 'fits', 'limit', 'peak'])->contains(fn ($f) => blank($entry->{$f}[$l] ?? null)))->map(fn ($l) => strtoupper($l));
            @endphp
            <tr wire:key="entry-{{ $entry->id }}">
                <td class="w-12 tabular-nums text-steel">{{ $entry->sort }}</td>
                <td>
                    <a href="{{ route('admin.guide.entries.edit', $entry) }}" class="font-semibold hover:text-accent-ink">{{ $entry->family?->tr('name', 'ru') ?: 'Семейство удалено' }}</a>
                    <div class="line-clamp-1 max-w-xl text-xs text-steel">{{ $entry->tr('does', 'ru') }}</div>
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
                        <x-admin.btn size="sm" :href="route('admin.guide.entries.edit', $entry)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="deleteEntry({{ $entry->id }})"
                                     confirm="Удалить раздел гида для этого семейства? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <x-admin.table :count="$checks->count()" empty="Пунктов пока нет.">
        <x-slot:toolbar>
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold">Список «Проверьте перед покупкой»</h2>
                <p class="text-xs text-steel">Показывается под гидом в том же порядке.</p>
            </div>
            <x-admin.btn size="sm" variant="primary" :href="route('admin.guide.checks.create')">Добавить пункт</x-admin.btn>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th>№</x-admin.th>
            <x-admin.th>Текст</x-admin.th>
            <x-admin.th>Переводы</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($checks as $check)
            @php $missing = collect(['ru', 'uz', 'en'])->filter(fn ($l) => blank($check->text[$l] ?? null))->map(fn ($l) => strtoupper($l)); @endphp
            <tr wire:key="check-{{ $check->id }}">
                <td class="w-12 tabular-nums text-steel">{{ $check->sort }}</td>
                <td><a href="{{ route('admin.guide.checks.edit', $check) }}" class="font-medium hover:text-accent-ink">{{ $check->tr('text', 'ru') }}</a></td>
                <td>
                    @if ($missing->isEmpty())
                        <x-admin.badge tone="ok">RU · UZ · EN</x-admin.badge>
                    @else
                        <x-admin.badge tone="warn">нет: {{ $missing->implode(', ') }}</x-admin.badge>
                    @endif
                </td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" :href="route('admin.guide.checks.edit', $check)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="deleteCheck({{ $check->id }})"
                                     confirm="Удалить этот пункт? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>
</x-admin.page>
