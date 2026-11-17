@php
    $c = \App\Livewire\Admin\Settings\Trust::class;
    $mb = fn (int $kb) => intdiv($kb, 1024);
    $accept = fn (string $mimes) => '.'.str_replace(',', ',.', $mimes);
    $limits = [
        'clients' => [$c::IMAGE_MIMES, $c::LOGO_MAX_KB],
        'pos' => [$c::IMAGE_MIMES, $c::LOGO_MAX_KB],
        'metrology' => [$c::METROLOGY_MIMES, $c::DOC_MAX_KB],
        'photos' => [$c::IMAGE_MIMES, $c::IMAGE_MAX_KB],
    ];
    $isImage = fn (string $path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'webp'], true);
@endphp
<x-admin.page title="Настройки сайта" subtitle="Материалы блока доверия на главной и на страницах моделей. Пустой пункт на сайте не показывается; если пусты все, блока нет.">
    @include('livewire.admin.settings.tabs', ['current' => 'admin.settings.trust'])

    <form wire:submit="save" novalidate>
        {{-- Certificate --}}
        <x-admin.card title="Сертификат официального дистрибьютора"
                      hint="Скан сертификата Rongta на имя RSG: изображение (PNG, JPG, WebP, до {{ $mb($c::IMAGE_MAX_KB) }} МБ) или PDF (до {{ $mb($c::DOC_MAX_KB) }} МБ).">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex h-24 w-40 flex-none items-center justify-center rounded-m border border-line bg-paper p-2">
                    @if ($newCertificate)
                        <span class="break-all text-center text-xs font-semibold text-ok">Новый файл: {{ $newCertificate->getClientOriginalName() }}</span>
                    @elseif ($certificate && $certificate['kind'] === 'image')
                        <img src="{{ asset($certificate['path']) }}" alt="Сертификат: текущий файл" class="max-h-full max-w-full object-contain">
                    @elseif ($certificate)
                        <a href="{{ asset($certificate['path']) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-accent-ink hover:underline">PDF: открыть</a>
                    @else
                        <span class="text-xs text-steel">Нет файла</span>
                    @endif
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <label class="inline-flex min-h-8 cursor-pointer items-center justify-center rounded-m border border-line bg-label px-2.5 text-xs font-semibold hover:bg-paper focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-ink">
                        Выбрать файл
                        <input type="file" class="sr-only" id="trust-cert-file" wire:model="newCertificate" accept="{{ $accept($c::CERT_MIMES) }}" aria-label="Сертификат: выбрать файл">
                    </label>
                    @if ($certificate || $newCertificate)
                        <x-admin.btn size="sm" variant="danger" wire:click="removeCertificate">Убрать</x-admin.btn>
                    @endif
                </div>
            </div>
            <p class="mt-1 text-xs text-steel" wire:loading wire:target="newCertificate">Загрузка файла…</p>
            @error('newCertificate') <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
        </x-admin.card>

        {{-- Warranty and service --}}
        <x-admin.card title="Гарантия и сервисный центр" hint="Только подтверждённые условия RSG. Пока оба поля пусты, пункт на сайте не показывается.">
            <div class="grid gap-5">
                <x-admin.trans-textarea label="Условия гарантии и сервиса" model="service.text" rows="3" />
                <x-admin.trans-input label="Адрес сервисного центра" model="service.address" />
            </div>
        </x-admin.card>

        {{-- Lists --}}
        @foreach ($c::LISTS as $list => [$title, $hint, $property, $field])
            @php [$mimes, $maxKb] = $limits[$list]; @endphp
            <x-admin.card :title="$title" :hint="$hint.' '.strtoupper(str_replace(',', ', ', $mimes)).', до '.$mb($maxKb).' МБ каждый.'" wire:key="list-{{ $list }}">
                @if ($items[$list] || $pending[$list])
                    <ol class="divide-y divide-paper-2">
                        @foreach ($items[$list] as $i => $item)
                            <li class="flex flex-wrap items-start gap-3 py-3 first:pt-0" wire:key="{{ $list }}-item-{{ $i }}-{{ md5($item['path']) }}">
                                <div class="flex h-16 w-24 flex-none items-center justify-center rounded-m border border-line bg-paper p-1.5">
                                    @if ($isImage($item['path']))
                                        <img src="{{ asset($item['path']) }}" alt="" class="max-h-full max-w-full object-contain">
                                    @else
                                        <a href="{{ asset($item['path']) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-accent-ink hover:underline">{{ strtoupper(pathinfo($item['path'], PATHINFO_EXTENSION)) }}: открыть</a>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    @if ($field === 'name')
                                        <x-admin.field label="Название" for="{{ $list }}-item-{{ $i }}" required :error="$errors->first('items.'.$list.'.'.$i.'.name')">
                                            <x-admin.input id="{{ $list }}-item-{{ $i }}" wire:model="items.{{ $list }}.{{ $i }}.name" maxlength="120" />
                                        </x-admin.field>
                                    @else
                                        <x-admin.trans-input :label="$field === 'title' ? 'Название документа' : 'Подпись'" :model="'items.'.$list.'.'.$i.'.'.$field" :required="$field === 'title'" />
                                    @endif
                                </div>
                                <div class="flex gap-1.5">
                                    <x-admin.btn size="sm" wire:click="move('{{ $list }}', {{ $i }}, -1)" :disabled="$i === 0" aria-label="Выше">↑</x-admin.btn>
                                    <x-admin.btn size="sm" wire:click="move('{{ $list }}', {{ $i }}, 1)" :disabled="$i === count($items[$list]) - 1" aria-label="Ниже">↓</x-admin.btn>
                                    <x-admin.btn size="sm" variant="danger" wire:click="remove('{{ $list }}', {{ $i }})">Убрать</x-admin.btn>
                                </div>
                            </li>
                        @endforeach
                        @foreach ($pending[$list] as $i => $item)
                            <li class="flex flex-wrap items-start gap-3 py-3 first:pt-0" wire:key="{{ $list }}-new-{{ $i }}">
                                <div class="flex h-16 w-24 flex-none items-center justify-center rounded-m border border-dashed border-ok bg-paper p-1.5">
                                    <span class="break-all text-center text-[11px] font-semibold text-ok">Новый: {{ $item['file']->getClientOriginalName() }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    @if ($field === 'name')
                                        <x-admin.field label="Название" for="{{ $list }}-new-{{ $i }}" required :error="$errors->first('pending.'.$list.'.'.$i.'.name')">
                                            <x-admin.input id="{{ $list }}-new-{{ $i }}" wire:model="pending.{{ $list }}.{{ $i }}.name" maxlength="120" />
                                        </x-admin.field>
                                    @else
                                        <x-admin.trans-input :label="$field === 'title' ? 'Название документа' : 'Подпись'" :model="'pending.'.$list.'.'.$i.'.'.$field" :required="$field === 'title'" />
                                    @endif
                                </div>
                                <x-admin.btn size="sm" wire:click="removePending('{{ $list }}', {{ $i }})">Отменить</x-admin.btn>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-steel">Пока пусто: на сайте этот пункт не показывается.</p>
                @endif

                <div class="mt-3">
                    <label class="inline-flex min-h-8 cursor-pointer items-center justify-center rounded-m border border-line bg-label px-2.5 text-xs font-semibold hover:bg-paper focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-ink">
                        Добавить файлы
                        <input type="file" multiple class="sr-only" id="trust-{{ $list }}-files" wire:model="{{ $property }}" accept="{{ $accept($mimes) }}" aria-label="{{ $title }}: добавить файлы">
                    </label>
                    <span class="ml-2 text-xs text-steel">Новые файлы появятся на сайте после сохранения.</span>
                    <p class="mt-1 text-xs text-steel" wire:loading wire:target="{{ $property }}">Загрузка файлов…</p>
                    @error($property) <p class="mt-1 text-xs font-medium text-danger" role="alert">{{ $message }}</p> @enderror
                </div>
            </x-admin.card>
        @endforeach

        <x-admin.card>
            <x-admin.form-actions label="Сохранить материалы" />
        </x-admin.card>
    </form>
</x-admin.page>
