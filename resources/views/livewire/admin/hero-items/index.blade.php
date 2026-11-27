<x-admin.page title="Образцы для весов" subtitle="Товары-примеры для табло весов и этикетки на главной странице. При каждой загрузке страницы показывается один случайный опубликованный образец. Валюта не указывается.">
    <x-admin.table :count="$items->count()" empty="Образцов пока нет: главная показывает стандартную этикетку.">
        <x-slot:toolbar>
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold">Образцы</h2>
                <p class="text-xs text-steel">Опубликовано: {{ $items->where('is_published', true)->count() }} из {{ $items->count() }}.</p>
            </div>
            <x-admin.btn size="sm" variant="primary" :href="route('admin.hero-items.create')">Добавить образец</x-admin.btn>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th>№</x-admin.th>
            <x-admin.th>Название</x-admin.th>
            <x-admin.th align="right">Вес, кг</x-admin.th>
            <x-admin.th align="right">За кг</x-admin.th>
            <x-admin.th align="right">Итог</x-admin.th>
            <x-admin.th>Показ</x-admin.th>
            <x-admin.th>Переводы</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($items as $item)
            @php $missing = collect(['ru', 'uz', 'en'])->filter(fn ($l) => blank($item->name[$l] ?? null))->map(fn ($l) => strtoupper($l)); @endphp
            <tr wire:key="hero-item-{{ $item->id }}">
                <td class="w-12 tabular-nums text-steel">{{ $item->sort }}</td>
                <td>
                    <a href="{{ route('admin.hero-items.edit', $item) }}" class="font-medium hover:text-accent-ink">{{ $item->tr('name', 'ru') }}</a>
                    <div class="text-xs text-steel">{{ $item->brandLine() }}</div>
                </td>
                <td class="text-right tabular-nums">{{ $item->weightKg() }}</td>
                <td class="text-right tabular-nums">{{ $item::spaced($item->unit_price) }}</td>
                <td class="text-right font-medium tabular-nums">{{ $item::spaced($item->total()) }}</td>
                <td>
                    <x-admin.toggle wire:click="togglePublished({{ $item->id }})" :checked="$item->is_published" wire:key="pub-{{ $item->id }}-{{ (int) $item->is_published }}">
                        <span class="sr-only">Опубликован: {{ $item->tr('name', 'ru') }}</span>
                    </x-admin.toggle>
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
                        <x-admin.btn size="sm" :href="route('admin.hero-items.edit', $item)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $item->id }})"
                                     confirm="Удалить этот образец? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>
</x-admin.page>
