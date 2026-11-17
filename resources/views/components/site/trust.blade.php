{{--
    Trust block: distributor certificate, warranty and service, client logos, POS software,
    Uzbek metrology documents. The materials are still to be supplied by the owner, so each
    frame is marked "to be confirmed". <x-site.trust /> full, <x-site.trust compact /> short.
--}}
@props(['compact' => false])
@php
    $items = [
        ['cert', 'tr.cert', 'tr.cert.d'],
        ['wrench', 'tr.service', 'tr.service.d'],
        ['logos', 'tr.clients', 'tr.clients.d'],
        ['pos', 'tr.pos', 'tr.pos.d'],
        ['doc', 'tr.metro', 'tr.metro.d'],
    ];
    // Manufacturer figures as published on rongtatech.com (carried over from the approved prototype).
    $facts = [
        ['tr.f.founded', '2010'],
        ['tr.f.hq', ui('tr.f.hq.v')],
        ['tr.f.stock', ui('tr.f.stock.v')],
        ['tr.f.patents', '210+'],
        ['tr.f.customers', app()->getLocale() === 'en' ? '2,000+' : "2\u{00A0}000+"],
        ['tr.f.rd', '100+'],
    ];
@endphp
<section {{ $attributes->class($compact ? 'sec-tight' : 'sec') }}>
    <div class="wrap">
        @if ($compact)
            <h2 style="font-size:1.7rem;margin-bottom:16px">{{ ui('tr.h.compact') }}</h2>
        @else
            <div class="sec-head"><div><h2>{{ ui('tr.h') }}</h2><p>{{ ui('tr.lead') }}</p></div></div>
        @endif
        <div class="trust-grid">
            @foreach ($items as [$icon, $title, $text])
                <div class="ph"><x-site.icon :name="$icon" /><b>{{ ui($title) }}</b><span>{{ ui($text) }}</span><span class="ph-tag">{{ ui('val.tbc') }}</span></div>
            @endforeach
        </div>
        @unless ($compact)
            <div class="trust-cols">
                <div>
                    <h3>{{ ui('tr.photos') }}</h3>
                    <div class="photos">
                        @for ($i = 0; $i < 3; $i++)
                            <div class="ph"><span>{{ ui('ph.photo') }}</span></div>
                        @endfor
                    </div>
                </div>
                <div class="receipt">
                    <h3>{{ ui('tr.maker') }}</h3>
                    <dl>
                        @foreach ($facts as [$key, $value])
                            <div><dt>{{ ui($key) }}</dt><dd class="num">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                    <p class="small">{{ ui('tr.source') }}</p>
                </div>
            </div>
        @endunless
    </div>
</section>
