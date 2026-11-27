<x-admin.page title="Передача весов дилеру" :back="route('admin.serials.index')" backLabel="Назад к серийным номерам"
              subtitle="Вставьте серийные номера весов, которые RSG передала одному дилеру. С даты передачи идёт гарантия: {{ $months }} мес.">
    @if ($report)
        <x-admin.card title="Результат" id="handover-report">
            <div class="flex flex-wrap items-center gap-2">
                <x-admin.badge tone="ok">Передано: {{ $report['done'] }}</x-admin.badge>
                @if ($report['problems'])
                    <x-admin.badge tone="warn">Не передано: {{ count($report['problems']) }}</x-admin.badge>
                @endif
            </div>
            @if ($report['problems'])
                <ul class="mt-3 space-y-1">
                    @foreach ($report['problems'] as $problem)
                        <li wire:key="problem-{{ $loop->index }}"><span class="font-mono text-xs font-semibold">{{ $problem['serial'] }}</span> <span class="text-warn">{{ $problem['reason'] }}</span></li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>
    @endif

    <form wire:submit="submit" novalidate>
        <x-admin.card title="Передача">
            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.field label="Дилер" for="ho-partner" required :error="$errors->first('partnerId')"
                               :hint="$dealers ? null : 'Дилеров пока нет: добавьте партнёра с услугой «Продажа» в разделе «Партнёры».'">
                    <x-admin.select id="ho-partner" wire:model="partnerId" :options="$dealers" placeholder="Выберите дилера" :invalid="$errors->has('partnerId')" />
                </x-admin.field>
                <x-admin.field label="Дата передачи" for="ho-date" required :error="$errors->first('happenedOn')">
                    <x-admin.input type="date" id="ho-date" wire:model="happenedOn" max="{{ now()->format('Y-m-d') }}" :invalid="$errors->has('happenedOn')" />
                </x-admin.field>
                <div class="md:col-span-2">
                    <x-admin.field label="Серийные номера" for="ho-serials" required :error="$errors->first('serials')"
                                   hint="По одному в строке или через запятую. До {{ \App\Livewire\Admin\Serials\Handover::MAX_NUMBERS }} номеров за раз.">
                        <x-admin.textarea id="ho-serials" rows="8" class="font-mono" wire:model="serials" :invalid="$errors->has('serials')" />
                    </x-admin.field>
                </div>
            </div>
            <x-slot:footer>
                <x-admin.btn type="submit" variant="primary" wire:loading.attr="disabled" wire:target="submit">Передать дилеру</x-admin.btn>
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
