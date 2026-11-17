{{--
    Catalog card: visual, name, three key facts (max weight, label printing, connection), compare tick.
    No price line (project rule).
    <x-site.model-card :model="$scaleModel" />        optional: where="sim" (id prefix), cap="15|30"
--}}
@props(['model', 'where' => 'cat', 'cap' => 'all'])
@php
    $url = lroute('model', ['slug' => $model->slug]);
    $names = (array) setting('connection_names', []) + ['eth' => 'Ethernet', 'rs232' => 'RS232', 'usb' => 'USB', 'wifi' => 'Wi-Fi', 'bt' => 'Bluetooth'];
    $conn = (array) $model->conn;
    $std = [];
    $opt = false;
    foreach (['eth', 'rs232', 'usb', 'wifi', 'bt'] as $key) {
        if (($conn[$key] ?? null) === 'std') {
            $std[] = $names[$key];
        } elseif (($conn[$key] ?? null) === 'opt') {
            $opt = true;
        }
    }
    $capText = in_array((string) $cap, ['15', '30'], true) ? ui('val.cap.'.$cap) : ui('val.cap.both');
    $labelText = ui(match ($model->label_print) { 'yes' => 'val.yes', 'receipt' => 'val.receipt', default => 'val.no' });
@endphp
<article {{ $attributes->class('card') }}>
    <a @class(['card-pic', 'night' => $model->is_aurora]) href="{{ $url }}" tabindex="-1" aria-hidden="true"><x-site.scale-ill :model="$model" /></a>
    <div class="card-body">
        <p class="card-type">{{ $model->tr('type') }}</p>
        <h3><a href="{{ $url }}" id="card-{{ $where }}-{{ $model->slug }}">{{ $model->name }}</a></h3>
        <dl class="facts">
            <div><dt>{{ ui('fact.max') }}</dt><dd class="num">{{ $capText }}</dd></div>
            <div><dt>{{ ui('fact.label') }}</dt><dd>{{ $labelText }}</dd></div>
            <div><dt>{{ ui('fact.conn') }}</dt><dd>@forelse ($std as $name)<span style="white-space:nowrap">{{ $name }}</span>{{ $loop->last ? '' : ', ' }}@empty @unless ($opt)<span class="tbc">—</span>@endunless @endforelse{{ $opt ? ' '.ui('val.plusopt') : '' }}</dd></div>
        </dl>
        <div class="card-foot">
            <x-site.compare-tick :model="$model" :where="$where" />
            <a class="link small" href="{{ $url }}" aria-label="{{ ui('card.more') }}: {{ $model->name }}">{{ ui('card.more') }}</a>
        </div>
    </div>
</article>
