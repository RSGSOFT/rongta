<x-admin.page title="Диалоги" subtitle="Разговоры посетителей с чат-ботом. Каждый диалог привязан к лиду из формы чата.">
    <x-slot:actions>
        <x-admin.btn :variant="$open > 0 ? 'primary' : 'ghost'" :href="route('admin.chatbot.conversations.unanswered')">
            Вопросы без ответа{{ $open > 0 ? ': '.$open : '' }}
        </x-admin.btn>
    </x-slot:actions>

    <x-admin.table :count="$rows->count()" empty="Диалогов пока нет.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Имя, фамилия или телефон" />
            <div class="w-full sm:w-56">
                <label class="sr-only" for="filter-only">Показать</label>
                <x-admin.select id="filter-only" wire:model.live="filterOnly" placeholder="Все диалоги"
                                :options="['unanswered' => 'Только с вопросами без ответа']" />
            </div>
            <div class="w-full sm:w-40">
                <label class="sr-only" for="filter-locale">Язык</label>
                <x-admin.select id="filter-locale" wire:model.live="filterLocale" placeholder="Любой язык"
                                :options="['ru' => 'Русский', 'uz' => 'Oʻzbekcha', 'en' => 'English']" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="id" :by="$sortBy ?: 'id'" :dir="$sortBy ? $sortDir : 'desc'">Начат</x-admin.th>
            <x-admin.th>Посетитель</x-admin.th>
            <x-admin.th>Язык</x-admin.th>
            <x-admin.th align="right">Сообщений</x-admin.th>
            <x-admin.th sort="unanswered_count" :by="$sortBy" :dir="$sortDir">Без ответа</x-admin.th>
            <x-admin.th align="right">Токены (вход / выход)</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $conversation)
            <tr wire:key="conversation-{{ $conversation->id }}">
                <td class="whitespace-nowrap text-steel">{{ $conversation->created_at?->format('d.m.Y H:i') }}</td>
                <td>
                    <a href="{{ route('admin.chatbot.conversations.show', $conversation) }}" class="font-semibold hover:text-accent-ink">
                        {{ $conversation->lead?->first_name }} {{ $conversation->lead?->last_name }}
                    </a>
                    <div class="text-xs text-steel">{{ $conversation->lead?->phone }}</div>
                </td>
                <td class="uppercase">{{ $conversation->locale }}</td>
                <td class="text-right tabular-nums">{{ $conversation->messages_count }}</td>
                <td>
                    @if ($conversation->unanswered_count > 0)
                        <x-admin.badge tone="warn">{{ $conversation->unanswered_count }}</x-admin.badge>
                    @else
                        <span class="text-steel">нет</span>
                    @endif
                </td>
                <td class="whitespace-nowrap text-right tabular-nums text-steel">{{ number_format((int) $conversation->tokens_in, 0, ',', ' ') }} / {{ number_format((int) $conversation->tokens_out, 0, ',', ' ') }}</td>
                <td class="text-right">
                    <x-admin.btn size="sm" :href="route('admin.chatbot.conversations.show', $conversation)">Открыть</x-admin.btn>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
