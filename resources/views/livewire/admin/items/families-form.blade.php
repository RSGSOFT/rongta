<x-admin.page :title="$title" :back="route('admin.families.index')">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Семейство">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Название" model="form.name" required />
                <x-admin.trans-textarea label="Описание одной фразой" model="form.line" rows="3" required />

                <x-admin.field label="Код" for="f-slug" required :error="$errors->first('form.slug')"
                               hint="Латиница, цифры и дефис. Используется в адресах и фильтрах каталога; у существующего семейства менять не рекомендуется.">
                    <x-admin.input id="f-slug" wire:model="form.slug" :invalid="$errors->has('form.slug')" />
                </x-admin.field>

                <div class="grid grid-cols-2 gap-4">
                    <x-admin.field label="Иллюстрация" for="f-pic" :error="$errors->first('form.pic')" hint="Рисунок на карточке семейства.">
                        <x-admin.select id="f-pic" wire:model="form.pic" placeholder="Не выбрана" :options="\App\Livewire\Admin\Items\Catalog::KINDS" />
                    </x-admin.field>
                    <x-admin.field label="Порядок" for="f-sort" required :error="$errors->first('form.sort')">
                        <x-admin.input type="number" min="0" id="f-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                    </x-admin.field>
                </div>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.families.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
