@php $opt = \App\Livewire\Admin\Partners\Options::class; @endphp
<x-admin.page :title="$title" :back="route('admin.partners.index')">
    <form wire:submit="save" novalidate>
        @if ($wasSample)
            <div class="mb-5 rounded-m border border-warn/30 bg-warn-soft px-3 py-2.5 text-warn" role="note">
                <p class="font-semibold">Это образец из прототипа, а не настоящий партнёр.</p>
                <p class="mt-0.5 text-xs">Чтобы заменить его: впишите настоящие название, регион, город и контакты, затем выключите переключатель «Образец» внизу и сохраните. Пока он включён, сайт показывает пометку «образец» и не показывает телефон.</p>
            </div>
        @endif

        <x-admin.card title="Партнёр">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Название" model="form.name" required />

                <div class="grid content-start gap-4">
                    <x-admin.field label="Тип" for="p-kind" required :error="$errors->first('form.kind')"
                                   hint="Реселлеры и интеграторы показываются на разных вкладках страницы дилеров.">
                        <x-admin.select id="p-kind" wire:model="form.kind" :options="$opt::KINDS" :invalid="$errors->has('form.kind')" />
                    </x-admin.field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.field label="Регион" for="p-region" required :error="$errors->first('form.region_id')">
                            <x-admin.select id="p-region" wire:model.live="form.region_id" placeholder="Выберите регион" :options="$regions" :invalid="$errors->has('form.region_id')" />
                        </x-admin.field>
                        <x-admin.field label="Город" for="p-city" :error="$errors->first('form.city_id')" hint="Список зависит от региона.">
                            <x-admin.select id="p-city" wire:model="form.city_id" wire:key="city-of-{{ $form['region_id'] }}" placeholder="Не указан" :options="$cities" :invalid="$errors->has('form.city_id')" />
                        </x-admin.field>
                    </div>
                </div>

                <fieldset class="min-w-0 md:col-span-2">
                    <legend class="mb-1.5 text-xs font-semibold">Что предлагает</legend>
                    <div class="flex flex-wrap gap-x-5 gap-y-2">
                        @foreach ($opt::OFFERS as $value => $label)
                            <label class="inline-flex min-h-8 cursor-pointer items-center gap-2">
                                <input type="checkbox" value="{{ $value }}" wire:model="form.offers" class="h-4 w-4 accent-[var(--accent-ink)]">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('form.offers') <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                    @error('form.offers.*') <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                </fieldset>
            </div>
        </x-admin.card>

        <x-admin.card title="Контакты" hint="Показываются на странице дилеров только у настоящих партнёров (не у образцов).">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid content-start gap-4">
                    <x-admin.field label="Телефон" for="p-phone" :error="$errors->first('form.phone')" hint="Например +998 71 200 00 00.">
                        <x-admin.input type="tel" id="p-phone" wire:model="form.phone" autocomplete="off" :invalid="$errors->has('form.phone')" />
                    </x-admin.field>
                    <x-admin.field label="Telegram" for="p-telegram" :error="$errors->first('form.telegram')" hint="Имя пользователя, например @rongta_uz.">
                        <x-admin.input id="p-telegram" wire:model="form.telegram" autocomplete="off" :invalid="$errors->has('form.telegram')" />
                    </x-admin.field>
                </div>
                <x-admin.trans-input label="Адрес" model="form.address" />
            </div>
        </x-admin.card>

        <x-admin.card title="Показ на сайте">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid content-start gap-3">
                    <x-admin.toggle wire:model="form.is_published" label="Опубликован" hint="Выключите, чтобы скрыть партнёра со страницы дилеров." />
                    <x-admin.toggle wire:model="form.is_placeholder" label="Образец" hint="Запись-пример: на сайте с пометкой «образец» и без контактов. Для настоящего партнёра выключите." />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-admin.field label="Порядок" for="p-sort" required :error="$errors->first('form.sort')" hint="Порядок в списке дилеров.">
                        <x-admin.input type="number" min="0" id="p-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                    </x-admin.field>
                    <x-admin.field label="Код" for="p-slug" :error="$errors->first('form.slug')" hint="Необязательно. Латиница, цифры и дефис.">
                        <x-admin.input id="p-slug" wire:model="form.slug" :invalid="$errors->has('form.slug')" />
                    </x-admin.field>
                </div>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.partners.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
