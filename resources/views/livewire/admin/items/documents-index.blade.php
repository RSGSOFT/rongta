@php $types = \App\Livewire\Admin\Items\Catalog::DOCUMENT_TYPES; @endphp
<x-admin.page title="Документы и файлы" subtitle="Руководства, драйверы и ПО, SDK. Документ без файла показывается на сайте без ссылки для скачивания.">
    <x-slot:actions>
        <x-admin.btn variant="primary" :href="route('admin.documents.create', array_filter(['model' => $filterModel]))">Добавить документ</x-admin.btn>
    </x-slot:actions>

    <x-admin.table :count="$rows->count()" empty="Документов не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Название, версия или формат" />
            <div class="w-full sm:w-52">
                <label class="sr-only" for="filter-model">Модель</label>
                <x-admin.select id="filter-model" wire:model.live="filterModel" placeholder="Все модели" :options="$models" />
            </div>
            <div class="w-full sm:w-44">
                <label class="sr-only" for="filter-type">Тип</label>
                <x-admin.select id="filter-type" wire:model.live="filterType" placeholder="Все типы" :options="$types" />
            </div>
            <div class="w-full sm:w-40">
                <label class="sr-only" for="filter-file">Файл</label>
                <x-admin.select id="filter-file" wire:model.live="filterFile" placeholder="Файл: любой" :options="['missing' => 'Без файла', 'present' => 'С файлом']" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="sort" :by="$sortBy ?: 'sort'" :dir="$sortDir">№</x-admin.th>
            <x-admin.th>Документ</x-admin.th>
            <x-admin.th>Модель</x-admin.th>
            <x-admin.th sort="type" :by="$sortBy" :dir="$sortDir">Тип</x-admin.th>
            <x-admin.th sort="released_on" :by="$sortBy" :dir="$sortDir">Версия / дата</x-admin.th>
            <x-admin.th>Файл</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $document)
            @php $name = $document->tr('title', 'ru') ?: ($types[$document->type] ?? $document->type); @endphp
            <tr wire:key="document-{{ $document->id }}">
                <td class="w-12 tabular-nums text-steel">{{ $document->sort }}</td>
                <td>
                    <a href="{{ route('admin.documents.edit', $document) }}" class="font-semibold hover:text-accent-ink">{{ $name }}</a>
                    @if ($document->is_placeholder)
                        <x-admin.badge tone="warn">заглушка</x-admin.badge>
                    @endif
                </td>
                <td>{{ $document->scaleModel?->name }}</td>
                <td>{{ $types[$document->type] ?? $document->type }}</td>
                <td class="whitespace-nowrap text-xs">
                    {{ $document->version ?: '—' }}
                    <span class="text-steel">· {{ $document->released_on?->format('d.m.Y') ?? 'без даты' }}</span>
                </td>
                <td class="whitespace-nowrap">
                    @if ($document->file_path)
                        <a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" rel="noopener" class="font-medium text-accent-ink hover:underline">{{ $document->format ?: 'файл' }}</a>
                        <span class="text-xs text-steel">{{ $document->tr('size_label', 'ru') }}</span>
                    @else
                        <x-admin.badge tone="danger">нет файла</x-admin.badge>
                    @endif
                </td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" :href="route('admin.documents.edit', $document)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $document->id }})"
                                     confirm="Удалить документ «{{ $name }}» ({{ $document->scaleModel?->name }}) вместе с файлом? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
