{{-- Rongta symbol + wordmark + "Uzbekistan", linking to the home page. Files come from setting('logo'). --}}
@php
    $symbol = asset(setting('logo.symbol') ?: 'images/rongta-symbol.png');
    $wordmark = asset(setting('logo.wordmark') ?: 'images/rongta-wordmark.png');
@endphp
<a {{ $attributes->class('logo') }} href="{{ lroute('home') }}" aria-label="Rongta Uzbekistan: {{ ui('nav.home') }}">
    <img class="rmark" src="{{ $symbol }}" width="183" height="132" alt="" aria-hidden="true">
    <span class="logo-txt"><img class="logo-wm" src="{{ $wordmark }}" width="426" height="60" alt="Rongta"><span class="logo-sub">Uzbekistan</span></span>
</a>
