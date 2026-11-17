@php
    $sources = ['manual' => 'Написана вручную', 'pdf' => 'Из руководства (PDF)', 'conversation' => 'Из вопроса в чате'];
@endphp
<x-admin.page title="База ответов" subtitle="Статьи, по которым отвечает чат-бот. Бот видит только опубликованные статьи.">
    <x-slot:actions>
        <x-admin.btn :href="route('admin.chatbot.conversations.unanswered')">Вопросы без ответа</x-admin.btn>
        <x-admin.btn :href="route('admin.chatbot.articles.import')">Обучить по руководству (PDF)</x-admin.btn>
        <x-admin.btn variant="primary" :href="route('admin.chatbot.articles.create')">Добавить статью</x-admin.btn>
    </x-slot:actions>

    @if ($drafts > 0)
        <p class="mb-4 rounded-m border border-warn/30 bg-warn-soft px-3 py-2.5 text-warn">
            Черновиков на проверке: <b>{{ $drafts }}</b>. Проверьте текст и опубликуйте, чтобы бот начал по ним отвечать.
        </p>
    @endif

    <x-admin.table :count="$rows->count()" empty="Статей не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Заголовок, текст или метка" />
            <div class="w-full sm:w-48">
                <label class="sr-only" for="filter-status">Состояние</label>
                <x-admin.select id="filter-status" wire:model.live="filterStatus" placeholder="Все статьи"
                                :options="['published' => 'Опубликованные', 'draft' => 'Черновики']" />
            </div>
            <div class="w-full sm:w-56">
                <label class="sr-only" for="filter-source">Источник</label>
                <x-admin.select id="filter-source" wire:model.live="filterSource" placeholder="Любой источник" :options="$sources" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th>Заголовок</x-admin.th>
            <x-admin.th>Модель</x-admin.th>
            <x-admin.th>Источник</x-admin.th>
            <x-admin.th sort="is_published" :by="$sortBy" :dir="$sortDir">Состояние</x-admin.th>
            <x-admin.th sort="updated_at" :by="$sortBy ?: 'updated_at'" :dir="$sortBy ? $sortDir : 'desc'">Изменена</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $article)
            <tr wire:key="article-{{ $article->id }}">
                <td class="max-w-80">
                    <a href="{{ route('admin.chatbot.articles.edit', $article) }}" class="font-semibold hover:text-accent-ink">{{ $article->tr('title', 'ru') ?: 'Без заголовка' }}</a>
                    @if ($article->tags)
                        <div class="mt-0.5 text-xs text-steel">{{ implode(', ', $article->tags) }}</div>
                    @endif
                </td>
                <td>{{ $article->scaleModel?->name ?? $article->family?->tr('name', 'ru') ?? 'Все весы' }}</td>
                <td>
                    {{ $sources[$article->source] ?? $article->source }}
                    @if ($article->source_ref)
                        <div class="max-w-48 truncate text-xs text-steel" title="{{ $article->source_ref }}">{{ $article->source_ref }}</div>
                    @endif
                </td>
                <td>
                    @if ($article->is_published)
                        <x-admin.badge tone="ok">опубликована</x-admin.badge>
                    @else
                        <x-admin.badge tone="warn">черновик</x-admin.badge>
                    @endif
                </td>
                <td class="whitespace-nowrap text-steel">{{ $article->updated_at?->format('d.m.Y H:i') }}</td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" wire:click="togglePublished({{ $article->id }})">{{ $article->is_published ? 'Снять' : 'Опубликовать' }}</x-admin.btn>
                        <x-admin.btn size="sm" :href="route('admin.chatbot.articles.edit', $article)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $article->id }})"
                                     confirm="Удалить статью «{{ $article->tr('title', 'ru') }}»? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
