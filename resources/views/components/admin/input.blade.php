@props(['type' => 'text', 'invalid' => false])
<input type="{{ $type }}" @if ($invalid) aria-invalid="true" @endif {{ $attributes->class('adm-control') }}>
