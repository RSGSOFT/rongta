<x-admin.page :title="$title" :back="route('admin.partner-users.index')">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Доступ в кабинет партнёра">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="Имя" for="u-name" required :error="$errors->first('name')" hint="Кому выдан доступ: человек или компания.">
                    <x-admin.input id="u-name" wire:model="name" autocomplete="off" :invalid="$errors->has('name')" />
                </x-admin.field>

                <x-admin.field label="Логин" for="u-login" required :error="$errors->first('login')" hint="Строчные латинские буквы, цифры, точка, дефис, подчёркивание.">
                    <x-admin.input id="u-login" wire:model="login" autocomplete="off" autocapitalize="none" spellcheck="false" :invalid="$errors->has('login')" />
                </x-admin.field>

                <x-admin.field label="Тип доступа" for="u-type" required :error="$errors->first('type')"
                               hint="Определяет, какой раздел кабинета увидит партнёр: для реселлеров или для интеграторов.">
                    <x-admin.select id="u-type" wire:model.live="type" :options="$types" :invalid="$errors->has('type')" />
                </x-admin.field>

                <x-admin.field label="Партнёр" for="u-partner" :error="$errors->first('partner_id')" hint="Необязательно. В списке партнёры выбранного типа.">
                    <x-admin.select id="u-partner" wire:model="partner_id" wire:key="partners-of-{{ $type }}" placeholder="Не привязан" :options="$partners" :invalid="$errors->has('partner_id')" />
                </x-admin.field>

                <div class="md:col-span-2">
                    <x-admin.toggle wire:model="is_active" label="Доступ включён" hint="С выключенным доступом войти в кабинет нельзя." />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card :title="$partnerUserId ? 'Смена пароля' : 'Пароль'"
                      :hint="$partnerUserId ? 'Действующий пароль не показывается. Чтобы выдать новый, заполните оба поля; чтобы не менять, оставьте пустыми.' : 'Передайте пароль партнёру лично. После сохранения он больше нигде не показывается.'">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="{{ $partnerUserId ? 'Новый пароль' : 'Пароль' }}" for="u-password" :required="! $partnerUserId" :error="$errors->first('password')" hint="Не меньше 10 символов, с буквами и цифрами.">
                    <x-admin.input type="password" id="u-password" wire:model="password" autocomplete="new-password" :invalid="$errors->has('password')" />
                </x-admin.field>
                <x-admin.field label="Пароль ещё раз" for="u-password2" :required="! $partnerUserId">
                    <x-admin.input type="password" id="u-password2" wire:model="password_confirmation" autocomplete="new-password" />
                </x-admin.field>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.partner-users.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
