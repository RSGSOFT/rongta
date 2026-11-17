<x-admin.page title="Видео" subtitle="Видеоинструкции для базы знаний.">
    <x-slot:actions>
        <x-admin.btn variant="primary" :href="route('admin.videos.create')">Добавить видео</x-admin.btn>
    </x-slot:actions>

    <x-admin.table :count="$rows->count()" empty="Видео не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Название или ссылка" />
            <div class="w-full sm:w-56">
                <label class="sr-only" for="filter-family">Семейство</label>
                <x-admin.select id="filter-family" wire:model.live="filterFamily" placeholder="Все семейства" :options="$families" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="sort" :by="$sortBy ?: 'sort'" :dir="$sortDir">№</x-admin.th>
            <x-admin.th>Название</x-admin.th>
            <x-admin.th>Семейство</x-admin.th>
            <x-admin.th>Ссылка</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $video)
            <tr wire:key="video-{{ $video->id }}">
                <td class="w-12 tabular-nums text-steel">{{ $video->sort }}</td>
                <td>
                    <a href="{{ route('admin.videos.edit', $video) }}" class="font-semibold hover:text-accent-ink">{{ $video->tr('title', 'ru') }}</a>
                    @if ($video->is_placeholder)
                        <x-admin.badge tone="warn">заглушка</x-admin.badge>
                    @endif
                </td>
                <td>{{ $video->family?->tr('name', 'ru') ?? 'Все семейства' }}</td>
                <td class="max-w-56 truncate">
                    @if ($video->url)
                        <a href="{{ $video->url }}" target="_blank" rel="noopener noreferrer" class="text-accent-ink hover:underline">{{ $video->url }}</a>
                    @else
                        <x-admin.badge tone="danger">нет ссылки</x-admin.badge>
                    @endif
                </td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" :href="route('admin.videos.edit', $video)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $video->id }})"
                                     confirm="Удалить видео «{{ $video->tr('title', 'ru') }}»? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
