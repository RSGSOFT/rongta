<x-admin.page title="Настройки бота" subtitle="Чат-помощник на сайте: включение, тексты, правила и лимиты.">
    @unless ($hasKey)
        <div role="alert" class="mb-4 rounded-m border border-warn/30 bg-warn-soft px-3 py-2.5 text-warn">
            <b>Ключ Claude API не задан.</b> Пока его нет, бот собирает контакты посетителя и отвечает:
            «Помощник сейчас недоступен, обратитесь к дилеру или в RSG». Добавьте строку
            <code>ANTHROPIC_API_KEY=…</code> в файл <code>.env</code> на сервере.
        </div>
    @endunless

    <form wire:submit="save" novalidate>
        <x-admin.card title="Работа бота" hint="Модель: {{ $model }}. Ключ API: {{ $hasKey ? 'задан' : 'не задан' }}.">
            <x-admin.toggle wire:model="form.enabled" label="Бот включён"
                            hint="Если выключить, кнопка чата исчезнет со всех страниц сайта." />
        </x-admin.card>

        <x-admin.card title="Тексты" hint="Пустое поле = стандартный текст. Цены и суммы не указываются.">
            <div class="grid gap-5">
                <x-admin.trans-textarea label="Приветствие" model="form.greeting" rows="4"
                                        hint="Первое сообщение бота, до формы с именем и телефоном. Стандартный текст: «{{ $defaultGreeting }}»" />
                <x-admin.trans-textarea label="Ответ «не могу помочь»" model="form.fallback" rows="3"
                                        hint="Показывается, когда бот не может ответить. Стандартный текст: «{{ $defaultFallback }}»" />
            </div>
        </x-admin.card>

        <x-admin.card title="Правила" hint="Основные правила заданы в коде и не отключаются: только весы Rongta, без цен, без выдуманных характеристик, гарантий и дилеров.">
            <div class="grid gap-5">
                <x-admin.field label="Дополнительные правила и тон" for="cb-rules" :error="$errors->first('form.rules_extra')"
                               hint="Обычным текстом, например: «Обращайся на вы. Если спрашивают про поверку, советуй обратиться в RSG». Пишите на любом языке.">
                    <x-admin.textarea id="cb-rules" rows="5" wire:model="form.rules_extra" :invalid="$errors->has('form.rules_extra')" />
                </x-admin.field>
                <x-admin.field label="Запретные темы" for="cb-off" :error="$errors->first('form.off_limits')"
                               hint="По одной теме в строке. На такие вопросы бот вежливо откажется отвечать.">
                    <x-admin.textarea id="cb-off" rows="4" wire:model="form.off_limits" :invalid="$errors->has('form.off_limits')" />
                </x-admin.field>
            </div>
        </x-admin.card>

        <x-admin.card title="Лимиты и расход" hint="Защита от злоупотреблений и лишних расходов на Claude API.">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-admin.field label="Сообщений в день от одного посетителя" for="cb-lead" required :error="$errors->first('form.lead_daily_limit')">
                    <x-admin.input type="number" min="1" id="cb-lead" wire:model="form.lead_daily_limit" :invalid="$errors->has('form.lead_daily_limit')" />
                </x-admin.field>
                <x-admin.field label="Сообщений в день с одного IP-адреса" for="cb-ip" required :error="$errors->first('form.ip_daily_limit')">
                    <x-admin.input type="number" min="1" id="cb-ip" wire:model="form.ip_daily_limit" :invalid="$errors->has('form.ip_daily_limit')" />
                </x-admin.field>
                <x-admin.field label="Длина сообщения, символов" for="cb-chars" required :error="$errors->first('form.max_message_chars')">
                    <x-admin.input type="number" min="50" id="cb-chars" wire:model="form.max_message_chars" :invalid="$errors->has('form.max_message_chars')" />
                </x-admin.field>
                <x-admin.field label="Сообщений истории" for="cb-hist" required :error="$errors->first('form.history_messages')"
                               hint="Сколько предыдущих сообщений диалога бот видит при ответе.">
                    <x-admin.input type="number" min="0" id="cb-hist" wire:model="form.history_messages" :invalid="$errors->has('form.history_messages')" />
                </x-admin.field>
                <x-admin.field label="Предел длины ответа, токенов" for="cb-tokens" required :error="$errors->first('form.max_output_tokens')"
                               hint="Верхняя граница на один ответ вместе с обдумыванием.">
                    <x-admin.input type="number" min="500" id="cb-tokens" wire:model="form.max_output_tokens" :invalid="$errors->has('form.max_output_tokens')" />
                </x-admin.field>
                <x-admin.field label="Глубина обдумывания" for="cb-effort" required :error="$errors->first('form.effort')"
                               hint="«Низкая» подходит для чата: быстрее и дешевле.">
                    <x-admin.select id="cb-effort" wire:model="form.effort" :options="['low' => 'Низкая', 'medium' => 'Средняя', 'high' => 'Высокая']" />
                </x-admin.field>
            </div>
            <x-slot:footer>
                <x-admin.form-actions />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
