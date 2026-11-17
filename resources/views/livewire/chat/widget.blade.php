@php
    $symbol = asset(setting('logo.symbol', 'images/rongta-symbol.png'));
@endphp
<div>
@if ($enabled)
    <div
        class="rc"
        x-data="{
            open: false,
            init() {
                const log = this.$refs.log;
                const down = () => { log.scrollTop = log.scrollHeight };
                new MutationObserver(down).observe(log, { childList: true, subtree: true, characterData: true });
                this.$watch('open', (value) => { if (value) this.$nextTick(() => { down(); this.focusIn() }) });
            },
            focusIn(field) {
                const el = this.$root.querySelector(field ? '[data-rc=' + field + ']' : '[data-rc=first], [data-rc=input]');
                if (el && ! el.disabled) el.focus();
            },
            close() { this.open = false; this.$nextTick(() => this.$refs.fab.focus()) },
        }"
        x-on:keydown.escape.window="open && close()"
        x-on:chat-focus.window="$nextTick(() => focusIn($event.detail.field))"
    >
        <button type="button" class="rc-fab" x-ref="fab" x-show="! open" x-on:click="open = true"
                aria-label="{{ ui('chat.open') }}" aria-haspopup="dialog" :aria-expanded="open.toString()">
            <img src="{{ $symbol }}" width="183" height="132" alt="">
            <span class="rc-fab-hint" aria-hidden="true">{{ ui('chat.hint') }}</span>
        </button>

        <section class="rc-panel" x-show="open" x-cloak role="dialog" aria-label="{{ ui('chat.title') }}">
            <header class="rc-hd">
                <img class="rc-mark" src="{{ $symbol }}" width="183" height="132" alt="">
                <div><b>Rongta Uzbekistan</b><span>{{ ui('bot.sub') }}</span></div>
                <button type="button" class="rc-x" x-on:click="close()" aria-label="{{ ui('chat.close') }}">×</button>
            </header>

            <div class="rc-log" x-ref="log" role="log" aria-live="polite" tabindex="0" aria-label="{{ ui('chat.log') }}">
                @if (! $lead)
                    <div class="rc-msg rc-bot"><p>{{ \App\Services\Chatbot\ChatbotSettings::greeting() }}</p></div>

                    <form class="rc-gate" wire:submit="startChat" novalidate aria-label="{{ ui('chat.gate.label') }}">
                        @foreach ([['first', 'chat.first', 'text', 'given-name'], ['last', 'chat.last', 'text', 'family-name'], ['phone', 'chat.phone', 'tel', 'tel']] as [$field, $label, $type, $autocomplete])
                            <div class="rc-field">
                                <label for="rc-{{ $field }}">{{ ui($label) }}</label>
                                <input class="rc-inp" type="{{ $type }}" id="rc-{{ $field }}" data-rc="{{ $field }}"
                                       wire:model="{{ $field }}" autocomplete="{{ $autocomplete }}" required maxlength="100"
                                       @if ($type === 'tel') inputmode="tel" placeholder="+998 90 123 45 67" @endif
                                       @error($field) aria-invalid="true" aria-describedby="rc-{{ $field }}-err" @enderror>
                                <span class="rc-err" id="rc-{{ $field }}-err">@error($field){{ $message }}@enderror</span>
                            </div>
                        @endforeach
                        <button type="submit" class="rc-btn rc-btn-sm" wire:loading.attr="disabled" wire:target="startChat">{{ ui('chat.gate.submit') }}</button>
                        <p class="rc-note">{{ ui('bot.gate.note') }}</p>
                    </form>
                @else
                    <div class="rc-msg rc-bot"><p>{{ ui('chat.hello.name', ['name' => $lead->first_name]) }}</p></div>

                    @foreach ($messages as $message)
                        @if ($message->role === 'user')
                            <div class="rc-msg rc-me" wire:key="m{{ $message->id }}">{{ $message->content }}</div>
                        @else
                            <div class="rc-msg rc-bot" wire:key="m{{ $message->id }}">
                                {!! \App\Services\Chatbot\ChatFormatter::html($message->content) !!}
                                @if ($message->status !== 'answered')
                                    <p><a href="{{ lroute('dealers') }}">{{ ui('bot.dealers.link') }}</a></p>
                                @endif
                            </div>
                        @endif
                    @endforeach

                    @if ($pending)
                        <div class="rc-msg rc-bot" wire:key="pending" aria-label="{{ ui('chat.typing') }}">
                            <div wire:stream="chat-answer"><span class="rc-typing"><i></i><i></i><i></i></span></div>
                        </div>
                    @elseif ($messages->isEmpty())
                        <div class="rc-chips" role="group" aria-label="{{ ui('chat.chips') }}">
                            @foreach ($chips as $chip)
                                <button type="button" wire:click="askChip('{{ $chip }}')">{{ ui('chat.chip.'.$chip) }}</button>
                            @endforeach
                        </div>
                    @endif

                    <p class="rc-note">{{ ui('bot.ai.note') }}</p>
                @endif
            </div>

            @if ($notice)
                <p class="rc-notice" role="alert">{{ $notice }}</p>
            @endif

            <form class="rc-in" wire:submit="send" novalidate>
                <label class="rc-vh" for="rc-input">{{ ui('chat.input') }}</label>
                <input class="rc-inp" type="text" id="rc-input" data-rc="input" wire:model="text" autocomplete="off"
                       maxlength="{{ $maxChars }}" placeholder="{{ ui($lead ? 'chat.ph' : 'chat.ph.locked') }}"
                       @disabled(! $lead || $pending)>
                <button type="submit" class="rc-btn" @disabled(! $lead || $pending) wire:loading.attr="disabled" wire:target="send, askChip">{{ ui('chat.send') }}</button>
            </form>
        </section>
    </div>
@endif
</div>
