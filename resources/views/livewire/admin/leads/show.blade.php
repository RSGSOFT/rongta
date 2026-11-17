@php
    $opt = \App\Livewire\Admin\Partners\Options::class;
    $hasChat = Route::has('admin.chatbot.conversations.show');
    $safeUrl = is_string($lead->page_url) && preg_match('~^https?://~i', $lead->page_url);
@endphp
<x-admin.page :title="$lead->first_name.' '.$lead->last_name" :back="route('admin.leads.index')" backLabel="Назад к лидам">
    <div class="grid gap-5 lg:grid-cols-[1fr_1fr]">
        <x-admin.card title="Лид № {{ $lead->id }}">
            <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-[9rem_1fr]">
                <dt class="text-xs font-semibold text-steel">Получен</dt>
                <dd class="tabular-nums">{{ $lead->created_at?->format('d.m.Y H:i') }}</dd>

                <dt class="text-xs font-semibold text-steel">Имя</dt>
                <dd class="break-words">{{ $lead->first_name }}</dd>

                <dt class="text-xs font-semibold text-steel">Фамилия</dt>
                <dd class="break-words">{{ $lead->last_name }}</dd>

                <dt class="text-xs font-semibold text-steel">Телефон</dt>
                <dd><a href="tel:{{ preg_replace('/[^\d+]/', '', $lead->phone) }}" class="font-semibold tabular-nums text-accent-ink hover:underline">{{ $lead->phone }}</a></dd>

                <dt class="text-xs font-semibold text-steel">Язык сайта</dt>
                <dd>{{ $opt::LOCALES[$lead->locale] ?? ($lead->locale ?: 'не указан') }}</dd>

                <dt class="text-xs font-semibold text-steel">Источник</dt>
                <dd>{{ $lead->source ?: 'не указан' }}</dd>

                <dt class="text-xs font-semibold text-steel">Страница</dt>
                <dd class="break-all">
                    @if ($safeUrl)
                        <a href="{{ $lead->page_url }}" target="_blank" rel="noopener noreferrer" class="text-accent-ink hover:underline">{{ $lead->page_url }}</a>
                    @else
                        {{ $lead->page_url ?: 'не указана' }}
                    @endif
                </dd>
            </dl>
        </x-admin.card>

        <div>
            <x-admin.table :count="$lead->conversations->count()" empty="Диалогов с чат-ботом нет." class="!mb-5">
                <x-slot:toolbar><h2 class="font-semibold">Диалоги с чат-ботом: {{ $lead->conversations->count() }}</h2></x-slot:toolbar>
                <x-slot:head>
                    <x-admin.th>Начат</x-admin.th>
                    <x-admin.th>Сообщений</x-admin.th>
                    <x-admin.th>Без ответа</x-admin.th>
                    <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
                </x-slot:head>
                @foreach ($lead->conversations as $conversation)
                    <tr wire:key="conversation-{{ $conversation->id }}">
                        <td class="whitespace-nowrap tabular-nums">{{ $conversation->created_at?->format('d.m.Y H:i') }} <span class="uppercase text-steel">{{ $conversation->locale }}</span></td>
                        <td class="tabular-nums">{{ $conversation->messages_count }}</td>
                        <td>
                            @if ($conversation->unanswered_count > 0)
                                <x-admin.badge tone="warn">{{ $conversation->unanswered_count }}</x-admin.badge>
                            @else
                                <span class="text-steel">0</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if ($hasChat)
                                <x-admin.btn size="sm" :href="route('admin.chatbot.conversations.show', $conversation)">Открыть диалог</x-admin.btn>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>

            @if ($others->isNotEmpty())
                <x-admin.card title="Другие обращения с этого номера" hint="Тот же телефон оставляли ещё раз.">
                    <ul class="space-y-1.5">
                        @foreach ($others as $other)
                            <li>
                                <a href="{{ route('admin.leads.show', $other) }}" class="font-semibold text-accent-ink hover:underline">{{ $other->first_name }} {{ $other->last_name }}</a>
                                <span class="tabular-nums text-steel">· {{ $other->created_at?->format('d.m.Y H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endif
        </div>
    </div>
</x-admin.page>
