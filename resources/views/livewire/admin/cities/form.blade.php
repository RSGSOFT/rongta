<x-admin.page :title="$title" :back="route('admin.cities.index')">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Город">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Название" model="form.name" required />

                <div class="grid content-start gap-4">
                    <x-admin.field label="Регион" for="c-region" required :error="$errors->first('form.region_id')"
                                   hint="Если у города есть партнёры, они перейдут в выбранный регион вместе с ним.">
                        <x-admin.select id="c-region" wire:model="form.region_id" placeholder="Выберите регион" :options="$regions" :invalid="$errors->has('form.region_id')" />
                    </x-admin.field>

                    <div class="grid grid-cols-2 gap-4">
                        <x-admin.field label="Код" for="c-slug" required :error="$errors->first('form.slug')" hint="Латиница, цифры и дефис.">
                            <x-admin.input id="c-slug" wire:model="form.slug" :invalid="$errors->has('form.slug')" />
                        </x-admin.field>
                        <x-admin.field label="Порядок" for="c-sort" required :error="$errors->first('form.sort')">
                            <x-admin.input type="number" min="0" id="c-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                        </x-admin.field>
                    </div>
                </div>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.cities.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
