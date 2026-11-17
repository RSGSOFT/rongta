<x-admin.page title="Подбор весов" subtitle="Варианты ответа в блоке «Какие весы вам нужны?» на главной странице.">
    <x-slot:actions>
        <x-admin.btn variant="primary" :href="route('admin.chooser.create')">Добавить вариант</x-admin.btn>
    </x-slot:actions>

    <x-admin.table :count="$options->count()" empty="Вариантов пока нет: блок подбора на главной не показывается.">
        <x-slot:head>
            <x-admin.th>№</x-admin.th>
            <x-admin.th>Вариант</x-admin.th>
            <x-admin.th>Семейство</x-admin.th>
            <x-admin.th>Модели</x-admin.th>
            <x-admin.th>Переводы</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($options as $option)
            @php
                $string = $labels['ch.'.$option->key] ?? null;
                $label = filled($string?->ru) ? $string->ru : $option->key;
                $missing = collect(['ru', 'uz', 'en'])->filter(fn ($l) => blank($option->why[$l] ?? null) || blank($string?->{$l}))->map(fn ($l) => strtoupper($l));
            @endphp
            <tr wire:key="option-{{ $option->id }}">
                <td class="w-12 tabular-nums text-steel">{{ $option->sort }}</td>
                <td>
                    <a href="{{ route('admin.chooser.edit', $option) }}" class="font-semibold hover:text-accent-ink">{{ $label }}</a>
                    <code class="text-xs text-steel">{{ $option->key }}</code>
                    <div class="line-clamp-2 max-w-md text-xs text-steel">{{ $option->tr('why', 'ru') }}</div>
                </td>
                <td>
                    @if ($option->family)
                        {{ $option->family->tr('name', 'ru') }}
                    @else
                        <x-admin.badge tone="warn">не выбрано</x-admin.badge>
                    @endif
                </td>
                <td class="max-w-xs text-xs">
                    @forelse ($option->scaleModels as $model)
                        <span @class(['mr-1 inline-block whitespace-nowrap', 'text-steel line-through' => ! $model->is_published]) @if (! $model->is_published) title="Скрыта с сайта" @endif>{{ $model->name }}@if (! $loop->last),@endif</span>
                    @empty
                        <span class="text-steel">нет</span>
                    @endforelse
                </td>
                <td>
                    @if ($missing->isEmpty())
                        <x-admin.badge tone="ok">RU · UZ · EN</x-admin.badge>
                    @else
                        <x-admin.badge tone="warn">нет: {{ $missing->implode(', ') }}</x-admin.badge>
                    @endif
                </td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" :href="route('admin.chooser.edit', $option)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $option->id }})"
                                     confirm="Удалить вариант «{{ $label }}»? Это действие нельзя отменить.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>
</x-admin.page>
