<x-admin.page title="Вопросы и ответы" subtitle="Частые вопросы в базе знаний, по темам. Тема без вопросов на сайте не показывается.">
    <x-slot:actions>
        <x-admin.btn :href="route('admin.faq.topics.create')">Добавить тему</x-admin.btn>
        <x-admin.btn variant="primary" :href="route('admin.faq.items.create')">Добавить вопрос</x-admin.btn>
    </x-slot:actions>

    <div class="mb-5">
        <x-admin.search placeholder="Текст вопроса или ответа" />
    </div>

    @forelse ($topics as $topic)
        @php $topicMissing = collect(['ru', 'uz', 'en'])->filter(fn ($l) => blank($topic->name[$l] ?? null))->map(fn ($l) => strtoupper($l)); @endphp
        <x-admin.table wire:key="topic-{{ $topic->id }}" :count="$topic->items->count()" empty="В этой теме пока нет вопросов.">
            <x-slot:toolbar>
                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold">
                        <span class="tabular-nums text-steel">{{ $topic->sort }}.</span> {{ $topic->tr('name', 'ru') }}
                        @if ($topicMissing->isNotEmpty())
                            <x-admin.badge tone="warn">нет: {{ $topicMissing->implode(', ') }}</x-admin.badge>
                        @endif
                    </h2>
                    <p class="text-xs text-steel"><code>{{ $topic->slug }}</code> · вопросов: {{ $topic->items->count() }}</p>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <x-admin.btn size="sm" :href="route('admin.faq.items.create', ['topic' => $topic->id])">Добавить вопрос</x-admin.btn>
                    <x-admin.btn size="sm" :href="route('admin.faq.topics.edit', $topic)">Изменить тему</x-admin.btn>
                    <x-admin.btn size="sm" variant="danger" wire:click="deleteTopic({{ $topic->id }})"
                                 confirm="Удалить тему «{{ $topic->tr('name', 'ru') }}»? Это действие нельзя отменить.">Удалить тему</x-admin.btn>
                </div>
            </x-slot:toolbar>
            <x-slot:head>
                <x-admin.th>№</x-admin.th>
                <x-admin.th>Вопрос</x-admin.th>
                <x-admin.th>Переводы</x-admin.th>
                <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
            </x-slot:head>

            @foreach ($topic->items as $item)
                @php $missing = collect(['ru', 'uz', 'en'])->filter(fn ($l) => blank($item->question[$l] ?? null) || blank($item->answer[$l] ?? null))->map(fn ($l) => strtoupper($l)); @endphp
                <tr wire:key="item-{{ $item->id }}">
                    <td class="w-12 tabular-nums text-steel">{{ $item->sort }}</td>
                    <td>
                        <a href="{{ route('admin.faq.items.edit', $item) }}" class="font-semibold hover:text-accent-ink">{{ $item->tr('question', 'ru') }}</a>
                        <div class="line-clamp-2 max-w-2xl text-xs text-steel">{{ $item->tr('answer', 'ru') }}</div>
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
                            <x-admin.btn size="sm" :href="route('admin.faq.items.edit', $item)">Изменить</x-admin.btn>
                            <x-admin.btn size="sm" variant="danger" wire:click="deleteItem({{ $item->id }})"
                                         confirm="Удалить этот вопрос? Это действие нельзя отменить.">Удалить</x-admin.btn>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>
    @empty
        <x-admin.card>
            <p class="py-4 text-center text-steel">{{ $searching ? 'По запросу ничего не найдено.' : 'Тем пока нет. Сначала добавьте тему, затем вопросы.' }}</p>
        </x-admin.card>
    @endforelse
</x-admin.page>
