@assets
    @vite('resources/js/serial-import.js')
@endassets

<div x-data="serialImport(@js($config))">
    <x-admin.page title="Загрузка серийных номеров" :back="route('admin.serials.index')" backLabel="Назад к серийным номерам"
                  subtitle="Выберите файл: сначала покажем, что в нём и что не так. В базу ничего не попадает, пока вы не нажмёте «Загрузить».">
        <x-admin.card title="1. Файл" hint="Excel (.xlsx) или CSV, до {{ $maxMb }} МБ и {{ number_format($maxRows, 0, '', ' ') }} строк. Берётся первый лист. Файл читается в браузере и на сервер не отправляется: туда уходят только его строки.">
            <div class="grid gap-4 md:grid-cols-[1fr_1fr]">
                <div>
                    <x-admin.field label="Файл" for="serial-file" required>
                        <input type="file" id="serial-file" x-ref="file" x-on:change="pick($event)" accept=".xlsx,.csv" class="adm-control !h-auto py-2">
                    </x-admin.field>
                    <div class="mt-3">
                        <x-admin.toggle x-model="hasHeader" x-on:change="check()" id="serial-header" label="Первая строка — заголовки" hint="Выключите, если данные начинаются с первой строки." />
                    </div>
                </div>
                <div class="rounded-m border border-line bg-paper/60 p-3 text-xs text-steel">
                    <p class="mb-1.5 font-semibold text-ink">Три колонки, в таком порядке</p>
                    <ol class="list-decimal space-y-1 pl-4">
                        <li>Серийный номер: латинские буквы и цифры. В Excel задайте колонке формат «Текстовый», иначе длинные номера искажаются.</li>
                        <li>Модель: как на сайте, например RLS1530, RLS1100A, C11, AURORA S2L-AI.</li>
                        <li>Дата импорта: 31.12.2026 или 2026-12-31.</li>
                    </ol>
                </div>
            </div>
        </x-admin.card>

        <template x-if="preview">
            <div id="serial-preview">
                <x-admin.card title="2. Проверка файла">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-admin.badge>Строк: <span x-text="preview.total"></span></x-admin.badge>
                        <x-admin.badge tone="ok">Будет загружено: <span x-text="preview.valid"></span></x-admin.badge>
                        <template x-if="preview.skipped">
                            <x-admin.badge tone="warn">Будет пропущено: <span x-text="preview.skipped"></span></x-admin.badge>
                        </template>
                    </div>

                    <template x-if="preview.models.length">
                        <p class="mt-3 text-steel">
                            По моделям:
                            <template x-for="(entry, index) in preview.models" :key="entry[0]">
                                <span><span class="whitespace-nowrap font-medium text-ink" x-text="entry[0] + ' — ' + entry[1]"></span><span x-show="index < preview.models.length - 1">, </span></span>
                            </template>
                        </p>
                    </template>

                    <x-slot:footer>
                        <x-admin.btn variant="primary" x-on:click="load()" id="serial-load" x-bind:disabled="preview.valid === 0">Загрузить <span x-text="preview.valid"></span> в базу</x-admin.btn>
                        <x-admin.btn x-on:click="again()">Выбрать другой файл</x-admin.btn>
                    </x-slot:footer>
                </x-admin.card>

                <template x-if="preview.problems.length">
                    <x-admin.table>
                        <x-slot:toolbar>
                            <div>
                                <h2 class="font-semibold">Строки, которые не загрузятся (<span x-text="preview.skipped"></span>)</h2>
                                <p class="text-xs text-steel">Исправьте их в файле и выберите его снова, или загрузите остальные как есть. <span x-show="preview.skipped > preview.problems.length">Показаны первые <span x-text="preview.problems.length"></span>.</span></p>
                            </div>
                        </x-slot:toolbar>
                        <x-slot:head>
                            <x-admin.th>Строка</x-admin.th>
                            <x-admin.th>Серийный номер</x-admin.th>
                            <x-admin.th>Причина</x-admin.th>
                        </x-slot:head>
                        <template x-for="problem in preview.problems" :key="'problem-' + problem.line">
                            <tr>
                                <td class="w-20 tabular-nums text-steel" x-text="problem.line"></td>
                                <td class="font-mono text-xs" x-text="problem.serial || '—'"></td>
                                <td class="text-warn" x-text="problem.reason"></td>
                            </tr>
                        </template>
                    </x-admin.table>
                </template>

                <template x-if="preview.sample.length">
                    <x-admin.table>
                        <x-slot:toolbar>
                            <div>
                                <h2 class="font-semibold">Что будет загружено</h2>
                                <p class="text-xs text-steel" x-text="preview.valid > preview.sample.length ? 'Первые ' + preview.sample.length + ' из ' + preview.valid + '.' : 'Все строки.'"></p>
                            </div>
                        </x-slot:toolbar>
                        <x-slot:head>
                            <x-admin.th>Строка</x-admin.th>
                            <x-admin.th>Серийный номер</x-admin.th>
                            <x-admin.th>Модель в файле</x-admin.th>
                            <x-admin.th>Модель на сайте</x-admin.th>
                            <x-admin.th>Дата импорта</x-admin.th>
                        </x-slot:head>
                        <template x-for="row in preview.sample" :key="'row-' + row.line">
                            <tr>
                                <td class="w-20 tabular-nums text-steel" x-text="row.line"></td>
                                <td class="font-mono text-xs" x-text="row.serial"></td>
                                <td x-text="row.model_text"></td>
                                <td class="font-medium" x-text="row.model"></td>
                                <td class="tabular-nums" x-text="day(row.imported_on)"></td>
                            </tr>
                        </template>
                    </x-admin.table>
                </template>
            </div>
        </template>

        {{-- Status window: progress while the file is read, checked or loaded, then the outcome. --}}
        <div x-show="busy" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-ink/40 p-4"
             role="dialog" aria-modal="true" aria-labelledby="serial-busy-title" id="serial-busy">
            <div class="w-full max-w-sm rounded-l border border-line bg-label p-6 text-center shadow-card">
                <svg viewBox="0 0 24 24" class="mx-auto mb-3 h-8 w-8 animate-spin text-accent motion-reduce:animate-none" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9"/></svg>
                <h2 class="font-semibold" id="serial-busy-title" x-text="busy?.title"></h2>
                <div x-show="busy?.total" class="mt-3">
                    <div class="h-2 overflow-hidden rounded-full bg-line" role="progressbar" aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="percent" aria-labelledby="serial-busy-title">
                        <div class="h-full bg-accent transition-[width]" x-bind:style="'width:' + percent + '%'"></div>
                    </div>
                    <p class="mt-1.5 text-xs tabular-nums text-steel" x-text="(busy?.done ?? 0) + ' из ' + (busy?.total ?? 0)"></p>
                </div>
                <p class="mt-1 text-xs text-steel">Не закрывайте страницу.</p>
            </div>
        </div>

        <div x-show="result || failure" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-ink/40 p-4"
             role="dialog" aria-modal="true" aria-labelledby="serial-status-title" id="serial-status"
             x-on:keydown.escape.window="(result || failure) && again()">
            <div class="w-full max-w-md rounded-l border border-line bg-label p-6 shadow-card">
                <template x-if="result">
                    <div>
                        <h2 class="text-lg font-semibold text-ok" id="serial-status-title">Загрузка завершена</h2>
                        <dl class="mt-3 grid grid-cols-[1fr_auto] gap-x-4 gap-y-1.5">
                            <dt class="text-steel">Загружено серийных номеров</dt>
                            <dd class="text-right font-semibold tabular-nums" x-text="result.added"></dd>
                            <dt class="text-steel">Пропущено строк</dt>
                            <dd class="text-right font-semibold tabular-nums" x-text="result.skipped"></dd>
                        </dl>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <x-admin.btn variant="primary" href="#" x-bind:href="result.url" x-init="$nextTick(() => $el.focus())">Открыть партию</x-admin.btn>
                            <x-admin.btn x-on:click="again()">Загрузить ещё файл</x-admin.btn>
                        </div>
                    </div>
                </template>
                <template x-if="failure">
                    <div>
                        <h2 class="text-lg font-semibold text-danger" id="serial-status-title">Файл не загружен</h2>
                        <p class="mt-2" role="alert" x-text="failure"></p>
                        <div class="mt-5">
                            <x-admin.btn variant="primary" x-on:click="again()" x-init="$nextTick(() => $el.focus())">Выбрать другой файл</x-admin.btn>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </x-admin.page>
</div>
