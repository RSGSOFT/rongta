@php
    $c = \App\Livewire\Admin\Settings\Index::class;
    $hexOk = fn ($v) => is_string($v) && preg_match($c::HEX, $v);
    $swatches = [
        'accent' => ['Фирменный цвет', 'Кнопки и акценты. Официальный цвет логотипа Rongta.', $c::DEFAULT_ACCENT, true],
        'accent_ink' => ['Тёмный оттенок', 'Ссылки и наведение на кнопки. Пусто = стандартный.', $c::DEFAULT_ACCENT_INK, false],
        'accent_soft' => ['Светлый оттенок', 'Мягкий фон выделенных блоков. Пусто = стандартный.', $c::DEFAULT_ACCENT_SOFT, false],
    ];
    $uploadProps = ['logo' => 'newLogo', 'symbol' => 'newSymbol', 'wordmark' => 'newWordmark'];
@endphp
<x-admin.page title="Настройки сайта" subtitle="Контакты RSG, фирменный цвет, логотип и главная страница. Изменения сразу видны на сайте.">
    @include('livewire.admin.settings.tabs', ['current' => 'admin.settings'])

    <form wire:submit="save" novalidate>
        {{-- Contacts --}}
        <x-admin.card title="Контакты RSG" hint="Показываются на главной, в подвале, в кнопке «Позвонить» и в ответах чат-бота.">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="Телефон" for="st-phone" :required="! $contacts['is_placeholder']" :error="$errors->first('contacts.phone')" hint="Например +998 71 200 00 00.">
                    <x-admin.input type="tel" id="st-phone" wire:model="contacts.phone" autocomplete="off" :invalid="$errors->has('contacts.phone')" />
                </x-admin.field>
                <x-admin.field label="Telegram" for="st-telegram" :error="$errors->first('contacts.telegram')" hint="Имя пользователя, например @rongta_uz.">
                    <x-admin.input id="st-telegram" wire:model="contacts.telegram" autocomplete="off" :invalid="$errors->has('contacts.telegram')" />
                </x-admin.field>
                <div class="md:col-span-2">
                    <x-admin.toggle wire:model.live="contacts.is_placeholder" label="Контакты ещё не подтверждены"
                                    hint="Пока включено, сайт не показывает телефон и Telegram, а пишет «уточняется». Выключите, когда впишете настоящие контакты." />
                    @if ($contacts['is_placeholder'])
                        <p class="mt-2 text-xs font-medium text-warn" role="status">Сейчас на сайте вместо контактов показано «уточняется».</p>
                    @endif
                </div>
            </div>
        </x-admin.card>

        {{-- Colours --}}
        <x-admin.card title="Фирменный цвет" hint="Один акцентный цвет и два его оттенка для всего сайта и этой панели.">
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($swatches as $field => [$label, $hint, $default, $required])
                    @php $value = $colors[$field] ?? ''; @endphp
                    <x-admin.field :label="$label" for="st-{{ $field }}" :required="$required" :error="$errors->first('colors.'.$field)" :hint="$hint">
                        <div class="flex items-stretch gap-2">
                            <span class="h-10 w-10 flex-none rounded-m border border-line" aria-hidden="true"
                                  style="background: {{ $hexOk($value) ? $value : ($value === '' ? $default : 'repeating-linear-gradient(45deg,#fff,#fff 4px,#D5DBE0 4px,#D5DBE0 8px)') }}"></span>
                            <x-admin.input id="st-{{ $field }}" wire:model.live.debounce.400ms="colors.{{ $field }}" maxlength="7" autocomplete="off" spellcheck="false"
                                           placeholder="{{ $default }}" class="font-mono uppercase" :invalid="$errors->has('colors.'.$field)" />
                        </div>
                    </x-admin.field>
                @endforeach
            </div>

            <div class="mt-4" aria-live="polite">
                <p class="mb-1.5 text-xs font-semibold">Читаемость (контраст не ниже 4,5 : 1)</p>
                @if ($checks)
                    <ul class="grid gap-1.5 sm:grid-cols-2">
                        @foreach ($checks as [$label, $ratio, $passes])
                            <li class="flex items-center gap-2 text-xs">
                                <x-admin.badge :tone="$passes ? 'ok' : 'warn'">{{ str_replace('.', ',', (string) $ratio) }} : 1</x-admin.badge>
                                <span @class(['text-steel' => $passes, 'font-medium text-warn' => ! $passes])>{{ $label }}{{ $passes ? '' : ': плохо читается' }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if (collect($checks)->contains(fn ($check) => ! $check[2]))
                        <p class="mt-2 rounded-m border border-warn/30 bg-warn-soft px-3 py-2 text-xs font-medium text-warn" role="alert">
                            Предупреждение: с такими цветами часть текста будет трудно прочитать. Сохранить можно, но лучше подобрать более тёмный или более светлый оттенок.
                        </p>
                    @endif
                @else
                    <p class="text-xs text-steel">Впишите цвет в виде #RRGGBB, чтобы увидеть проверку.</p>
                @endif
            </div>
        </x-admin.card>

        {{-- Logo --}}
        <x-admin.card title="Логотип" hint="Официальные файлы Rongta. PNG, JPG или WebP, до {{ intdiv($c::LOGO_MAX_KB, 1024) }} МБ; лучше PNG с прозрачным фоном.">
            <div class="grid gap-5 md:grid-cols-3">
                @foreach ($c::LOGOS as $slot => [$label, $hint, $default])
                    @php
                        $prop = $uploadProps[$slot];
                        $new = $uploads[$slot];
                        $isDefault = ($logo[$slot] ?? $default) === $default;
                    @endphp
                    <div class="min-w-0" wire:key="logo-{{ $slot }}">
                        <p class="mb-1 text-xs font-semibold">{{ $label }}</p>
                        <div class="flex h-24 items-center justify-center rounded-m border border-line bg-paper p-3">
                            @if ($new)
                                <span class="break-all text-center text-xs font-semibold text-ok">Новый файл: {{ $new->getClientOriginalName() }}</span>
                            @else
                                <img src="{{ asset($logo[$slot] ?? $default) }}" alt="{{ $label }}: текущий файл" class="max-h-full max-w-full object-contain">
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-steel">{{ $hint }} {{ $new ? 'Будет заменён после сохранения.' : ($isDefault ? 'Сейчас: стандартный файл.' : 'Сейчас: загруженный файл.') }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <label class="inline-flex min-h-8 cursor-pointer items-center justify-center rounded-m border border-line bg-label px-2.5 text-xs font-semibold hover:bg-paper focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-ink">
                                Выбрать файл
                                <input type="file" class="sr-only" wire:model="{{ $prop }}" accept=".{{ str_replace(',', ',.', $c::LOGO_MIMES) }}" aria-label="{{ $label }}: выбрать файл">
                            </label>
                            @if ($new)
                                <x-admin.btn size="sm" wire:click="clearUpload('{{ $slot }}')">Отменить</x-admin.btn>
                            @elseif (! $isDefault)
                                <x-admin.btn size="sm" wire:click="useDefaultLogo('{{ $slot }}')">Вернуть стандартный</x-admin.btn>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-steel" wire:loading wire:target="{{ $prop }}">Загрузка файла…</p>
                        @error($prop) <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </x-admin.card>

        {{-- Home sections --}}
        <x-admin.card title="Блоки главной страницы" hint="Порядок сверху вниз. Выключенный блок на главной не показывается.">
            <ol class="divide-y divide-paper-2">
                @foreach ($sections as $i => $section)
                    <li wire:key="section-{{ $section['key'] }}" class="flex flex-wrap items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                        <span class="w-5 flex-none tabular-nums text-steel">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <x-admin.toggle wire:model="sections.{{ $i }}.visible" :label="$c::SECTION_LABELS[$section['key']] ?? $section['key']" />
                        </div>
                        <div class="flex gap-1.5">
                            <x-admin.btn size="sm" wire:click="moveSection({{ $i }}, -1)" :disabled="$i === 0"
                                         aria-label="{{ $c::SECTION_LABELS[$section['key']] ?? $section['key'] }}: выше">↑</x-admin.btn>
                            <x-admin.btn size="sm" wire:click="moveSection({{ $i }}, 1)" :disabled="$i === count($sections) - 1"
                                         aria-label="{{ $c::SECTION_LABELS[$section['key']] ?? $section['key'] }}: ниже">↓</x-admin.btn>
                        </div>
                    </li>
                @endforeach
            </ol>
            @error('sections') <p class="mt-2 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
            @error('sections.*.key') <p class="mt-2 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
        </x-admin.card>

        {{-- Hero --}}
        <x-admin.card title="Тексты первого экрана" hint="Здесь выбирается, какой текст интерфейса показывать. Сами тексты на трёх языках правятся в разделе «Тексты интерфейса».">
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($c::HERO_KEYS as $field => $label)
                    @php $text = $heroTexts[$hero[$field] ?? ''] ?? null; @endphp
                    <x-admin.field :label="$label" for="st-{{ $field }}" required :error="$errors->first('hero.'.$field)">
                        <x-admin.input id="st-{{ $field }}" wire:model.blur="hero.{{ $field }}" autocomplete="off" spellcheck="false" class="font-mono" :invalid="$errors->has('hero.'.$field)" />
                        @if ($text)
                            <p class="mt-1 text-xs text-steel">
                                <span class="line-clamp-2">«{{ $text->ru }}»</span>
                                @if (Route::has('admin.strings.edit'))
                                    <a href="{{ route('admin.strings.edit', $text) }}" class="font-semibold text-accent-ink hover:underline">Изменить текст</a>
                                @endif
                            </p>
                        @else
                            <p class="mt-1 text-xs font-medium text-warn">Текста с таким ключом нет.</p>
                        @endif
                    </x-admin.field>
                @endforeach
            </div>
            <x-slot:footer>
                <x-admin.form-actions label="Сохранить настройки" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
