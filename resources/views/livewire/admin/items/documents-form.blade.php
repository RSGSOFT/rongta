@php
    $cat = \App\Livewire\Admin\Items\Catalog::class;
    $fileHint = 'Допустимые типы: '.str_replace(',', ', ', $cat::FILE_MIMES).'. До '.intdiv($cat::FILE_MAX_KB, 1024).' МБ. Установщики (.exe, .msi) загружайте в архиве ZIP.';
@endphp
<x-admin.page :title="$title" :back="route('admin.documents.index')">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Документ">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="Модель" for="d-model" required :error="$errors->first('form.scale_model_id')">
                    <x-admin.select id="d-model" wire:model="form.scale_model_id" placeholder="Выберите модель" :options="$models" :invalid="$errors->has('form.scale_model_id')" />
                </x-admin.field>

                <x-admin.field label="Тип документа" for="d-type" required :error="$errors->first('form.type')">
                    <x-admin.select id="d-type" wire:model="form.type" :options="$cat::DOCUMENT_TYPES" />
                </x-admin.field>

                <x-admin.trans-input label="Название" model="form.title" class="md:col-span-2"
                                     hint="Можно оставить пустым: тогда на сайте показывается название типа документа." />

                <x-admin.field label="Версия" for="d-version" :error="$errors->first('form.version')" hint="Как указано в самом файле, например 1.2. Не знаете точно: оставьте пустым.">
                    <x-admin.input id="d-version" wire:model="form.version" :invalid="$errors->has('form.version')" />
                </x-admin.field>

                <x-admin.field label="Дата выпуска" for="d-date" :error="$errors->first('form.released_on')">
                    <x-admin.input type="date" id="d-date" wire:model="form.released_on" :invalid="$errors->has('form.released_on')" />
                </x-admin.field>

                <x-admin.field label="Порядок" for="d-sort" required :error="$errors->first('form.sort')">
                    <x-admin.input type="number" min="0" id="d-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                </x-admin.field>

                <x-admin.field label="Код" for="d-slug" :error="$errors->first('form.slug')" hint="Необязательно. Строчная латиница, цифры и дефис.">
                    <x-admin.input id="d-slug" wire:model="form.slug" :invalid="$errors->has('form.slug')" />
                </x-admin.field>
            </div>
        </x-admin.card>

        <x-admin.card title="Файл" hint="Файл сохраняется при нажатии «Сохранить».">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <div class="rounded-m border border-line bg-paper px-3 py-3">
                        @if ($newFile)
                            <div class="font-semibold">{{ $newFile->getClientOriginalName() }}</div>
                            <div class="text-xs text-accent-ink">Новый файл, ещё не сохранён</div>
                        @elseif ($filePath && ! $removeFile)
                            <a href="{{ asset('storage/'.$filePath) }}" target="_blank" rel="noopener" class="break-all font-semibold text-accent-ink hover:underline">{{ basename($filePath) }}</a>
                            <div class="text-xs text-steel">Текущий файл</div>
                        @elseif ($filePath && $removeFile)
                            <div class="break-all font-semibold text-danger line-through">{{ basename($filePath) }}</div>
                            <div class="text-xs text-danger">Будет удалён при сохранении</div>
                        @else
                            <div class="font-semibold">Файл не загружен</div>
                            <div class="text-xs text-steel">На сайте документ показывается без ссылки для скачивания.</div>
                        @endif
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <label class="inline-flex min-h-8 cursor-pointer items-center rounded-m border border-line bg-label px-2.5 text-xs font-semibold hover:bg-paper focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-ink">
                            {{ $filePath || $newFile ? 'Заменить файл' : 'Выбрать файл' }}
                            <input type="file" class="sr-only" wire:model="newFile" accept=".{{ str_replace(',', ',.', $cat::FILE_MIMES) }}">
                        </label>
                        @if ($newFile || ($filePath && ! $removeFile))
                            <x-admin.btn size="sm" variant="danger" wire:click="removeStoredFile">Убрать файл</x-admin.btn>
                        @elseif ($filePath && $removeFile)
                            <x-admin.btn size="sm" wire:click="keepStoredFile">Оставить файл</x-admin.btn>
                        @endif
                        <span wire:loading wire:target="newFile" class="text-xs text-steel">Загрузка…</span>
                    </div>
                    <p class="mt-1 text-xs text-steel">{{ $fileHint }}</p>
                    @error('newFile') <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-4">
                    <x-admin.field label="Формат" for="d-format" :error="$errors->first('form.format')" hint="Заполняется по загруженному файлу: PDF, ZIP…">
                        <x-admin.input id="d-format" wire:model="form.format" :invalid="$errors->has('form.format')" />
                    </x-admin.field>
                    <x-admin.trans-input label="Размер" model="form.size_label" hint="Заполняется по загруженному файлу: «3,1 МБ» / «3,1 MB» / «3.1 MB»." />
                    <x-admin.toggle wire:model="form.is_placeholder" label="Заглушка"
                                    hint="Запись-образец без настоящего файла или с неподтверждёнными данными. Снимите отметку, когда загружен реальный файл." />
                </div>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.documents.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
