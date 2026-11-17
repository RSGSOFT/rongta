{{-- "Compare" tick for one model. The list lives in the session (key "compare") and is kept by the compare tray. --}}
@props(['model', 'where' => 'card'])
@php
    $id = 'cmp-'.$where.'-'.$model->slug;
    $on = in_array($model->slug, (array) session('compare', []), true);
@endphp
<label {{ $attributes->class('cmp') }} for="{{ $id }}">
    <input type="checkbox" id="{{ $id }}" value="{{ $model->slug }}" @checked($on)
        x-data
        x-bind:checked="$store.compare.has(@js($model->slug))"
        x-on:change="$store.compare.toggle(@js($model->slug), $event.target)">
    {{ ui('cmp.add') }}
</label>
