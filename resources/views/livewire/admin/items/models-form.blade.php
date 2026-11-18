@php
    $cat = \App\Livewire\Admin\Items\Catalog::class;
    $imageHint = 'JPG, PNG или WebP, до '.intdiv($cat::IMAGE_MAX_KB, 1024).' МБ.';
@endphp
<x-admin.page :title="$title" :back="route('admin.models.index')">
    @if ($model)
        <x-slot:actions>
            <x-admin.btn :href="route('admin.models.specs', $model)">Характеристики ({{ $model->specs_count }})</x-admin.btn>
            @if (Route::has('admin.documents.index'))
                <x-admin.btn :href="route('admin.documents.index', ['model' => $model->id])">Документы ({{ $model->documents_count }})</x-admin.btn>
            @endif
            @if ($model->is_published && Route::has('model'))
                <x-admin.btn :href="route('model', ['slug' => $model->slug])" target="_blank" rel="noopener">На сайте ↗</x-admin.btn>
            @endif
        </x-slot:actions>
    @endif

    <form wire:submit="save" novalidate>
        <x-admin.card title="Основное">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="Название модели" for="m-name" required :error="$errors->first('form.name')" hint="Одинаково на всех языках, например RLS1000/RLS1100A.">
                    <x-admin.input id="m-name" wire:model.blur="form.name" :invalid="$errors->has('form.name')" />
                </x-admin.field>

                @if ($slugLocked)
                    <x-admin.field label="Адрес (slug)" for="m-slug"
                                   hint="Модель опубликована: адрес страницы не меняется, чтобы не сломать ссылки и результаты поиска. Чтобы изменить, сначала снимите модель с публикации.">
                        <x-admin.input id="m-slug" :value="$form['slug']" readonly class="font-mono" />
                    </x-admin.field>
                @else
                    <x-admin.field label="Адрес (slug)" for="m-slug" required :error="$errors->first('form.slug')"
                                   hint="Заполняется из названия. Строчная латиница, цифры и дефис; адрес страницы /model/…. После публикации не меняется.">
                        <div class="flex gap-2">
                            <x-admin.input id="m-slug" wire:model.blur="form.slug" class="font-mono" :invalid="$errors->has('form.slug')" />
                            <x-admin.btn wire:click="slugFromName">Из названия</x-admin.btn>
                        </div>
                    </x-admin.field>
                @endif

                <x-admin.field label="Семейство" for="m-family" required :error="$errors->first('form.family_id')">
                    <x-admin.select id="m-family" wire:model="form.family_id" placeholder="Выберите семейство" :options="$families" :invalid="$errors->has('form.family_id')" />
                </x-admin.field>

                <div class="grid grid-cols-2 gap-4">
                    <x-admin.field label="Порядок в каталоге" for="m-sort" required :error="$errors->first('form.sort')">
                        <x-admin.input type="number" min="0" id="m-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                    </x-admin.field>
                    <div class="flex flex-col justify-end gap-2 pb-1">
                        <x-admin.toggle wire:model="form.is_published" label="Опубликована" />
                        <x-admin.toggle wire:model="form.is_aurora" label="Линейка AURORA" />
                    </div>
                </div>

                <x-admin.field label="Страница производителя" for="m-source" class="md:col-span-2" :error="$errors->first('form.source_url')"
                               hint="Страница этой модели на rongtatech.com: источник характеристик.">
                    <x-admin.input type="url" id="m-source" wire:model="form.source_url" placeholder="https://www.rongtatech.com/…" :invalid="$errors->has('form.source_url')" />
                </x-admin.field>
            </div>
        </x-admin.card>

        <x-admin.card title="Тексты" hint="Русский обязателен. Пустые UZ и EN отмечаются жёлтым: на сайте вместо них будет показан русский текст.">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Тип весов" model="form.type" required hint="Короткая строка под названием, например «Весы с печатью этикеток»." />
                <x-admin.trans-input label="Заголовок страницы модели" model="form.headline" required hint="Главная выгода для покупателя." />
                <x-admin.trans-textarea label="Вступление" model="form.intro" rows="3" required class="md:col-span-2" />
            </div>
        </x-admin.card>

        <x-admin.card title="Карточка в каталоге" hint="Три факта на карточке: максимальный вес берётся из характеристик, печать и подключение задаются здесь.">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="Печать" for="m-label" required :error="$errors->first('form.label_print')">
                    <x-admin.select id="m-label" wire:model="form.label_print" :options="$cat::LABEL_PRINT" />
                </x-admin.field>

                <fieldset class="md:row-span-2">
                    <legend class="mb-1 block text-xs font-semibold">Подключение</legend>
                    <div class="grid grid-cols-[auto_1fr] items-center gap-x-3 gap-y-1.5">
                        @foreach ($cat::CONN as $key => $connLabel)
                            <label for="m-conn-{{ $key }}">{{ $connLabel }}</label>
                            <x-admin.select id="m-conn-{{ $key }}" wire:model="form.conn.{{ $key }}" :options="$cat::CONN_STATES" />
                        @endforeach
                    </div>
                    @error('form.conn.*') <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                </fieldset>

                <div>
                    <div class="grid grid-cols-2 gap-4">
                        <x-admin.field label="Тип рисунка" for="m-kind" required :error="$errors->first('form.kind')">
                            <x-admin.select id="m-kind" wire:model="form.kind" placeholder="Выберите" :options="$cat::KINDS" :invalid="$errors->has('form.kind')" />
                        </x-admin.field>
                        <x-admin.field label="Голова весов" for="m-head" :error="$errors->first('form.head')">
                            <x-admin.select id="m-head" wire:model="form.head" :options="$cat::HEADS" />
                        </x-admin.field>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2">
                        <x-admin.toggle wire:model="form.cam" label="Камера" />
                        <x-admin.toggle wire:model="form.receipt" label="Чек" />
                        <x-admin.toggle wire:model="form.dual" label="Два экрана" />
                    </div>
                    <p class="mt-2 text-xs text-steel">Рисунок показывается на сайте, пока не загружено фото модели.</p>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="SEO: поиск Google и Яндекс" hint="Заголовок и описание страницы в результатах поиска. Пустое поле: берутся название модели и вступление. Без переводов UZ и EN на тех страницах тоже будут название и вступление.">
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <x-admin.btn wire:click="generateSeo" wire:loading.attr="disabled" wire:target="generateSeo" :disabled="! $seoAvailable">
                    <span wire:loading.remove wire:target="generateSeo">Сгенерировать SEO</span>
                    <span wire:loading wire:target="generateSeo">Claude пишет…</span>
                </x-admin.btn>
                <span class="text-xs text-steel">
                    @if ($seoAvailable)
                        Claude пишет тексты на трёх языках только из фактов этой формы и характеристик модели. Поля заполнятся, сохраните их сами.
                    @else
                        Ключ Claude API не задан (ANTHROPIC_API_KEY в .env): тексты можно написать вручную.
                    @endif
                </span>
            </div>
            @if ($seoProblem)
                <p class="mb-3 text-sm font-medium text-danger" role="alert">{{ $seoProblem }}</p>
            @elseif ($seoStatus)
                <p class="mb-3 text-sm font-medium text-ok" role="status">{{ $seoStatus }}</p>
            @endif
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.trans-input label="Заголовок для поиска" model="form.seo_title"
                                     hint="До {{ \App\Services\Catalog\SeoGenerator::TITLE_MAX }} знаков, начинается с названия модели. «· Rongta Uzbekistan» добавляется сам." />
                <x-admin.trans-textarea label="Описание для поиска" model="form.seo_description" rows="3"
                                        hint="120–{{ \App\Services\Catalog\SeoGenerator::DESCRIPTION_MAX }} знаков: что делают весы и где их купить в Узбекистане." />
            </div>
        </x-admin.card>

        <x-admin.card title="Фото и галерея" hint="Файлы сохраняются при нажатии «Сохранить».">
            <div class="grid gap-5 md:grid-cols-[14rem_1fr]">
                <div>
                    <div class="mb-1 text-xs font-semibold">Главное фото</div>
                    <div class="flex aspect-square w-full max-w-56 items-center justify-center overflow-hidden rounded-m border border-line bg-paper">
                        @if ($newImage)
                            <img src="{{ $newImage->temporaryUrl() }}" alt="Новое фото (ещё не сохранено)" class="h-full w-full object-contain">
                        @elseif ($image)
                            <img src="{{ asset('storage/'.$image) }}" alt="Главное фото модели" class="h-full w-full object-contain">
                        @else
                            <span class="px-3 text-center text-xs text-steel">Фото нет: на сайте используется рисунок</span>
                        @endif
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <label class="inline-flex min-h-8 cursor-pointer items-center rounded-m border border-line bg-label px-2.5 text-xs font-semibold hover:bg-paper focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-ink">
                            {{ $image || $newImage ? 'Заменить' : 'Загрузить' }}
                            <input type="file" class="sr-only" wire:model="newImage" accept=".jpg,.jpeg,.png,.webp">
                        </label>
                        @if ($image || $newImage)
                            <x-admin.btn size="sm" variant="danger" wire:click="removeImage">Убрать</x-admin.btn>
                        @endif
                        <span wire:loading wire:target="newImage" class="text-xs text-steel">Загрузка…</span>
                    </div>
                    <p class="mt-1 text-xs text-steel">{{ $imageHint }}</p>
                    @error('newImage') <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="min-w-0">
                    <div class="mb-1 text-xs font-semibold">Галерея ({{ count($gallery) + count($newGallery) }} из {{ $cat::GALLERY_MAX }})</div>
                    <ul class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5">
                        @foreach ($gallery as $i => $path)
                            <li wire:key="gal-{{ md5($path) }}" class="rounded-m border border-line bg-paper p-1">
                                <img src="{{ asset('storage/'.$path) }}" alt="Изображение {{ $i + 1 }}" class="aspect-square w-full rounded-s object-cover" loading="lazy">
                                <div class="mt-1 flex justify-between gap-1">
                                    <span class="inline-flex gap-1">
                                        <button type="button" wire:click="moveGalleryImage({{ $i }}, -1)" @disabled($i === 0) class="h-6 w-6 rounded-s border border-line bg-label text-xs disabled:opacity-30" aria-label="Изображение {{ $i + 1 }}: сдвинуть раньше">←</button>
                                        <button type="button" wire:click="moveGalleryImage({{ $i }}, 1)" @disabled($i === count($gallery) - 1) class="h-6 w-6 rounded-s border border-line bg-label text-xs disabled:opacity-30" aria-label="Изображение {{ $i + 1 }}: сдвинуть позже">→</button>
                                    </span>
                                    <button type="button" wire:click="removeGalleryImage({{ $i }})" class="h-6 w-6 rounded-s border border-danger/40 bg-label text-xs text-danger" aria-label="Изображение {{ $i + 1 }}: убрать">×</button>
                                </div>
                            </li>
                        @endforeach
                        @foreach ($newGallery as $i => $file)
                            <li wire:key="gal-new-{{ $i }}-{{ $file->getFilename() }}" class="rounded-m border border-dashed border-accent-ink bg-accent-soft p-1">
                                <img src="{{ $file->temporaryUrl() }}" alt="Новое изображение {{ $i + 1 }} (ещё не сохранено)" class="aspect-square w-full rounded-s object-cover">
                                <div class="mt-1 flex items-center justify-between gap-1">
                                    <span class="text-[10px] font-semibold text-accent-ink">новое</span>
                                    <button type="button" wire:click="removeNewGalleryImage({{ $i }})" class="h-6 w-6 rounded-s border border-danger/40 bg-label text-xs text-danger" aria-label="Новое изображение {{ $i + 1 }}: убрать">×</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @if (count($gallery) + count($newGallery) === 0)
                        <p class="rounded-m border border-dashed border-line px-3 py-4 text-center text-xs text-steel">В галерее пока нет изображений.</p>
                    @endif
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if (count($gallery) + count($newGallery) < $cat::GALLERY_MAX)
                            <label class="inline-flex min-h-8 cursor-pointer items-center rounded-m border border-line bg-label px-2.5 text-xs font-semibold hover:bg-paper focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-ink">
                                Добавить изображения
                                <input type="file" class="sr-only" wire:model="newGallery" accept=".jpg,.jpeg,.png,.webp" multiple>
                            </label>
                        @endif
                        <span wire:loading wire:target="newGallery" class="text-xs text-steel">Загрузка…</span>
                        <span class="text-xs text-steel">{{ $imageHint }}</span>
                    </div>
                    @error('newGallery') <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                    @error('newGallery.*') <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-admin.card>

        @if ($altPhotos)
            <x-admin.card title="Описания фото (alt)" hint="Что видно на каждом фото. Их читают поисковики и программы чтения с экрана. Пустое поле: «Rongta {название}: {тип весов}». Новые фото появятся здесь после сохранения.">
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <x-admin.btn wire:click="generateAlts" wire:loading.attr="disabled" wire:target="generateAlts" :disabled="! $seoAvailable">
                        <span wire:loading.remove wire:target="generateAlts">Описать фото с ИИ</span>
                        <span wire:loading wire:target="generateAlts">Claude смотрит фото…</span>
                    </x-admin.btn>
                    <span class="text-xs text-steel">
                        @if ($seoAvailable)
                            Claude описывает фото без описания, до {{ \App\Services\Catalog\AltTextGenerator::MAX_PHOTOS }} за раз. Поля заполнятся, сохраните их сами.
                        @else
                            Ключ Claude API не задан (ANTHROPIC_API_KEY в .env): описания можно написать вручную.
                        @endif
                    </span>
                </div>
                @if ($altProblem)
                    <p class="mb-3 text-sm font-medium text-danger" role="alert">{{ $altProblem }}</p>
                @elseif ($altStatus)
                    <p class="mb-3 text-sm font-medium text-ok" role="status">{{ $altStatus }}</p>
                @endif
                <ul class="space-y-4">
                    @foreach ($altPhotos as $i => $path)
                        <li wire:key="alt-{{ md5($path) }}" class="grid gap-3 sm:grid-cols-[6rem_1fr]">
                            <img src="{{ asset('storage/'.$path) }}" alt="" class="aspect-square w-24 rounded-s border border-line bg-paper object-contain" loading="lazy">
                            <x-admin.trans-input :label="$i === 0 ? 'Главное фото' : 'Фото '.($i + 1)" :model="'alts.'.md5($path)" />
                        </li>
                    @endforeach
                </ul>
            </x-admin.card>
        @endif

        <x-admin.card title="Преимущества" hint="Блоки с иконкой на странице модели. Порядок здесь = порядок на сайте.">
            <x-slot:tools>
                <x-admin.btn size="sm" wire:click="addFeature" :disabled="count($features) >= 12">Добавить блок</x-admin.btn>
            </x-slot:tools>

            @if ($features === [])
                <p class="rounded-m border border-dashed border-line px-3 py-5 text-center text-steel">Блоков пока нет.</p>
            @endif

            <ol class="space-y-4">
                @foreach ($features as $i => $feature)
                    <li wire:key="feature-{{ $i }}-{{ $feature['id'] ?? 'new' }}" class="rounded-m border border-line p-3 sm:p-4">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <span class="font-semibold">Блок {{ $i + 1 }}</span>
                            <span class="inline-flex flex-wrap items-center gap-1.5">
                                <label class="sr-only" for="feat-icon-{{ $i }}">Значок блока {{ $i + 1 }}</label>
                                <x-admin.select id="feat-icon-{{ $i }}" wire:model="features.{{ $i }}.icon" class="!min-h-8 !w-36 !py-1 !text-xs" placeholder="Без значка"
                                                :options="array_combine($cat::FEATURE_ICONS, $cat::FEATURE_ICONS)" />
                                <x-admin.btn size="sm" wire:click="moveFeature({{ $i }}, -1)" :disabled="$i === 0" aria-label="Блок {{ $i + 1 }}: выше">↑</x-admin.btn>
                                <x-admin.btn size="sm" wire:click="moveFeature({{ $i }}, 1)" :disabled="$i === count($features) - 1" aria-label="Блок {{ $i + 1 }}: ниже">↓</x-admin.btn>
                                <x-admin.btn size="sm" variant="danger" wire:click="removeFeature({{ $i }})" confirm="Убрать блок {{ $i + 1 }}? Изменение вступит в силу после сохранения.">Убрать</x-admin.btn>
                            </span>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin.trans-input label="Заголовок" model="features.{{ $i }}.title" required />
                            <x-admin.trans-textarea label="Текст" model="features.{{ $i }}.text" rows="2" required />
                        </div>
                        @error("features.{$i}.icon") <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                    </li>
                @endforeach
            </ol>
            @error('features') <p class="mt-2 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror

            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.models.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>

    @if ($model)
        <livewire:admin.items.models.drawing :model-id="$model->id" :key="'drawing-'.$model->id" />
    @endif
</x-admin.page>
