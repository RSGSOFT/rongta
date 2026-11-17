@php
    $statuses = [
        'answered' => ['ok', 'ответил'],
        'unanswered' => ['warn', 'не смог ответить'],
        'refused' => ['danger', 'отказ модели'],
        'blocked' => ['danger', 'ответ с ценой скрыт'],
        'unavailable' => ['danger', 'бот был недоступен'],
    ];
    $lead = $conversation->lead;
    $n = fn ($value) => number_format((int) $value, 0, ',', ' ');
@endphp
<x-admin.page title="Диалог №{{ $conversation->id }}" :back="route('admin.chatbot.conversations.index')" backLabel="Назад к диалогам">
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <x-admin.card title="Посетитель (лид)">
                <dl class="grid gap-2.5 text-sm">
                    <div><dt class="text-xs text-steel">Имя и фамилия</dt><dd class="font-semibold">{{ $lead?->first_name }} {{ $lead?->last_name }}</dd></div>
                    <div><dt class="text-xs text-steel">Телефон</dt><dd><a class="font-semibold text-accent-ink hover:underline" href="tel:{{ $lead?->phone }}">{{ $lead?->phone }}</a></dd></div>
                    <div><dt class="text-xs text-steel">Язык сайта</dt><dd class="uppercase">{{ $conversation->locale }}</dd></div>
                    <div><dt class="text-xs text-steel">Начат</dt><dd>{{ $conversation->created_at?->format('d.m.Y H:i') }}</dd></div>
                    <div><dt class="text-xs text-steel">Страница</dt><dd class="break-all">{{ $conversation->page_url ?: 'не записана' }}</dd></div>
                    <div><dt class="text-xs text-steel">IP-адрес</dt><dd>{{ $conversation->ip ?: 'не записан' }}</dd></div>
                </dl>
            </x-admin.card>

            <x-admin.card title="Расход Claude API" hint="Токены за все ответы в этом диалоге.">
                <dl class="grid grid-cols-2 gap-2.5 text-sm">
                    <div><dt class="text-xs text-steel">Вход</dt><dd class="tabular-nums">{{ $n($usage['input']) }}</dd></div>
                    <div><dt class="text-xs text-steel">Выход</dt><dd class="tabular-nums">{{ $n($usage['output']) }}</dd></div>
                    <div><dt class="text-xs text-steel">Из кэша</dt><dd class="tabular-nums">{{ $n($usage['cache_read']) }}</dd></div>
                    <div><dt class="text-xs text-steel">Запись в кэш</dt><dd class="tabular-nums">{{ $n($usage['cache_write']) }}</dd></div>
                </dl>
            </x-admin.card>
        </div>

        <div class="lg:col-span-2">
            <x-admin.card title="Сообщения">
                @if ($conversation->messages->isEmpty())
                    <p class="text-steel">Посетитель оставил контакты, но ничего не спросил.</p>
                @endif
                <ol class="grid gap-3">
                    @foreach ($conversation->messages as $message)
                        <li wire:key="message-{{ $message->id }}" @class([
                            'rounded-m border px-3 py-2.5',
                            'border-line bg-paper sm:mr-12' => $message->role === 'user',
                            'border-line bg-label sm:ml-12' => $message->role !== 'user',
                        ])>
                            <div class="mb-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-steel">
                                <b class="text-ink">{{ $message->role === 'user' ? 'Посетитель' : 'Бот' }}</b>
                                <span>{{ $message->created_at?->format('d.m.Y H:i:s') }}</span>
                                @if ($message->role === 'user' && $message->is_unanswered)
                                    <x-admin.badge tone="warn">без ответа{{ $message->unanswered_reason ? ': '.$message->unanswered_reason : '' }}</x-admin.badge>
                                @elseif ($message->role !== 'user' && isset($statuses[$message->status]))
                                    <x-admin.badge :tone="$statuses[$message->status][0]">{{ $statuses[$message->status][1] }}</x-admin.badge>
                                @endif
                            </div>
                            <div class="whitespace-pre-line break-words">{{ $message->content }}</div>

                            @if ($message->role !== 'user')
                                <div class="mt-1.5 text-xs text-steel">
                                    @if ($message->model){{ $message->model }} · @endif
                                    вход {{ $n($message->input_tokens) }}, выход {{ $n($message->output_tokens) }}, из кэша {{ $n($message->cache_read_tokens) }}
                                    @if ($message->tool_calls)
                                        · поиск: {{ collect($message->tool_calls)->pluck('name')->implode(', ') }}
                                    @endif
                                </div>
                            @elseif ($message->article)
                                <div class="mt-2">
                                    <x-admin.btn size="sm" :href="route('admin.chatbot.articles.edit', $message->article)">
                                        Статья по этому вопросу{{ $message->article->is_published ? '' : ' (черновик)' }}
                                    </x-admin.btn>
                                </div>
                            @elseif ($message->is_unanswered)
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <x-admin.btn size="sm" variant="primary" wire:click="makeArticle({{ $message->id }})">Сделать статьёй</x-admin.btn>
                                    <x-admin.btn size="sm" wire:click="dismissQuestion({{ $message->id }})">Статья не нужна</x-admin.btn>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </x-admin.card>
        </div>
    </div>
</x-admin.page>
