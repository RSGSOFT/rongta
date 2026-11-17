<x-admin.page :title="$title" :back="route('admin.guide.index')" backLabel="Назад к гиду">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Пункт списка «Проверьте перед покупкой»">
            <div class="grid gap-4 md:grid-cols-[1fr_10rem]">
                <x-admin.trans-textarea label="Текст пункта" model="form.text" rows="2" required />
                <x-admin.field label="Порядок" for="g-sort" required :error="$errors->first('form.sort')">
                    <x-admin.input type="number" min="0" id="g-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                </x-admin.field>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.guide.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
