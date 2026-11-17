<x-admin.page title="Обучение по руководству" :back="route('admin.chatbot.articles.index')" backLabel="Назад к базе ответов"
              subtitle="Загрузите руководство в PDF: Claude составит по нему черновики статей на трёх языках.">
    @unless ($available)
        <div role="alert" class="mb-4 rounded-m border border-warn/30 bg-warn-soft px-3 py-2.5 text-warn">
            <b>Ключ Claude API не задан.</b> Добавьте строку <code>ANTHROPIC_API_KEY=…</code> в файл <code>.env</code> на сервере, после этого загрузка заработает.
        </div>
    @endunless

    @if ($failure)
        <div role="alert" class="mb-4 rounded-m border border-danger/30 bg-danger-soft px-3 py-2.5 font-medium text-danger">{{ $failure }}</div>
    @endif

    @if ($drafts->isNotEmpty())
        <x-admin.card title="Черновиков создано: {{ $drafts->count() }}"
                      hint="Ни один черновик не опубликован. Откройте каждый, сверьте с руководством и опубликуйте.">
            <ul class="grid gap-2">
                @foreach ($drafts as $draft)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-m border border-line px-3 py-2">
                        <span class="min-w-0 font-medium">{{ $draft->tr('title', 'ru') }}</span>
                        <x-admin.btn size="sm" :href="route('admin.chatbot.articles.edit', $draft)">Проверить</x-admin.btn>
                    </li>
                @endforeach
            </ul>
            <x-slot:footer>
                <x-admin.btn :href="route('admin.chatbot.articles.index', ['status' => 'draft'])">Все черновики</x-admin.btn>
            </x-slot:footer>
        </x-admin.card>
    @endif

    <form wire:submit="draft" novalidate>
        <x-admin.card title="Руководство">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="Файл PDF" for="imp-pdf" required :error="$errors->first('pdf')"
                               hint="До {{ $maxMb }} МБ, с текстовым слоем (не только сканы). Больше нельзя: Claude принимает запрос не более 32 МБ, а файл при отправке увеличивается примерно на треть. Большое руководство разделите на части.">
                    <input type="file" id="imp-pdf" wire:model="pdf" accept="application/pdf,.pdf" class="adm-control" @disabled(! $available)>
                    <span wire:loading wire:target="pdf" class="mt-1 block text-xs text-steel">Файл загружается…</span>
                </x-admin.field>
                <x-admin.field label="Модель" for="imp-model" :error="$errors->first('modelId')"
                               hint="К какой модели относится руководство. Черновики будут привязаны к ней.">
                    <x-admin.select id="imp-model" wire:model="modelId" placeholder="Не привязывать к модели" :options="$models" />
                </x-admin.field>
            </div>
            <p class="mt-4 text-xs text-steel">
                Обработка занимает от одной до нескольких минут и расходует средства на счёте Claude API. Не закрывайте страницу, пока она идёт.
                Черновики никогда не публикуются сами.
            </p>
            <x-slot:footer>
                <x-admin.btn variant="primary" type="submit" wire:loading.attr="disabled" wire:target="draft, pdf" :disabled="! $available">
                    <span wire:loading.remove wire:target="draft">Составить черновики</span>
                    <span wire:loading wire:target="draft">Claude читает руководство…</span>
                </x-admin.btn>
                <x-admin.btn :href="route('admin.chatbot.articles.index')">Отмена</x-admin.btn>
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
