@php $opt = \App\Livewire\Admin\Partners\Options::class; @endphp
<x-admin.page :title="'Заявка: '.$request->company" :back="route('admin.partner-requests.index')" backLabel="Назад к заявкам">
    <div class="grid gap-5 lg:grid-cols-[1fr_20rem]">
        <x-admin.card title="Заявка № {{ $request->id }}">
            <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-[10rem_1fr]">
                <dt class="text-xs font-semibold text-steel">Получена</dt>
                <dd class="tabular-nums">{{ $request->created_at?->format('d.m.Y H:i') }}</dd>

                <dt class="text-xs font-semibold text-steel">Компания</dt>
                <dd class="break-words font-semibold">{{ $request->company }}</dd>

                <dt class="text-xs font-semibold text-steel">Контактное лицо</dt>
                <dd class="break-words">{{ $request->contact_name }}</dd>

                <dt class="text-xs font-semibold text-steel">Телефон</dt>
                <dd><a href="tel:{{ preg_replace('/[^\d+]/', '', $request->phone) }}" class="font-semibold tabular-nums text-accent-ink hover:underline">{{ $request->phone }}</a></dd>

                <dt class="text-xs font-semibold text-steel">Тип</dt>
                <dd>{{ $opt::KINDS[$request->type] ?? $request->type }}</dd>

                <dt class="text-xs font-semibold text-steel">Регион</dt>
                <dd>{{ $request->region?->tr('name', 'ru') ?: 'не указан' }}</dd>

                <dt class="text-xs font-semibold text-steel">Сообщение</dt>
                <dd class="whitespace-pre-line break-words">{{ $request->message ?: 'нет' }}</dd>
            </dl>
        </x-admin.card>

        <form wire:submit="save" novalidate>
            <x-admin.card title="Статус">
                <x-admin.field label="Статус заявки" for="pr-status" :error="$errors->first('status')">
                    <x-admin.select id="pr-status" wire:model="status" :options="$opt::REQUEST_STATUSES" :invalid="$errors->has('status')" />
                </x-admin.field>
                <p class="mt-3 text-xs text-steel">Сейчас:
                    <x-admin.badge :tone="$opt::REQUEST_TONES[$request->status] ?? 'neutral'">{{ $opt::REQUEST_STATUSES[$request->status] ?? $request->status }}</x-admin.badge>
                </p>
                <x-slot:footer>
                    <x-admin.form-actions label="Сохранить статус">
                        <x-admin.btn variant="danger" wire:click="delete" confirm="Удалить заявку от «{{ $request->company }}»? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </x-admin.form-actions>
                </x-slot:footer>
            </x-admin.card>
        </form>
    </div>
</x-admin.page>
