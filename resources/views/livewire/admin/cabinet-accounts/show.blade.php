@php
    $statuses = \App\Models\CabinetAccount::STATUSES;
    $tones = ['pending' => 'warn', 'approved' => 'ok', 'blocked' => 'danger'];
    $wants = collect([$account->wants_sales ? 'Продажа' : null, $account->wants_service ? 'Сервис' : null])->filter();
@endphp
<x-admin.page title="Кабинет: {{ $account->company }}" :back="route('admin.cabinet-accounts.index')" backLabel="Назад к кабинетам"
              subtitle="Зарегистрирован {{ $account->created_at->format('d.m.Y H:i') }}">
    <x-slot:actions>
        <x-admin.btn variant="danger" wire:click="delete" confirm="Удалить этот кабинет? Партнёр больше не сможет войти. Записи о работах сервиса останутся.">Удалить</x-admin.btn>
    </x-slot:actions>

    <x-admin.card title="Заявка">
        <dl class="grid gap-x-6 gap-y-2 sm:grid-cols-[auto_1fr]">
            <dt class="text-steel">Статус</dt>
            <dd id="account-status">
                <x-admin.badge :tone="$tones[$account->status] ?? 'neutral'">{{ $statuses[$account->status] ?? $account->status }}</x-admin.badge>
                @if ($account->decided_at)
                    <span class="text-xs text-steel">{{ $account->decided_at->format('d.m.Y H:i') }}{{ $account->decidedBy ? ', '.$account->decidedBy->name : '' }}</span>
                @endif
            </dd>
            <dt class="text-steel">Компания</dt>
            <dd class="font-medium">{{ $account->company }}</dd>
            <dt class="text-steel">Контактное лицо</dt>
            <dd>{{ $account->contact_name }}</dd>
            <dt class="text-steel">Телефон</dt>
            <dd><a href="tel:{{ $account->phone }}" class="tabular-nums hover:text-accent-ink">{{ $account->phone }}</a></dd>
            <dt class="text-steel">Эл. почта (логин)</dt>
            <dd>{{ $account->email }}</dd>
            <dt class="text-steel">Чем занимается</dt>
            <dd>{{ $wants->implode(', ') ?: '—' }}</dd>
        </dl>
    </x-admin.card>

    <form wire:submit="approve" novalidate>
        <x-admin.card title="Партнёр и доступ" hint="Кабинет работает только с привязкой к партнёру. Разделы кабинета зависят от услуг партнёра: «Продажа» открывает его весы, «Сервис» открывает сервисный раздел.">
            <div class="max-w-xl">
                <x-admin.field label="Партнёр" for="account-partner" required :error="$errors->first('partnerId')"
                               :hint="$partners ? 'Нет нужного? Сначала добавьте его в разделе «Партнёры».' : 'Партнёров пока нет: добавьте в разделе «Партнёры».'">
                    <x-admin.select id="account-partner" wire:model.live="partnerId" :options="$partners" placeholder="Выберите партнёра" :invalid="$errors->has('partnerId')" />
                </x-admin.field>
            </div>
            @if ($chosen)
                @php
                    $missing = collect([
                        $account->wants_sales && ! $chosen->offersSales() ? 'Продажа' : null,
                        $account->wants_service && ! $chosen->offersService() ? 'Сервис' : null,
                    ])->filter();
                @endphp
                @if ($missing->isNotEmpty())
                    <p class="mt-3 text-sm text-warn" id="account-missing">В заявке указано «{{ $missing->implode('», «') }}», а у партнёра эта услуга не отмечена: такой раздел в кабинете не откроется. Отметьте услугу в <a class="underline" href="{{ route('admin.partners.edit', $chosen) }}">карточке партнёра</a>.</p>
                @endif
            @endif
            <x-slot:footer>
                <x-admin.btn type="submit" variant="primary" id="account-approve">{{ $account->status === 'approved' ? 'Сохранить партнёра' : 'Одобрить и открыть кабинет' }}</x-admin.btn>
                @if ($account->status !== 'blocked')
                    <x-admin.btn variant="danger" wire:click="block" id="account-block" confirm="Заблокировать кабинет? Партнёр не сможет войти.">Заблокировать</x-admin.btn>
                @endif
            </x-slot:footer>
        </x-admin.card>
    </form>

    <form wire:submit="savePassword" novalidate>
        <x-admin.card title="Новый пароль" hint="Если партнёр забыл пароль: задайте новый и передайте ему. Не меньше 8 знаков.">
            <div class="grid max-w-xl gap-4 sm:grid-cols-2">
                <x-admin.field label="Новый пароль" for="account-password" :error="$errors->first('password')">
                    <x-admin.input type="password" id="account-password" wire:model="password" autocomplete="new-password" :invalid="$errors->has('password')" />
                </x-admin.field>
                <x-admin.field label="Пароль ещё раз" for="account-password2">
                    <x-admin.input type="password" id="account-password2" wire:model="password_confirmation" autocomplete="new-password" />
                </x-admin.field>
            </div>
            <x-slot:footer>
                <x-admin.btn type="submit">Сохранить пароль</x-admin.btn>
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
