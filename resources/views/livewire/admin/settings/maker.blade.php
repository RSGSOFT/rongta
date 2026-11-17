<x-admin.page title="Настройки сайта" subtitle="Карточка «О производителе» в блоке доверия на главной. Только проверенные цифры с rongtatech.com.">
    @include('livewire.admin.settings.tabs', ['current' => 'admin.settings.maker'])

    <form wire:submit="save" novalidate>
        <x-admin.card title="Строки карточки" hint="Порядок сверху вниз. Выключенная строка на сайте не показывается; если выключены все, карточки нет.">
            @if ($rows)
                <ol class="divide-y divide-paper-2">
                    @foreach ($rows as $i => $row)
                        <li class="grid gap-3 py-4 first:pt-0 last:pb-0" wire:key="maker-row-{{ $i }}">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="w-5 flex-none tabular-nums text-steel">{{ $i + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <x-admin.toggle wire:model.live="rows.{{ $i }}.visible" label="Показывать на сайте" />
                                </div>
                                <div class="flex gap-1.5">
                                    <x-admin.btn size="sm" wire:click="moveRow({{ $i }}, -1)" :disabled="$i === 0" aria-label="Строка {{ $i + 1 }}: выше">↑</x-admin.btn>
                                    <x-admin.btn size="sm" wire:click="moveRow({{ $i }}, 1)" :disabled="$i === count($rows) - 1" aria-label="Строка {{ $i + 1 }}: ниже">↓</x-admin.btn>
                                    <x-admin.btn size="sm" variant="danger" wire:click="removeRow({{ $i }})" confirm="Удалить строку {{ $i + 1 }}?">Удалить</x-admin.btn>
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <x-admin.trans-input label="Название" :model="'rows.'.$i.'.label'" required />
                                <x-admin.trans-input label="Значение" :model="'rows.'.$i.'.value'" required />
                            </div>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="text-steel">Строк нет: карточка на сайте не показывается.</p>
            @endif
            @error('rows') <p class="mt-2 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <x-admin.btn size="sm" wire:click="addRow" :disabled="count($rows) >= \App\Livewire\Admin\Settings\Maker::MAX_ROWS">Добавить строку</x-admin.btn>
                <span class="text-xs text-steel" aria-live="polite">На сайте строк: {{ $visibleCount }}.</span>
            </div>
        </x-admin.card>

        <x-admin.card title="Источник" hint="Подпись под цифрами. Пусто = без подписи.">
            <x-admin.trans-input label="Подпись" model="source" />
            <x-slot:footer>
                <x-admin.form-actions label="Сохранить" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
