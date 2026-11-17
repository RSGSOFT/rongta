<div class="w-full max-w-sm">
    <div class="mb-6 flex flex-col items-center text-center">
        <img src="{{ asset('images/rongta-logo.png') }}" alt="Rongta" class="h-10 w-auto">
        <h1 class="mt-4 text-xl font-semibold">Панель управления</h1>
        <p class="mt-1 text-steel">Rongta Uzbekistan · только для сотрудников RSG</p>
    </div>

    <form wire:submit="login" class="rounded-l border border-line bg-label p-5 shadow-card sm:p-6" novalidate>
        <div class="space-y-4">
            <x-admin.field label="Эл. почта" for="login-email" :error="$errors->first('email')">
                <x-admin.input type="email" id="login-email" wire:model="email" autocomplete="username" autofocus required :invalid="$errors->has('email')" />
            </x-admin.field>

            <x-admin.field label="Пароль" for="login-password" :error="$errors->first('password')">
                <x-admin.input type="password" id="login-password" wire:model="password" autocomplete="current-password" required :invalid="$errors->has('password')" />
            </x-admin.field>

            <label class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded-s border-line accent-(--accent-ink)">
                <span>Запомнить меня на этом устройстве</span>
            </label>

            <x-admin.btn variant="primary" type="submit" class="w-full" wire:loading.attr="disabled" wire:target="login">
                <span wire:loading.remove wire:target="login">Войти</span>
                <span wire:loading wire:target="login">Проверка…</span>
            </x-admin.btn>
        </div>
    </form>

    <p class="mt-4 text-center text-xs text-steel">Забыли пароль? Обратитесь к администратору панели.</p>
</div>
