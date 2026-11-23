{{-- Site footer: about line, distributor badge, section links, contacts. --}}
@php
    // Distributor website from admin settings; the badge links to it (new tab) only when set.
    $distributorUrl = (string) setting('contacts.distributor_url', '');
@endphp
<footer class="ftr">
    <div class="wrap ftr-in">
        <div>
            <x-site.logo />
            <p style="margin-top:12px;max-width:44ch">{{ ui('ftr.about') }}</p>
            @if ($distributorUrl !== '')
                <a class="badge" id="ftr-dist" href="{{ $distributorUrl }}" target="_blank" rel="noopener"><span class="badge-dot"></span>{{ ui('ftr.dist') }}</a>
            @else
                <span class="badge" id="ftr-dist"><span class="badge-dot"></span>{{ ui('ftr.dist') }}</span>
            @endif
        </div>
        <div>
            <h4>{{ ui('ftr.site') }}</h4>
            <ul>
                <li><a href="{{ lroute('catalog') }}">{{ ui('nav.catalog') }}</a></li>
                <li><a href="{{ lroute('compare') }}">{{ ui('nav.compare') }}</a></li>
                <li><a href="{{ lroute('dealers') }}">{{ ui('nav.dealers') }}</a></li>
                <li><a href="{{ lroute('knowledge') }}">{{ ui('nav.knowledge') }}</a></li>
                <li><a href="{{ lroute('partners') }}">{{ ui('nav.partner') }}</a></li>
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
