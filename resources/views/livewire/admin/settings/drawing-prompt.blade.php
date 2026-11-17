<x-admin.page title="Настройки сайта" subtitle="Промпт, по которому ИИ рисует модели весов в стиле сайта.">
    @include('livewire.admin.settings.tabs', ['current' => 'admin.settings.lineart'])

    <form wire:submit="save" novalidate>
        <x-admin.card title="Промпт рисунков" hint="Используется кнопкой «Сгенерировать изображение (ИИ)» на странице модели и показывается там для копирования в другие ИИ. Пишется по-английски: так ИИ точнее следует правилам.">
            <x-admin.field label="Текст промпта" for="lineart-prompt" required :error="$errors->first('prompt')">
                <x-admin.textarea id="lineart-prompt" wire:model="prompt" rows="24" class="font-mono text-xs" :invalid="$errors->has('prompt')" />
            </x-admin.field>
            <p class="mt-2 text-xs text-steel">
                @if ($isDefault)
                    Сейчас действует стандартный промпт.
                @else
                    Сейчас действует изменённый промпт.
                @endif
                Цвета задаются только классами i-body, i-steel и другими из таблицы: так рисунок работает и на светлом, и на тёмном фоне.
            </p>
            <x-slot:footer>
                <div class="flex flex-wrap items-center gap-2">
                    <x-admin.btn type="submit" variant="primary">Сохранить</x-admin.btn>
                    @unless ($isDefault)
                        <x-admin.btn wire:click="restoreDefault" confirm="Вернуть стандартный промпт? Ваши изменения пропадут.">Вернуть стандартный</x-admin.btn>
                    @endunless
                </div>
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
