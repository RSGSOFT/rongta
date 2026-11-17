{{--
    One spec value in any of its three shapes (see App\Models\ModelSpec):
    text {ru,uz,en} | parts {parts: [[label, value], ...]} | note {v, note}. Null renders a dash.
    <x-site.spec-value :value="$spec?->value" />
--}}
@props(['value' => null])
@php
    $tbc = ui('val.tbc');
@endphp
@if (! is_array($value) || $value === [])
    <span class="tbc">—</span>
@elseif (isset($value['parts']))
    <div class="vparts">
        @foreach ($value['parts'] as $part)
            <div><small>{{ trv($part[0] ?? null) }}</small><x-site.spec-value :value="$part[1] ?? null" /></div>
        @endforeach
    </div>
@elseif (array_key_exists('v', $value))
    <x-site.spec-value :value="$value['v']" /><span class="vnote">{{ trv($value['note'] ?? null) }}</span>
@else
    @php $text = trv($value); @endphp
    @if ($text === '')
        <span class="tbc">—</span>
    @elseif ($text === $tbc || ($value['en'] ?? null) === 'to be confirmed')
        <span class="tbc">{{ $tbc }}</span>
    @else
        {{ $text }}
    @endif
@endif
