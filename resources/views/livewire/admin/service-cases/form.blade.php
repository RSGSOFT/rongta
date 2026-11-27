<x-admin.page :title="$title" :back="route('admin.service-cases.index')" backLabel="Назад к работам сервиса">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Работа" hint="Название работы и, если нужно, короткое примечание под ним. Вносите только то, что подтверждено RSG.">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Работа" model="form.title" required />
                <x-admin.trans-textarea label="Примечание" model="form.note" rows="2" />
            </div>
        </x-admin.card>

        <x-admin.card title="Тариф">
            <div class="grid gap-4 md:grid-cols-[1fr_14rem_1fr]">
                <x-admin.field label="Весы" for="sc-family" :error="$errors->first('form.family_id')" hint="К каким весам относится работа.">
                    <x-admin.select id="sc-family" wire:model="form.family_id" :options="$families" placeholder="Для всех весов" :invalid="$errors->has('form.family_id')" />
                </x-admin.field>
                <x-admin.field label="Тариф, сум" for="sc-amount" :error="$errors->first('form.amount')" hint="Целое число. Пока пусто, работа на сайте не видна.">
                    <x-admin.input inputmode="numeric" id="sc-amount" wire:model="form.amount" autocomplete="off" :invalid="$errors->has('form.amount')" />
                </x-admin.field>
                <div class="grid content-end">
                    <x-admin.toggle wire:model="form.is_from" label="Тариф «от»" hint="На сайте будет «от 150 000 сум», а не точное число." />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Показ на сайте">
            <div class="grid gap-4 md:grid-cols-[1fr_10rem]">
                <div class="grid content-end">
                    <x-admin.toggle wire:model="form.is_published" label="Опубликована" hint="Выключите, чтобы скрыть работу из Базы знаний." />
                </div>
                <x-admin.field label="Порядок" for="sc-sort" required :error="$errors->first('form.sort')">
                    <x-admin.input type="number" min="0" id="sc-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                </x-admin.field>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.service-cases.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
