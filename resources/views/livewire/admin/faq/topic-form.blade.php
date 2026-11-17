<x-admin.page :title="$title" :back="route('admin.faq.index')">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Тема вопросов">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Название темы" model="form.name" required />
                <div class="grid content-start grid-cols-2 gap-4">
                    <x-admin.field label="Код" for="t-slug" required :error="$errors->first('form.slug')" hint="Латиница, цифры и дефис.">
                        <x-admin.input id="t-slug" wire:model="form.slug" :invalid="$errors->has('form.slug')" />
                    </x-admin.field>
                    <x-admin.field label="Порядок" for="t-sort" required :error="$errors->first('form.sort')">
                        <x-admin.input type="number" min="0" id="t-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                    </x-admin.field>
                </div>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.faq.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
