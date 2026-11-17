@php $opt = \App\Livewire\Admin\Partners\Options::class; @endphp
<x-admin.page :title="$title" :back="route('admin.regions.index')">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Регион">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Название" model="form.name" required />
                <x-admin.trans-input label="Короткое название" model="form.short" required live
                                     hint="Подпись на клетке карты, до 24 символов." />

                <x-admin.field label="Код" for="r-slug" required :error="$errors->first('form.slug')"
                               hint="Латиница, цифры и дефис. Используется в адресе страницы дилеров; у существующего региона менять не рекомендуется.">
                    <x-admin.input id="r-slug" wire:model="form.slug" :invalid="$errors->has('form.slug')" />
                </x-admin.field>

                <x-admin.field label="Порядок" for="r-sort" required :error="$errors->first('form.sort')" hint="Порядок в списках регионов.">
                    <x-admin.input type="number" min="0" id="r-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                </x-admin.field>
            </div>
        </x-admin.card>

        <x-admin.card title="Место на карте" hint="Карта на странице дилеров схематичная: каждая область занимает одну клетку.">
            <div class="grid gap-5 lg:grid-cols-[14rem_1fr]">
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-1">
                    <x-admin.field label="Колонка (1–{{ $opt::MAP_COLS }})" for="r-col" required :error="$errors->first('form.map_col')" hint="Слева направо.">
                        <x-admin.input type="number" min="1" max="{{ $opt::MAP_COLS }}" id="r-col" wire:model.live.debounce.300ms="form.map_col" :invalid="$errors->has('form.map_col')" />
                    </x-admin.field>
                    <x-admin.field label="Ряд (1–{{ $opt::MAP_ROWS }})" for="r-row" required :error="$errors->first('form.map_row')" hint="Сверху вниз.">
                        <x-admin.input type="number" min="1" max="{{ $opt::MAP_ROWS }}" id="r-row" wire:model.live.debounce.300ms="form.map_row" :invalid="$errors->has('form.map_row')" />
                    </x-admin.field>
                </div>
                <div class="min-w-0">
                    <p class="mb-2 text-xs font-semibold">Предпросмотр</p>
                    @include('livewire.admin.regions.map', ['regions' => $others, 'tile' => $tile, 'label' => trim((string) ($form['short']['ru'] ?? ''))])
                    <div class="mt-2 text-xs" aria-live="polite">
                        @if (! $tile)
                            <p class="font-medium text-danger">Клетка вне карты: укажите колонку от 1 до {{ $opt::MAP_COLS }} и ряд от 1 до {{ $opt::MAP_ROWS }}.</p>
                        @elseif ($occupant)
                            <p class="font-medium text-danger">Клетка занята регионом «{{ $occupant->tr('name', 'ru') }}».</p>
                        @else
                            <p class="text-steel">Клетка свободна: колонка {{ $tile[0] }}, ряд {{ $tile[1] }}.</p>
                        @endif
                    </div>
                </div>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.regions.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
