<x-admin.page :title="$title" :back="route('admin.hero-items.index')" backLabel="Назад к образцам">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Товар на этикетке" hint="Пример для главной страницы: что показывают весы и что печатается на этикетке. Валюта на сайте не указывается.">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Название" model="form.name" required hint="До 60 знаков: на этикетке помещается две-три строки." />
                <x-admin.field label="Бренд" for="hi-brand" :error="$errors->first('form.brand')"
                               hint="Верхняя строка этикетки. Если пусто, печатается «{{ \App\Models\HeroItem::DEFAULT_BRAND }}».">
                    <x-admin.input id="hi-brand" wire:model="form.brand" maxlength="40" autocomplete="off" :invalid="$errors->has('form.brand')" />
                </x-admin.field>
            </div>
        </x-admin.card>

        <x-admin.card title="Числа на табло">
            <div class="grid gap-4 md:grid-cols-3">
                <x-admin.field label="Вес, кг" for="hi-weight" required :error="$errors->first('form.weight_kg')" hint="От 0.040 до 30. Например 1.250.">
                    <x-admin.input id="hi-weight" inputmode="decimal" wire:model.blur="form.weight_kg" autocomplete="off" :invalid="$errors->has('form.weight_kg')" />
                </x-admin.field>
                <x-admin.field label="За кг" for="hi-unit" required :error="$errors->first('form.unit_price')" hint="Целое число от 10 до 5 000 000.">
                    <x-admin.input type="number" min="10" max="5000000" step="1" id="hi-unit" wire:model.blur="form.unit_price" :invalid="$errors->has('form.unit_price')" />
                </x-admin.field>
                <div>
                    <p class="mb-1.5 text-xs font-semibold">Итог</p>
                    <p class="flex min-h-10 items-center text-lg font-semibold tabular-nums" id="hi-total" aria-live="polite">{{ $total ?? '—' }}</p>
                    <p class="mt-1 text-xs text-steel">Считается сам: вес × число «за кг».</p>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Показ на сайте">
            <div class="grid gap-4 md:grid-cols-[1fr_10rem]">
                <div class="grid content-end">
                    <x-admin.toggle wire:model="form.is_published" label="Опубликован" hint="Главная страница при каждой загрузке показывает один случайный опубликованный образец." />
                </div>
                <x-admin.field label="Порядок" for="hi-sort" required :error="$errors->first('form.sort')" hint="Только для этого списка.">
                    <x-admin.input type="number" min="0" id="hi-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                </x-admin.field>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.hero-items.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
