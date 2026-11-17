<x-admin.page title="Семейства" subtitle="Группы весов в каталоге и на главной странице.">
    <x-slot:actions>
        <x-admin.btn variant="primary" :href="route('admin.families.create')">Добавить семейство</x-admin.btn>
    </x-slot:actions>

    <x-admin.table :count="$rows->count()" empty="Семейств пока нет.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Название или код" />
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="sort" :by="$sortBy ?: 'sort'" :dir="$sortDir">№</x-admin.th>
            <x-admin.th>Название</x-admin.th>
            <x-admin.th sort="slug" :by="$sortBy" :dir="$sortDir">Код</x-admin.th>
            <x-admin.th>Моделей</x-admin.th>
            <x-admin.th>Переводы</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $family)
            @php
                $missing = collect(['ru', 'uz', 'en'])->filter(fn ($l) => blank($family->name[$l] ?? null) || blank($family->line[$l] ?? null))->map(fn ($l) => strtoupper($l));
            @endphp
            <tr wire:key="family-{{ $family->id }}">
                <td class="w-12 tabular-nums text-steel">{{ $family->sort }}</td>
                <td>
                    <a href="{{ route('admin.families.edit', $family) }}" class="font-semibold hover:text-accent-ink">{{ $family->tr('name', 'ru') }}</a>
                    <div class="max-w-md truncate text-xs text-steel">{{ $family->tr('line', 'ru') }}</div>
                </td>
                <td><code class="text-xs">{{ $family->slug }}</code></td>
                <td class="tabular-nums">{{ $family->scale_models_count }}</td>
                <td>
                    @if ($missing->isEmpty())
                        <x-admin.badge tone="ok">RU · UZ · EN</x-admin.badge>
                    @else
                        <x-admin.badge tone="warn">нет: {{ $missing->implode(', ') }}</x-admin.badge>
                    @endif
                </td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" :href="route('admin.families.edit', $family)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $family->id }})"
                                     confirm="Удалить семейство «{{ $family->tr('name', 'ru') }}»? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
