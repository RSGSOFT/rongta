<x-admin.page title="Тексты интерфейса" subtitle="Все надписи публичного сайта на трёх языках. Без перевода: {{ $missingCount }}.">
    <x-admin.table :count="$rows->count()" empty="Текстов не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Ключ или текст" />
            <div class="w-full sm:w-52">
                <label class="sr-only" for="filter-missing">Переводы</label>
                <x-admin.select id="filter-missing" wire:model.live="filterMissing" placeholder="Все тексты"
                                :options="['any' => 'Нет какого-либо перевода', 'ru' => 'Нет русского', 'uz' => 'Нет узбекского', 'en' => 'Нет английского']" />
            </div>
            <div class="w-full sm:w-44">
                <label class="sr-only" for="filter-group">Раздел</label>
                <x-admin.select id="filter-group" wire:model.live="filterGroup" placeholder="Все разделы" :options="$groups" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="key" :by="$sortBy ?: 'key'" :dir="$sortDir">Ключ</x-admin.th>
            <x-admin.th>RU</x-admin.th>
            <x-admin.th>UZ</x-admin.th>
            <x-admin.th>EN</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $string)
            <tr wire:key="string-{{ $string->id }}">
                <td class="align-top"><a href="{{ route('admin.strings.edit', $string) }}" class="font-semibold hover:text-accent-ink"><code class="text-xs">{{ $string->key }}</code></a></td>
                @foreach (['ru', 'uz', 'en'] as $l)
                    <td class="min-w-40 max-w-64 align-top" lang="{{ $l }}">
                        @if (filled($string->{$l}))
                            <div class="line-clamp-2 break-words text-xs">{{ $string->{$l} }}</div>
                        @else
                            <x-admin.badge tone="warn">нет {{ strtoupper($l) }}</x-admin.badge>
                        @endif
                    </td>
                @endforeach
                <td class="text-right align-top"><x-admin.btn size="sm" :href="route('admin.strings.edit', $string)">Изменить</x-admin.btn></td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
