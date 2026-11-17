{{-- Main picture as a line drawing: generate with Claude from the saved photos, or upload an SVG. --}}
@php
    $max = \App\Services\Catalog\LineArtGenerator::MAX_PHOTOS;
    $box = 'flex aspect-[8/7] items-center justify-center overflow-hidden rounded-m border border-line';
@endphp
<div class="mt-5">
    <x-admin.card title="Главное изображение: рисунок (ИИ)"
                  hint="Плоский рисунок модели в стиле сайта по её фотографиям. Если рисунок есть, он показывается на сайте вместо фото: в каталоге, сравнении и на странице модели; фото остаются в галерее.">
        <div class="grid gap-5 md:grid-cols-[14rem_1fr]">
            <div>
                <div class="mb-1 text-xs font-semibold">Сейчас на сайте</div>
                @if ($current)
                    <div class="grid grid-cols-2 gap-2">
                        <div class="{{ $box }} bg-label"><img src="{{ $current['light'] }}" alt="Рисунок {{ $model->name }} на светлом фоне" class="h-full w-full object-contain"></div>
                        <div class="{{ $box }}" style="background:#0E1828"><img src="{{ $current['night'] }}" alt="Рисунок {{ $model->name }} на тёмном фоне" class="h-full w-full object-contain"></div>
                    </div>
                    <div class="mt-2">
                        <x-admin.btn size="sm" variant="danger" wire:click="removeDrawing" confirm="Убрать рисунок модели {{ $model->name }}? На сайте снова будет фото или схема.">Убрать рисунок</x-admin.btn>
                    </div>
                @else
                    <div class="{{ $box }} bg-paper"><span class="px-3 text-center text-xs text-steel">Рисунка нет: показывается фото или схема</span></div>
                @endif
            </div>

            <div class="min-w-0">
                @if ($photos)
                    <fieldset>
                        <legend class="mb-1 text-xs font-semibold">Фото для рисунка (от 1 до {{ $max }}; сохранённые фото модели)</legend>
                        <ul class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                            @foreach ($photos as $i => $path)
                                <li wire:key="lineart-photo-{{ md5($path) }}">
                                    <label class="block cursor-pointer rounded-m border border-line bg-paper p-1 has-[:checked]:border-accent has-[:checked]:bg-accent-soft">
                                        <img src="{{ asset('storage/'.$path) }}" alt="Фото {{ $i + 1 }}" class="aspect-square w-full rounded-s object-cover" loading="lazy">
                                        <span class="mt-1 flex items-center gap-1 text-xs">
                                            <input type="checkbox" wire:model="selected" value="{{ $path }}" class="accent-accent"> Фото {{ $i + 1 }}
                                        </span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </fieldset>
                @else
                    <p class="text-steel">У модели ещё нет сохранённых фото. Загрузите фото в блоке «Фото и галерея» выше и нажмите «Сохранить»: рисунок делается только по фото.</p>
                @endif

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-admin.btn variant="primary" wire:click="generate" wire:loading.attr="disabled" wire:target="generate" :disabled="! $photos || ! $available">
                        {{ $draft ? 'Сгенерировать заново' : 'Сгенерировать изображение (ИИ)' }}
                    </x-admin.btn>
                    <label class="inline-flex min-h-10 cursor-pointer items-center rounded-m border border-line bg-label px-4 text-sm font-semibold hover:bg-paper focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-ink">
                        Загрузить SVG
                        <input type="file" class="sr-only" wire:model="svgFile" accept=".svg,image/svg+xml">
                    </label>
                    <span wire:loading wire:target="generate" class="text-xs text-steel" role="status">Claude рисует по фото, это займёт 1–3 минуты…</span>
                    <span wire:loading wire:target="svgFile" class="text-xs text-steel">Загрузка…</span>
                </div>
                @unless ($available)
                    <p class="mt-1 text-xs text-warn">Ключ Claude API не задан (ANTHROPIC_API_KEY в .env): генерация недоступна. Можно загрузить готовый SVG.</p>
                @endunless
                @error('svgFile') <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                @if ($status)
                    <p class="mt-2 text-sm font-medium text-ok" role="status">{{ $status }}</p>
                @endif
                @if ($problem)
                    <p class="mt-2 text-sm font-medium text-danger" role="alert">{{ $problem }}</p>
                @endif
            </div>
        </div>

        @if ($preview)
            <div class="mt-5 border-t border-paper-2 pt-5" wire:key="lineart-draft">
                <div class="mb-2 text-xs font-semibold">Черновик ({{ $draft['source'] === 'file' ? 'из файла' : 'нарисовал Claude' }}): проверьте по фото</div>
                <div class="grid gap-3 sm:grid-cols-[1fr_1fr_6rem]">
                    <div class="{{ $box }} bg-label"><img src="{{ $preview['light'] }}" alt="Черновик на светлом фоне" class="h-full w-full object-contain"></div>
                    <div class="{{ $box }}" style="background:#0E1828"><img src="{{ $preview['night'] }}" alt="Черновик на тёмном фоне" class="h-full w-full object-contain"></div>
                    <div>
                        <div class="{{ $box }} bg-label" style="width:90px"><img src="{{ $preview['light'] }}" alt="Черновик в размере карточки каталога" class="h-full w-full object-contain"></div>
                        <p class="mt-1 text-xs text-steel">Как в карточке каталога</p>
                    </div>
                </div>

                @if ($preview['crops'])
                    <div class="mt-3 grid grid-cols-2 gap-3 sm:max-w-md">
                        @foreach ($preview['crops'] as $crop)
                            <figure>
                                <div class="{{ $box }} bg-label"><img src="{{ $crop['src'] }}" alt="Крупный план: {{ $crop['caption'] }}" class="h-full w-full object-contain"></div>
                                <figcaption class="mt-1 text-xs text-steel">{{ $crop['caption'] }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif

                @if ($draft['parts'])
                    <div class="mt-3">
                        <div class="text-xs font-semibold">Детали, которые Claude увидел на фото</div>
                        <ul class="mt-1 list-disc pl-5 text-sm">
                            @foreach ($draft['parts'] as $part)
                                <li>{{ $part }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($draft['note'] !== '')
                    <p class="mt-2 text-sm text-steel">{{ $draft['note'] }}</p>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    <x-admin.btn variant="primary" wire:click="useDraft">Использовать</x-admin.btn>
                    <x-admin.btn wire:click="discard">Отмена</x-admin.btn>
                </div>
            </div>
        @endif

        <details class="mt-5 border-t border-paper-2 pt-4" x-data="{ copied: false }">
            <summary class="cursor-pointer text-sm font-semibold">Промпт для других ИИ</summary>
            <p class="mt-2 text-xs text-steel">Скопируйте и вставьте в другой ИИ (ChatGPT, Gemini и т.д.) вместе с фото модели. Полученный файл .svg загрузите кнопкой «Загрузить SVG». Сам промпт меняется в «Настройки сайта → Промпт рисунков».</p>
            <textarea x-ref="hint" readonly rows="8" class="mt-2 w-full rounded-m border border-line bg-paper p-2 font-mono text-xs">{{ $hint }}</textarea>
            <div class="mt-2 flex items-center gap-2">
                <x-admin.btn size="sm" x-on:click="navigator.clipboard.writeText($refs.hint.value).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">Копировать</x-admin.btn>
                <span x-show="copied" x-cloak class="text-xs text-ok" role="status">Скопировано</span>
            </div>
        </details>
    </x-admin.card>
</div>
