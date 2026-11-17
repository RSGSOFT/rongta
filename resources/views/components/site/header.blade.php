{{-- Top strip (distributor badge + contacts) and the sticky header (logo, nav, language switch, dealer button). --}}
@php

    $page = \App\Support\Locales::currentPage();
    $current = match (true) {
        in_array($page, ['model', 'compare'], true) => 'catalog',
        $page === 'partner.login' => 'partner',
        default => $page,
    };
    $nav = [
        'catalog' => [lroute('catalog'), 'nav.catalog'],
        'dealers' => [lroute('dealers'), 'nav.dealers'],
        'knowledge' => [lroute('knowledge'), 'nav.knowledge'],
        'partner' => [auth('partner')->check() ? lroute('partner') : lroute('partner.login'), 'nav.partner'],
    ];
@endphp
<div class="strip">
    <div class="wrap strip-in">
        <span class="badge"><span class="badge-dot"></span>{{ ui('badge.dist') }}</span>
        <span class="strip-contact"><x-site.contact /></span>
    </div>
</div>
<header class="hdr">
    <div class="wrap hdr-in">
        <x-site.logo id="logo-link" />
        <nav class="nav" aria-label="{{ ui('nav.label') }}">
            @foreach ($nav as $id => [$url, $key])
                <a href="{{ $url }}" id="nav-{{ $id }}" @if ($current === $id) aria-current="page" @endif>{{ ui($key) }}</a>
            @endforeach
        </nav>
        <div class="lang" role="group" aria-label="{{ ui('lang.label') }}">
            @foreach (\App\Support\Locales::ALL as $locale)
                <a href="{{ locale_url($locale) }}" id="lang-{{ $locale }}" lang="{{ $locale }}" hreflang="{{ $locale }}"
                    title="{{ ui('lang.name.'.$locale) }}" @if (app()->getLocale() === $locale) aria-current="true" @endif>{{ strtoupper($locale) }}</a>
            @endforeach
        </div>
        <a class="btn btn-p btn-sm" href="{{ lroute('dealers') }}" id="hdr-cta">{{ ui('cta.dealer') }}</a>
    </div>
</header>
