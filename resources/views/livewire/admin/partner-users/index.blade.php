@php $opt = \App\Livewire\Admin\Partners\Options::class; @endphp
<x-admin.page title="Доступы партнёров" subtitle="Логины для входа в кабинет партнёра на сайте. Пароли хранятся в зашифрованном виде и здесь не показываются.">
    <x-slot:actions>
        <x-admin.btn variant="primary" :href="route('admin.partner-users.create')">Добавить доступ</x-admin.btn>
    </x-slot:actions>

    <x-admin.table :count="$rows->count()" empty="Доступов не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Имя или логин" />
            <div class="w-full sm:w-44">
                <label class="sr-only" for="filter-type">Тип</label>
                <x-admin.select id="filter-type" wire:model.live="filterType" placeholder="Все типы" :options="$opt::KINDS" />
            </div>
            <div class="w-full sm:w-44">
                <label class="sr-only" for="filter-status">Статус</label>
                <x-admin.select id="filter-status" wire:model.live="filterStatus" placeholder="Статус: любой" :options="['active' => 'Включённые', 'off' => 'Выключенные']" />
            </div>
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="name" :by="$sortBy ?: 'name'" :dir="$sortDir">Имя</x-admin.th>
            <x-admin.th sort="login" :by="$sortBy" :dir="$sortDir">Логин</x-admin.th>
            <x-admin.th sort="type" :by="$sortBy" :dir="$sortDir">Тип</x-admin.th>
            <x-admin.th>Партнёр</x-admin.th>
            <x-admin.th>Доступ</x-admin.th>
            <x-admin.th sort="created_at" :by="$sortBy" :dir="$sortDir">Создан</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $user)
            <tr wire:key="partner-user-{{ $user->id }}">
                <td><a href="{{ route('admin.partner-users.edit', $user) }}" class="font-semibold hover:text-accent-ink">{{ $user->name }}</a></td>
                <td><code class="text-xs">{{ $user->login }}</code></td>
                <td class="whitespace-nowrap">{{ $opt::KINDS[$user->type] ?? $user->type }}</td>
                <td>
                    @if ($user->partner)
                        <a href="{{ route('admin.partners.edit', $user->partner) }}" class="hover:text-accent-ink hover:underline">{{ $user->partner->tr('name', 'ru') }}</a>
                    @else
                        <span class="text-steel">не привязан</span>
                    @endif
                </td>
                <td>
                    <button type="button" wire:click="toggleActive({{ $user->id }})" class="rounded-full"
                            title="{{ $user->is_active ? 'Выключить доступ' : 'Включить доступ' }}"
                            aria-label="{{ $user->login }}: {{ $user->is_active ? 'доступ включён, выключить' : 'доступ выключен, включить' }}">
                        <x-admin.badge :tone="$user->is_active ? 'ok' : 'neutral'">{{ $user->is_active ? 'включён' : 'выключен' }}</x-admin.badge>
                    </button>
                </td>
                <td class="whitespace-nowrap tabular-nums text-steel">{{ $user->created_at?->format('d.m.Y') }}</td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" :href="route('admin.partner-users.edit', $user)">Изменить</x-admin.btn>
                        <x-admin.btn size="sm" variant="danger" wire:click="delete({{ $user->id }})"
                                     confirm="Удалить доступ «{{ $user->login }}»? Партнёр больше не сможет войти в кабинет.">Удалить</x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
