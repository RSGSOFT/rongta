<x-admin.page :title="$title" :back="route('admin.chooser.index')">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Вариант ответа" hint="Блок «Какие весы вам нужны?» на главной странице: кнопка, рекомендуемое семейство и объяснение.">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Надпись на кнопке" model="form.label" required hint="Например: Магазин у дома." />
                <x-admin.trans-textarea label="Почему подходит" model="form.why" rows="3" required />

                <x-admin.field label="Рекомендуемое семейство" for="ch-family" required :error="$errors->first('form.family_id')"
                               hint="Его название показывается заголовком, а ссылка ведёт в каталог этого семейства.">
                    <x-admin.select id="ch-family" wire:model="form.family_id" placeholder="Выберите семейство" :options="$families" :invalid="$errors->has('form.family_id')" />
                </x-admin.field>

                <div class="grid grid-cols-2 gap-4">
                    <x-admin.field label="Код" for="ch-key" required :error="$errors->first('form.key')"
                                   :hint="$optionId ? 'У существующего варианта код не меняется.' : 'Латиница, например shop. После создания не меняется.'">
                        <x-admin.input id="ch-key" wire:model="form.key" :disabled="(bool) $optionId" :invalid="$errors->has('form.key')" />
                    </x-admin.field>
                    <x-admin.field label="Порядок" for="ch-sort" required :error="$errors->first('form.sort')">
                        <x-admin.input type="number" min="0" id="ch-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                    </x-admin.field>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Рекомендуемые модели" hint="Показываются карточками рядом с объяснением, в этом порядке. Скрытые с сайта модели посетители не видят.">
            <x-slot:tools>
                <x-admin.btn size="sm" wire:click="addModel" :disabled="count($models) >= $max">Добавить модель</x-admin.btn>
            </x-slot:tools>

            @error('models') <p class="mb-3 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror

            @forelse ($models as $i => $row)
                <div wire:key="model-row-{{ $i }}-{{ $row['id'] }}" class="grid gap-4 border-b border-paper-2 py-4 first:pt-0 last:border-b-0 last:pb-0 md:grid-cols-[16rem_1fr_auto]">
                    <x-admin.field label="Модель {{ $i + 1 }}" for="ch-model-{{ $i }}" required :error="$errors->first('models.'.$i.'.id')">
                        <x-admin.select id="ch-model-{{ $i }}" wire:model="models.{{ $i }}.id" placeholder="Выберите модель" :options="$allModels" :invalid="$errors->has('models.'.$i.'.id')" />
                    </x-admin.field>
                    <x-admin.trans-input label="Примечание под названием" :model="'models.'.$i.'.note'" hint="Необязательно. Если пусто, показывается тип модели." />
                    <div class="flex items-start gap-1.5 md:pt-5">
                        <x-admin.btn size="sm" wire:click="moveModel({{ $i }}, -1)" :disabled="$i === 0" aria-label="Модель {{ $i + 1 }}: выше">↑</x-admin.btn>
                        <x-admin.btn size="sm" wire:click="moveModel({{ $i }}, 1)" :disabled="$i === count($models) - 1" aria-label="Модель {{ $i + 1 }}: ниже">↓</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="removeModel({{ $i }})" aria-label="Убрать модель {{ $i + 1 }}">Убрать</x-admin.btn>
                    </div>
                </div>
            @empty
                <p class="text-steel">Моделей нет: на главной будет показано только семейство и объяснение.</p>
            @endforelse

            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.chooser.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
