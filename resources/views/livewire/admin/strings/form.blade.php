@php $languages = ['ru' => 'Русский', 'uz' => 'Oʻzbekcha', 'en' => 'English']; @endphp
<x-admin.page :title="$title" :back="route('admin.strings.index')" backLabel="Назад к текстам">
    <form wire:submit="save" novalidate>
        <x-admin.card title="Текст на трёх языках" hint="Ключ: {{ $key }}. Ключ задан кодом сайта и не меняется.">
            <div class="grid gap-4 lg:grid-cols-3">
                @foreach ($languages as $l => $name)
                    <x-admin.field :label="$name.' ('.strtoupper($l).')'" for="s-{{ $l }}" :required="$l === 'ru'" :error="$errors->first('form.text.'.$l)">
                        <x-admin.textarea id="s-{{ $l }}" lang="{{ $l }}" rows="7" wire:model="form.text.{{ $l }}" :invalid="$errors->has('form.text.'.$l)" />
                    </x-admin.field>
                @endforeach
            </div>

            <div class="mt-4 space-y-1 text-xs text-steel">
                @if ($placeholders)
                    <p>В тексте есть подстановки:
                        @foreach ($placeholders as $name)<code class="mx-0.5 rounded-s bg-paper-2 px-1 py-0.5 text-ink">{{ '{'.$name.'}' }}</code>@endforeach.
                        Сайт заменяет их на числа и названия, поэтому они должны остаться во всех трёх языках без изменений.</p>
                @endif
                <p>Пустой перевод на сайте заменяется русским текстом. В узбекском используйте знак ʻ (oʻ, gʻ), а не обычный апостроф.</p>
            </div>

            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.strings.index')" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin.page>
