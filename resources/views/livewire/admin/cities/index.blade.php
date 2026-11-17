<x-admin.page title="Города" subtitle="Города и районы, которые указываются у партнёров. Каждый город относится к одному региону.">
    <x-slot:actions>
        <x-admin.btn variant="primary" :href="route('admin.cities.create', array_filter(['region' => is_numeric($filterRegion) ? $filterRegion : null]))">Добавить город</x-admin.btn>
    </x-slot:actions>

    <x-admin.table :count="$rows->count()" empty="Городов не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Название или код" />
            <div class="w-full sm:w-60">
                <label class="sr-only" for="filter-region">Регион</label>
                <x-admin.select id="filter-region" wire:model.live="filterRegion" placeholder="Все регионы">
                    @foreach ($regions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                    <option value="none">Без региона</option>
                </x-admin.select>
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="sort" :by="$sortBy ?: 'sort'" :dir="$sortDir">№</x-admin.th>
            <x-admin.th>Название</x-admin.th>
            <x-admin.th>Регион</x-admin.th>
            <x-admin.th sort="slug" :by="$sortBy" :dir="$sortDir">Код</x-admin.th>
            <x-admin.th>Партнёров</x-admin.th>
            <x-admin.th>Переводы</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $city)
            @php $missing = collect(['ru', 'uz', 'en'])->filter(fn ($l) => blank($city->name[$l] ?? null))->map(fn ($l) => strtoupper($l)); @endphp
            <tr wire:key="city-{{ $city->id }}">
                <td class="w-12 tabular-nums text-steel">{{ $city->sort }}</td>
                <td><a href="{{ route('admin.cities.edit', $city) }}" class="font-semibold hover:text-accent-ink">{{ $city->tr('name', 'ru') }}</a></td>
                <td>
                    @if ($city->region)
                        {{ $city->region->tr('name', 'ru') }}
                    @else
                        <x-admin.badge tone="warn">без региона</x-admin.badge>
                    @endif
                </td>
                <td><code class="text-xs">{{ $city->slug }}</code></td>
                <td class="tabular-nums">{{ $city->partners_count }}</td>
                <td>
                    @if ($missing->isEmpty())
                        <x-admin.badge tone="ok">RU · UZ · EN</x-admin.badge>
                    @else
                        <x-admin.badge tone="warn">нет: {{ $missing->implode(', ') }}</x-admin.badge>
                    @endif
                </td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" :href="route('admin.cities.edit', $city)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $city->id }})"
                                     confirm="Удалить город «{{ $city->tr('name', 'ru') }}»? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
