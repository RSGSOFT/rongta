<x-admin.page :title="$title" :back="route('admin.chatbot.articles.index')"
              subtitle="Бот отвечает посетителям по тексту статьи. Пишите только проверенные факты.">
    @if ($article?->source === 'pdf')
        <p class="mb-4 rounded-m border border-warn/30 bg-warn-soft px-3 py-2.5 text-warn">
            Черновик составлен Claude по руководству «{{ $article->source_ref }}». Сверьте его с документом на всех трёх языках, прежде чем публиковать.
        </p>
    @elseif ($question)
        <p class="mb-4 rounded-m border border-line bg-label px-3 py-2.5">
            Статья создана из вопроса посетителя, на который бот не ответил:
            <b>«{{ $question->content }}»</b>
            <a class="ml-1 font-semibold text-accent-ink hover:underline" href="{{ route('admin.chatbot.conversations.show', $question->chat_conversation_id) }}">Открыть диалог</a>
        </p>
    @endif

    <form wire:submit="save" novalidate>
        <x-admin.card title="Статья" hint="Цены, суммы и сроки гарантии в статьях не указываются.">
            <div class="grid gap-5">
                <x-admin.trans-input label="Заголовок" model="form.title" required
                                     hint="Коротко, как вопрос посетителя: «Как заменить рулон этикеток»." />
                <x-admin.trans-textarea label="Текст" model="form.body" rows="9"
                                        hint="Шаги пишите с новой строки, начиная с «- ». Для публикации нужны все три языка." />

                <div class="grid gap-4 md:grid-cols-2">
                    <x-admin.field label="Модель" for="ka-model" :error="$errors->first('form.scale_model_id')"
                                   hint="Если статья относится к одной модели.">
                        <x-admin.select id="ka-model" wire:model="form.scale_model_id" placeholder="Не привязана к модели" :options="$models" />
                    </x-admin.field>
                    <x-admin.field label="Семейство" for="ka-family" :error="$errors->first('form.family_id')"
                                   hint="Если выбрана модель, семейство берётся из неё.">
                        <x-admin.select id="ka-family" wire:model="form.family_id" placeholder="Все семейства" :options="$families" />
                    </x-admin.field>
                </div>

                <x-admin.field label="Метки" for="ka-tags" :error="$errors->first('form.tags')"
                               hint="Через запятую: слова, по которым бот найдёт статью. Например: рулон, этикетки, принтер.">
                    <x-admin.input id="ka-tags" wire:model="form.tags" :invalid="$errors->has('form.tags')" />
                </x-admin.field>

                <x-admin.toggle wire:model="form.is_published" label="Опубликована"
                                hint="Бот отвечает только по опубликованным статьям." />
                @error('form.is_published')
                    <p class="text-xs font-medium text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.chatbot.articles.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
