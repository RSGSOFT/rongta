<x-admin.page :title="$title" :back="route('admin.staff.index')">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Учётная запись">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="Имя" for="s-name" required :error="$errors->first('name')">
                    <x-admin.input id="s-name" wire:model="name" autocomplete="off" :invalid="$errors->has('name')" />
                </x-admin.field>

                <x-admin.field label="Эл. почта" for="s-email" required :error="$errors->first('email')" hint="Используется для входа в панель.">
                    <x-admin.input type="email" id="s-email" wire:model="email" autocomplete="off" :invalid="$errors->has('email')" />
                </x-admin.field>

                <x-admin.field label="Роль" for="s-role" required :error="$errors->first('role')"
                               :hint="$self ? 'Свою роль изменить нельзя.' : 'Редактор ведёт товары и контент. Администратор также управляет сотрудниками и настройками.'">
                    <x-admin.select id="s-role" wire:model="role" :disabled="$self" :options="['editor' => 'Редактор', 'admin' => 'Администратор']" />
                </x-admin.field>

                <div class="flex flex-col justify-center">
                    <x-admin.toggle wire:model="is_active" :disabled="$self" label="Доступ включён"
                                    :hint="$self ? 'Свою учётную запись отключить нельзя.' : 'Отключённый сотрудник не может войти в панель.'" />
                    @error('is_active') <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-admin.card>

        <x-admin.card :title="$userId ? 'Смена пароля' : 'Пароль'"
                      :hint="$userId ? 'Оставьте поля пустыми, чтобы не менять пароль.' : 'Передайте пароль сотруднику лично: письмо со ссылкой не отправляется.'">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="Пароль" for="s-password" :required="! $userId" :error="$errors->first('password')" hint="Не меньше 10 символов, с буквами и цифрами.">
                    <x-admin.input type="password" id="s-password" wire:model="password" autocomplete="new-password" :invalid="$errors->has('password')" />
                </x-admin.field>
                <x-admin.field label="Пароль ещё раз" for="s-password2" :required="! $userId">
                    <x-admin.input type="password" id="s-password2" wire:model="password_confirmation" autocomplete="new-password" />
                </x-admin.field>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.staff.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
