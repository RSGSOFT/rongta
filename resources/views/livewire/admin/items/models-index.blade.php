<x-admin.page title="Модели весов" subtitle="Каталог сайта. Цены на сайте не показываются и здесь не задаются.">
    <x-slot:actions>
        <x-admin.btn variant="primary" :href="route('admin.models.create')">Добавить модель</x-admin.btn>
    </x-slot:actions>

    <x-admin.table :count="$rows->count()" empty="Моделей не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Название, адрес или тип" />
            <div class="w-full sm:w-56">
                <label class="sr-only" for="filter-family">Семейство</label>
                <x-admin.select id="filter-family" wire:model.live="filterFamily" placeholder="Все семейства" :options="$families" />
            </div>
            <div class="w-full sm:w-44">
                <label class="sr-only" for="filter-published">Публикация</label>
                <x-admin.select id="filter-published" wire:model.live="filterPublished" placeholder="Любой статус" :options="['1' => 'Опубликованы', '0' => 'Скрыты']" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="sort" :by="$sortBy ?: 'sort'" :dir="$sortDir">№</x-admin.th>
            <x-admin.th sort="name" :by="$sortBy" :dir="$sortDir">Модель</x-admin.th>
            <x-admin.th>Семейство</x-admin.th>
            <x-admin.th>Наполнение</x-admin.th>
            <x-admin.th sort="is_published" :by="$sortBy" :dir="$sortDir">На сайте</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $model)
            <tr wire:key="model-{{ $model->id }}">
                <td class="w-12 tabular-nums text-steel">{{ $model->sort }}</td>
                <td>
                    <div class="flex items-center gap-3">
                        @if ($model->image)
                            <img src="{{ asset('storage/'.$model->image) }}" alt="" class="h-10 w-10 flex-none rounded-m border border-line object-cover" loading="lazy">
                        @else
                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-m border border-dashed border-line text-[10px] text-steel" title="Фото не загружено: на сайте показывается рисунок">рис.</span>
                        @endif
                        <div class="min-w-0">
                            <a href="{{ route('admin.models.edit', $model) }}" class="font-semibold hover:text-accent-ink">{{ $model->name }}</a>
                            <div class="max-w-xs truncate text-xs text-steel">{{ $model->tr('type', 'ru') }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    {{ $model->family?->tr('name', 'ru') }}
                    @if ($model->is_aurora)
                        <x-admin.badge>AURORA</x-admin.badge>
                    @endif
                </td>
                <td class="whitespace-nowrap text-xs text-steel">
                    <a href="{{ route('admin.models.specs', $model) }}" class="hover:text-accent-ink">характеристик: {{ $model->specs_count }}</a><br>
                    преимуществ: {{ $model->features_count }} · документов: {{ $model->documents_count }}
                </td>
                <td>
                    <button type="button" wire:click="togglePublished({{ $model->id }})" class="rounded-full"
                            title="{{ $model->is_published ? 'Скрыть с сайта' : 'Опубликовать на сайте' }}"
                            aria-pressed="{{ $model->is_published ? 'true' : 'false' }}">
                        @if ($model->is_published)
                            <x-admin.badge tone="ok">опубликована</x-admin.badge>
                        @else
                            <x-admin.badge tone="warn">скрыта</x-admin.badge>
                        @endif
                    </button>
                </td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" :href="route('admin.models.edit', $model)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" :href="route('admin.models.specs', $model)">Характеристики</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $model->id }})"
                                     confirm="Удалить модель {{ $model->name }}? Вместе с ней будут удалены характеристики, преимущества, документы и файлы. Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
