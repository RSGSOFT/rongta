<x-admin.page :title="$title" :back="route('admin.videos.index')">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Видео">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Название" model="form.title" required class="md:col-span-2" />

                <x-admin.field label="Ссылка на видео" for="v-url" class="md:col-span-2" :error="$errors->first('form.url')"
                               hint="Полный адрес, начинается с https://. Пока ссылки нет, оставьте поле пустым.">
                    <x-admin.input type="url" id="v-url" wire:model="form.url" placeholder="https://…" :invalid="$errors->has('form.url')" />
                </x-admin.field>

                <x-admin.field label="Семейство" for="v-family" :error="$errors->first('form.family_id')">
                    <x-admin.select id="v-family" wire:model="form.family_id" placeholder="Все семейства" :options="$families" />
                </x-admin.field>

                <div class="grid grid-cols-2 gap-4">
                    <x-admin.field label="Порядок" for="v-sort" required :error="$errors->first('form.sort')">
                        <x-admin.input type="number" min="0" id="v-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                    </x-admin.field>
                    <x-admin.field label="Код" for="v-slug" :error="$errors->first('form.slug')" hint="Необязательно.">
                        <x-admin.input id="v-slug" wire:model="form.slug" :invalid="$errors->has('form.slug')" />
                    </x-admin.field>
                </div>

                <div class="md:col-span-2">
                    <x-admin.toggle wire:model="form.is_placeholder" label="Заглушка"
                                    hint="Запись-образец без настоящего видео. Снимите отметку, когда добавлена реальная ссылка." />
                </div>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.videos.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
