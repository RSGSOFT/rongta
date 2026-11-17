@php
    $reasons = [
        'not_in_knowledge' => 'нет в базе ответов',
        'unconfirmed_value' => 'значение не подтверждено',
        'price' => 'спросили цену',
        'off_topic' => 'не по теме',
        'needs_person' => 'нужен сотрудник',
        'other' => 'другое',
        'price_in_reply' => 'ответ с ценой скрыт',
        'tool_loop' => 'бот не нашёл ответ',
        'empty_reply' => 'пустой ответ',
    ];
@endphp
<x-admin.page title="Вопросы без ответа" :back="route('admin.chatbot.conversations.index')" backLabel="Назад к диалогам"
              subtitle="Вопросы посетителей, на которые бот не смог ответить. Сделайте из вопроса статью, и бот начнёт на него отвечать.">
    <x-admin.table :count="$rows->count()" empty="Вопросов без ответа нет.">
        <x-slot:head>
            <x-admin.th>Вопрос</x-admin.th>
            <x-admin.th>Причина</x-admin.th>
            <x-admin.th>Посетитель</x-admin.th>
            <x-admin.th>Когда</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $question)
            @php $lead = $question->conversation?->lead; @endphp
            <tr wire:key="question-{{ $question->id }}">
                <td class="max-w-96">
                    <div class="whitespace-pre-line break-words font-medium">{{ $question->content }}</div>
                    <div class="mt-0.5 text-xs uppercase text-steel">{{ $question->conversation?->locale }}</div>
                </td>
                <td>
                    <x-admin.badge tone="warn">{{ $reasons[$question->unanswered_reason] ?? (str_starts_with((string) $question->unanswered_reason, 'refusal') ? 'отказ модели' : ($question->unanswered_reason ?: 'не указана')) }}</x-admin.badge>
                </td>
                <td>
                    <a href="{{ route('admin.chatbot.conversations.show', $question->chat_conversation_id) }}" class="font-semibold hover:text-accent-ink">
                        {{ $lead?->first_name }} {{ $lead?->last_name }}
                    </a>
                    <div class="text-xs text-steel">{{ $lead?->phone }}</div>
                </td>
                <td class="whitespace-nowrap text-steel">{{ $question->created_at?->format('d.m.Y H:i') }}</td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" variant="primary" wire:click="makeArticle({{ $question->id }})">Сделать статьёй</x-admin.btn>
                        <x-admin.btn size="sm" wire:click="dismissQuestion({{ $question->id }})"
                                     confirm="Убрать вопрос из списка без создания статьи?">Не нужна</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
