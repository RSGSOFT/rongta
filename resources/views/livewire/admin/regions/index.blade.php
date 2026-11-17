<x-admin.page title="Регионы" subtitle="Области на карте и в фильтре страницы дилеров.">
    <x-slot:actions>
        <x-admin.btn variant="primary" :href="route('admin.regions.create')">Добавить регион</x-admin.btn>
    </x-slot:actions>

    <x-admin.card title="Карта регионов" hint="Так клетки расположены на странице дилеров. Положение меняется в карточке региона.">
        @include('livewire.admin.regions.map', ['regions' => $map, 'tile' => null])
    </x-admin.card>

    <x-admin.table :count="$rows->count()" empty="Регионов не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Название или код" />
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="sort" :by="$sortBy ?: 'sort'" :dir="$sortDir">№</x-admin.th>
            <x-admin.th>Название</x-admin.th>
            <x-admin.th sort="slug" :by="$sortBy" :dir="$sortDir">Код</x-admin.th>
            <x-admin.th>Клетка</x-admin.th>
            <x-admin.th>Городов</x-admin.th>
            <x-admin.th>Партнёров</x-admin.th>
            <x-admin.th>Переводы</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $region)
            @php
                $missing = collect(['ru', 'uz', 'en'])->filter(fn ($l) => blank($region->name[$l] ?? null) || blank($region->short[$l] ?? null))->map(fn ($l) => strtoupper($l));
            @endphp
            <tr wire:key="region-{{ $region->id }}">
                <td class="w-12 tabular-nums text-steel">{{ $region->sort }}</td>
                <td>
                    <a href="{{ route('admin.regions.edit', $region) }}" class="font-semibold hover:text-accent-ink">{{ $region->tr('name', 'ru') }}</a>
                    <div class="text-xs text-steel">{{ $region->tr('short', 'ru') }}</div>
                </td>
                <td><code class="text-xs">{{ $region->slug }}</code></td>
                <td class="whitespace-nowrap tabular-nums text-steel">кол. {{ $region->map_col }}, ряд {{ $region->map_row }}</td>
                <td class="tabular-nums">
                    <a href="{{ route('admin.cities.index', ['region' => $region->id]) }}" class="hover:text-accent-ink hover:underline">{{ $region->cities_count }}</a>
                </td>
                <td class="tabular-nums">
                    <a href="{{ route('admin.partners.index', ['region' => $region->id]) }}" class="hover:text-accent-ink hover:underline">{{ $region->partners_count }}</a>
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
                        <x-admin.btn size="sm" :href="route('admin.regions.edit', $region)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $region->id }})"
                                     confirm="Удалить регион «{{ $region->tr('name', 'ru') }}»? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
