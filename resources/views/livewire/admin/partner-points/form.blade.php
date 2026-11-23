<x-admin.page :title="$title" :back="route('admin.partner-points.index')" backLabel="Назад к странице «Партнёрам»">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Пункт" hint="Вносите только то, что подтверждено RSG.">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Заголовок" model="form.title" required />
                <x-admin.trans-textarea label="Пояснение" model="form.body" rows="3" />
            </div>
        </x-admin.card>

        <x-admin.card title="Показ на сайте">
            <div class="grid gap-4 md:grid-cols-[1fr_1fr_10rem]">
                <x-admin.field label="Блок" for="pp-kind" required :error="$errors->first('form.kind')">
                    <x-admin.select id="pp-kind" wire:model="form.kind" :options="\App\Livewire\Admin\PartnerPoints\Form::KINDS" :invalid="$errors->has('form.kind')" />
                </x-admin.field>
                <div class="grid content-end">
                    <x-admin.toggle wire:model="form.is_published" label="Опубликован" hint="Выключите, чтобы скрыть пункт со страницы «Партнёрам»." />
                </div>
                <x-admin.field label="Порядок" for="pp-sort" required :error="$errors->first('form.sort')">
                    <x-admin.input type="number" min="0" id="pp-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                </x-admin.field>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.partner-points.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
