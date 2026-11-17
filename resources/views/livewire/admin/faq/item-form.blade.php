<x-admin.page :title="$title" :back="route('admin.faq.index')">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Вопрос и ответ" hint="Показывается в базе знаний. Не указывайте сроки гарантии и характеристики, которые не подтверждены.">
            <div class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-[1fr_10rem]">
                    <x-admin.field label="Тема" for="q-topic" required :error="$errors->first('form.faq_topic_id')">
                        <x-admin.select id="q-topic" wire:model="form.faq_topic_id" placeholder="Выберите тему" :options="$topics" :invalid="$errors->has('form.faq_topic_id')" />
                    </x-admin.field>
                    <x-admin.field label="Порядок" for="q-sort" required :error="$errors->first('form.sort')">
                        <x-admin.input type="number" min="0" id="q-sort" wire:model="form.sort" :invalid="$errors->has('form.sort')" />
                    </x-admin.field>
                </div>
                <x-admin.trans-input label="Вопрос" model="form.question" required />
                <x-admin.trans-textarea label="Ответ" model="form.answer" rows="5" required />
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.faq.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
