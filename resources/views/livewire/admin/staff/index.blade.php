<x-admin.page title="Сотрудники" subtitle="Учётные записи панели управления. Запись нельзя удалить, только отключить: так сохраняется журнал действий.">
    <x-slot:actions>
        <x-admin.btn variant="primary" :href="route('admin.staff.create')">Добавить сотрудника</x-admin.btn>
    </x-slot:actions>

    <x-admin.table :count="$rows->count()" empty="Сотрудников не найдено.">
        <x-slot:toolbar>
            <x-admin.search placeholder="Имя или эл. почта" />
        </x-slot:toolbar>
        <x-slot:head>
            <x-admin.th sort="name" :by="$sortBy ?: 'name'" :dir="$sortDir">Имя</x-admin.th>
            <x-admin.th sort="email" :by="$sortBy" :dir="$sortDir">Эл. почта</x-admin.th>
            <x-admin.th sort="role" :by="$sortBy" :dir="$sortDir">Роль</x-admin.th>
            <x-admin.th sort="is_active" :by="$sortBy" :dir="$sortDir">Доступ</x-admin.th>
            <x-admin.th align="right"><span class="sr-only">Действия</span></x-admin.th>
        </x-slot:head>

        @foreach ($rows as $user)
            @php $self = $user->is(auth()->user()); @endphp
            <tr wire:key="user-{{ $user->id }}">
                <td>
                    <a href="{{ route('admin.staff.edit', $user) }}" class="font-semibold hover:text-accent-ink">{{ $user->name }}</a>
                    @if ($self)
                        <x-admin.badge>это вы</x-admin.badge>
                    @endif
                </td>
                <td>{{ $user->email }}</td>
                <td>
                    @if ($user->isAdmin())
                        <x-admin.badge tone="accent">администратор</x-admin.badge>
                    @else
                        <x-admin.badge>редактор</x-admin.badge>
                    @endif
                </td>
                <td>
                    @if ($user->is_active)
                        <x-admin.badge tone="ok">включён</x-admin.badge>
                    @else
                        <x-admin.badge tone="danger">отключён</x-admin.badge>
                    @endif
                </td>
                <td class="text-right">
                    <div class="inline-flex gap-1.5">
                        <x-admin.btn size="sm" :href="route('admin.staff.edit', $user)">Изменить</x-admin.btn>
                        @unless ($self)
                            @if ($user->is_active)
                                <x-admin.btn size="sm" variant="danger" wire:click="toggleActive({{ $user->id }})"
                                             confirm="Отключить доступ для {{ $user->name }}? Сотрудник не сможет войти в панель.">Отключить</x-admin.btn>
                            @else
                                <x-admin.btn size="sm" wire:click="toggleActive({{ $user->id }})">Включить</x-admin.btn>
                            @endif
                        @endunless
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>{{ $rows->links('components.admin.pagination') }}</x-slot:footer>
    </x-admin.table>
</x-admin.page>
