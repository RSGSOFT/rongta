{{-- Site footer: about line, distributor badge, section links, contacts. --}}
@php
    $partnerUrl = auth('partner')->check() ? lroute('partner') : lroute('partner.login');
@endphp
<footer class="ftr">
    <div class="wrap ftr-in">
        <div>
            <x-site.logo />
            <p style="margin-top:12px;max-width:44ch">{{ ui('ftr.about') }}</p>
            <span class="badge"><span class="badge-dot"></span>{{ ui('badge.dist') }}</span>
        </div>
        <div>
            <h4>{{ ui('ftr.site') }}</h4>
            <ul>
                <li><a href="{{ lroute('catalog') }}">{{ ui('nav.catalog') }}</a></li>
                <li><a href="{{ lroute('compare') }}">{{ ui('nav.compare') }}</a></li>
                <li><a href="{{ lroute('dealers') }}">{{ ui('nav.dealers') }}</a></li>
                <li><a href="{{ lroute('knowledge') }}">{{ ui('nav.knowledge') }}</a></li>
                <li><a href="{{ $partnerUrl }}">{{ ui('nav.partner') }}</a></li>
            </ul>
        </div>
        <div>
            <h4>{{ ui('ftr.contact') }}</h4>
            <ul>
                <li><x-site.contact /></li>
                <li>{{ ui('ftr.city') }}</li>
            </ul>
        </div>
    </div>
    <div class="wrap ftr-note">{{ ui('site.ftr.note') }}</div>
</footer>
