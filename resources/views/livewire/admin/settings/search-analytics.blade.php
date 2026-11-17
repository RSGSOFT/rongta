<x-admin.page title="Настройки сайта" subtitle="Подтверждение сайта в Google и Яндексе, счётчики посещений и быстрое оповещение поисковиков.">
    @include('livewire.admin.settings.tabs', ['current' => 'admin.settings.search'])

    <form wire:submit="save" novalidate>
        <x-admin.card title="Подтверждение сайта" hint="Чтобы управлять сайтом в Google Search Console и Яндекс Вебмастере. Вставьте код или весь мета-тег целиком: код будет извлечён. Пустое поле: тег не выводится.">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="Google Search Console" for="se-google" :error="$errors->first('form.google_verification')"
                               hint="search.google.com/search-console → добавить ресурс → способ «Тег HTML».">
                    <x-admin.input id="se-google" wire:model="form.google_verification" autocomplete="off" placeholder='<meta name="google-site-verification" content="…">' :invalid="$errors->has('form.google_verification')" />
                </x-admin.field>
                <x-admin.field label="Яндекс Вебмастер" for="se-yandex" :error="$errors->first('form.yandex_verification')"
                               hint="webmaster.yandex.ru → добавить сайт → способ «Мета-тег».">
                    <x-admin.input id="se-yandex" wire:model="form.yandex_verification" autocomplete="off" placeholder='<meta name="yandex-verification" content="…">' :invalid="$errors->has('form.yandex_verification')" />
                </x-admin.field>
            </div>
            <p class="mt-3 text-xs text-steel">После подтверждения добавьте в обоих сервисах карту сайта: <span class="font-mono">{{ $sitemapUrl }}</span></p>
        </x-admin.card>

        <x-admin.card title="Аналитика" hint="Счётчики посещений. Они ставят cookie посетителям: перед включением проверьте с юристом, нужно ли уведомление о персональных данных.">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="Google Analytics 4" for="se-ga4" :error="$errors->first('form.ga4_id')" hint="Идентификатор потока, например G-XXXXXXXXXX.">
                    <x-admin.input id="se-ga4" wire:model="form.ga4_id" autocomplete="off" placeholder="G-XXXXXXXXXX" :invalid="$errors->has('form.ga4_id')" />
                </x-admin.field>
                <x-admin.field label="Яндекс Метрика" for="se-metrica" :error="$errors->first('form.metrica_id')" hint="Номер счётчика, например 98765432. Вебвизор не включается.">
                    <x-admin.input id="se-metrica" wire:model="form.metrica_id" inputmode="numeric" autocomplete="off" placeholder="98765432" :invalid="$errors->has('form.metrica_id')" />
                </x-admin.field>
            </div>
            @unless ($live)
                <p class="mt-3 text-xs font-medium text-warn" role="status">Это не рабочий сервер: счётчики здесь не выводятся, они заработают только на рабочем сайте.</p>
            @endunless
        </x-admin.card>

        <x-admin.card title="IndexNow" hint="Когда модель публикуется, меняется или снимается с публикации, сайт сразу сообщает об этом Яндексу, Bing и другим поисковикам IndexNow. Регистрация не нужна.">
            <x-admin.toggle wire:model="form.indexnow" label="Сообщать поисковикам об изменениях моделей" />
            @if ($keyUrl)
                <p class="mt-2 text-xs text-steel">Файл ключа: <span class="font-mono">{{ $keyUrl }}</span></p>
            @endif
            <x-slot:footer>
                <x-admin.btn type="submit" variant="primary">Сохранить</x-admin.btn>
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
