{{-- Options go in the slot, or pass :options="[value => label]" (and an optional placeholder). --}}
@props(['options' => null, 'placeholder' => null, 'invalid' => false])
<select @if ($invalid) aria-invalid="true" @endif {{ $attributes->class('adm-control pr-8') }}>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @if ($options !== null)
        @foreach ($options as $value => $text)
            <option value="{{ $value }}">{{ $text }}</option>
        @endforeach
    @endif
    {{ $slot }}
</select>
