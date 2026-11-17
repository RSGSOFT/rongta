<x-admin.page :title="'Характеристики: '.$model->name" :back="route('admin.models.edit', $model)" backLabel="Назад к модели"
              subtitle="Источник значений: страница модели на rongtatech.com. Неподтверждённое значение отмечайте «Уточняется», не вписывайте догадки.">
    <x-slot:actions>
        @if ($model->source_url)
            <x-admin.btn :href="$model->source_url" target="_blank" rel="noopener noreferrer">Страница производителя ↗</x-admin.btn>
        @endif
    </x-slot:actions>

    <form wire:submit="save" novalidate>
        @foreach ($groups as $group)
            <x-admin.card :title="$group['label']" flush wire:key="group-{{ $group['id'] }}">
                <div class="divide-y divide-paper-2">
                    @foreach ($group['keys'] as $item)
                        @php $key = $item['key']; $row = $rows[$key]; @endphp
                        <div wire:key="spec-{{ $key }}" class="grid gap-3 px-4 py-3 sm:px-5 md:grid-cols-[15rem_1fr]"
                             x-data="{
                                get on() { return !!$wire.$get('rows.{{ $key }}.on') },
                                get tbc() { return !!$wire.$get('rows.{{ $key }}.tbc') },
                                get shape() { return $wire.$get('rows.{{ $key }}.shape') },
                             }">
                            <div class="min-w-0">
                                <div class="font-semibold">{{ $item['label'] }}</div>
                                <div class="text-xs text-steel">
                                    <code>{{ $key }}</code>
                                    @unless ($item['hasLabel'])
                                        · <span class="text-warn">нет текста «spec.{{ $key }}» в текстах интерфейса</span>
                                    @endunless
                                </div>
                                <div class="mt-2 space-y-1.5">
                                    <x-admin.toggle wire:model="rows.{{ $key }}.on" label="Показывать на сайте" />
                                    <div x-show="on" x-cloak>
                                        <x-admin.toggle wire:model="rows.{{ $key }}.tbc" label="Уточняется" />
                                    </div>
                                </div>
                            </div>

                            <div class="min-w-0">
                                <p x-show="!on" class="text-xs text-steel">Не указано: строка не выводится на странице модели и в сравнении.</p>

                                <p x-show="on && tbc" x-cloak class="rounded-m border border-[#E2B93B] bg-warn-soft px-3 py-2 text-xs text-warn">
                                    На сайте будет показано: «{{ $tbc['ru'] }}» / «{{ $tbc['uz'] }}» / «{{ $tbc['en'] }}».
                                </p>

                                <div x-show="on && !tbc" x-cloak class="space-y-3">
                                    <div class="max-w-xs">
                                        <label class="sr-only" for="shape-{{ $key }}">Вид значения: {{ $item['label'] }}</label>
                                        <x-admin.select id="shape-{{ $key }}" wire:model.live="rows.{{ $key }}.shape" class="!min-h-8 !py-1 !text-xs"
                                                        :options="\App\Livewire\Admin\Items\Models\Specs::SHAPES" />
                                    </div>

                                    <div x-show="shape !== 'parts'">
                                        <x-admin.trans-input label="Значение" model="rows.{{ $key }}.text" required />
                                    </div>

                                    <div x-show="shape === 'note'" x-cloak>
                                        <x-admin.trans-input label="Примечание (оговорка к значению)" model="rows.{{ $key }}.note" required />
                                    </div>

                                    <div x-show="shape === 'parts'" x-cloak class="space-y-3">
                                        @foreach ($row['parts'] as $i => $part)
                                            <div wire:key="part-{{ $key }}-{{ $i }}" class="rounded-m border border-line p-3">
                                                <div class="mb-2 flex items-center justify-between gap-2">
                                                    <span class="text-xs font-semibold">Версия {{ $i + 1 }}</span>
                                                    <x-admin.btn size="sm" variant="danger" wire:click="removePart('{{ $key }}', {{ $i }})">Убрать</x-admin.btn>
                                                </div>
                                                <div class="grid gap-3 lg:grid-cols-2">
                                                    <x-admin.trans-input label="Название версии" model="rows.{{ $key }}.parts.{{ $i }}.label" required />
                                                    <x-admin.trans-input label="Значение" model="rows.{{ $key }}.parts.{{ $i }}.value" required />
                                                </div>
                                            </div>
                                        @endforeach
                                        @if (count($row['parts']) < 6)
                                            <x-admin.btn size="sm" wire:click="addPart('{{ $key }}')">Добавить версию</x-admin.btn>
                                        @endif
                                        @error("rows.{$key}.parts") <p class="text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex flex-wrap items-start gap-2 px-4 py-3 sm:px-5">
                        <div class="w-full sm:w-64">
                            <label class="sr-only" for="newkey-{{ $group['id'] }}">Новый ключ характеристики в группе «{{ $group['label'] }}»</label>
                            <x-admin.input id="newkey-{{ $group['id'] }}" wire:model="newKey.{{ $group['id'] }}" wire:keydown.enter.prevent="addKey('{{ $group['id'] }}')"
                                           class="!min-h-8 !py-1 !text-xs" placeholder="Ключ новой характеристики, напр. loadCell" />
                            @error("newKey.{$group['id']}") <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <x-admin.btn size="sm" wire:click="addKey('{{ $group['id'] }}')">Добавить характеристику</x-admin.btn>
                        <span class="basis-full text-xs text-steel">Название новой строки берётся из текста интерфейса «spec.ключ»: добавьте его в разделе «Тексты интерфейса».</span>
                    </div>
                </div>
            </x-admin.card>
        @endforeach

        <div class="sticky bottom-0 z-10 -mx-4 border-t border-line bg-label/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6">
            <x-admin.form-actions :cancel="route('admin.models.edit', $model)" label="Сохранить характеристики" />
        </div>
    </form>
</x-admin.page>
