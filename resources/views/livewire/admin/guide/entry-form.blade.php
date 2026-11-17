<x-admin.page :title="$title" :back="route('admin.guide.index')" backLabel="Назад к гиду">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Раздел гида" hint="Одна колонка гида по выбору в базе знаний: по одной на семейство весов.">
            <div class="grid gap-4 sm:grid-cols-[1fr_10rem]">
                <x-admin.field label="Семейство" for="g-family" required :error="$errors->first('form.family_id')" hint="В списке семейства, у которых раздела ещё нет.">
                    <x-admin.select id="g-family" wire:model="form.family_id" placeholder="Выберите семейство" :options="$families" :invalid="$errors->has('form.family_id')" />
                </x-admin.field>
                <x-admin.field label="Порядок" for="g-sort" required :error="$errors->first('form.sort')">
                    <x-admin.input type="number" min="0" id="g-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                </x-admin.field>
            </div>
        </x-admin.card>

        <x-admin.card title="Тексты">
            <div class="grid gap-5 lg:grid-cols-2">
                @foreach ($fields as $field => [$label, $hint])
                    <x-admin.trans-textarea :label="$label" :model="'form.'.$field" :hint="$hint" :rows="3" required />
                @endforeach
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.guide.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
