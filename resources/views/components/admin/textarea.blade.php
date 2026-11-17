@props(['rows' => 3, 'invalid' => false])
<textarea rows="{{ $rows }}" @if ($invalid) aria-invalid="true" @endif {{ $attributes->class('adm-control') }}>{{ $slot }}</textarea>
