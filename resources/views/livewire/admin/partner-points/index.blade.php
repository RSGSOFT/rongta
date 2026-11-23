<x-admin.page title="Страница «Партнёрам»" subtitle="Преимущества и условия партнёрства на сайте. Блок без опубликованных пунктов не показывается. Форма заявки на странице есть всегда.">
    @foreach (\App\Livewire\Admin\PartnerPoints\Index::KINDS as $kind => [$heading, $addLabel, $emptyText])
        @php $rows = $points->get($kind, collect()); @endphp
        <x-admin.table :count="$rows->count()" :empty="$emptyText" wire:key="kind-{{ $kind }}">
            <x-slot:toolbar>
                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold">{{ $heading }}</h2>
                    <p class="text-xs text-steel">Показываются на сайте в этом порядке.</p>
                </div>
                <x-admin.btn size="sm" variant="primary" :href="route('admin.partner-points.create', ['kind' => $kind])">{{ $addLabel }}</x-admin.btn>
            </x-slot:toolbar>
            <x-slot:head>
                <x-admin.th>№</x-admin.th>
                <x-admin.th>Заголовок</x-admin.th>
                <x-admin.th>Показ</x-admin.th>
                <x-admin.th>Переводы</x-admin.th>
                <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
            </x-slot:head>

            @foreach ($rows as $point)
                @php
                    $hasBody = filled($point->body['ru'] ?? null);
                    $missing = collect(['ru', 'uz', 'en'])
                        ->filter(fn ($l) => blank($point->title[$l] ?? null) || ($hasBody && blank($point->body[$l] ?? null)))
                        ->map(fn ($l) => strtoupper($l));
                @endphp
                <tr wire:key="point-{{ $point->id }}">
                    <td class="w-12 tabular-nums text-steel">{{ $point->sort }}</td>
                    <td><a href="{{ route('admin.partner-points.edit', $point) }}" class="font-medium hover:text-accent-ink">{{ $point->tr('title', 'ru') }}</a></td>
                    <td>
                        @if ($point->is_published)
                            <x-admin.badge tone="ok">На сайте</x-admin.badge>
                        @else
                            <x-admin.badge tone="warn">Скрыт</x-admin.badge>
                        @endif
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
                            <x-admin.btn size="sm" :href="route('admin.partner-points.edit', $point)">Изменить</x-admin.btn>
                            <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $point->id }})"
                                         confirm="Удалить этот пункт? Это действие нельзя отменить.">Удалить</x-admin.btn>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>
    @endforeach
</x-admin.page>
