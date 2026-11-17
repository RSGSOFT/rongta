{{--
    Trust block: distributor certificate, warranty and service, client logos, POS software,
    Uzbek metrology documents (setting "trust", App\Support\Trust), plus shop photos and the
    "About the manufacturer" card (setting "maker", App\Support\Maker) in the full version.
    An item without material renders nothing; the block renders nothing when all are empty.
    <x-site.trust /> full, <x-site.trust compact /> short (model page).
--}}
@props(['compact' => false])
@php
    $trust = \App\Support\Trust::all();
    $hasGrid = \App\Support\Trust::hasGrid($trust);
    $hasService = \App\Support\Trust::hasService($trust);
    $photos = $compact ? [] : $trust['photos'];
    $facts = $compact ? [] : \App\Support\Maker::visibleRows();
    $source = $facts ? \App\Support\Maker::source() : '';
    $show = $hasGrid || $photos || $facts;
@endphp
@if ($show)
<section {{ $attributes->class($compact ? 'sec-tight' : 'sec') }}>
    <div class="wrap">
        @if ($compact)
            <h2 style="font-size:1.7rem;margin-bottom:16px">{{ ui('tr.h.compact') }}</h2>
        @else
            <div class="sec-head"><div><h2>{{ ui('tr.h') }}</h2>@if ($hasGrid)<p>{{ ui('tr.lead') }}</p>@endif</div></div>
        @endif

        @if ($hasGrid)
            <div class="trust-grid">
                @if ($trust['certificate'])
                    <div class="trust-item" id="trust-cert">
                        @if ($trust['certificate']['kind'] === 'image')
                            <a class="trust-cert" href="{{ asset($trust['certificate']['path']) }}" target="_blank" rel="noopener">
                                <img src="{{ asset($trust['certificate']['path']) }}" alt="{{ ui('tr.cert') }}" loading="lazy">
                            </a>
                        @else
                            <x-site.icon name="cert" />
                        @endif
                        <b>{{ ui('tr.cert') }}</b>
                        <span>{{ ui('tr.cert.d') }}</span>
                        <a class="link trust-open" href="{{ asset($trust['certificate']['path']) }}" target="_blank" rel="noopener">{{ ui('tr.open') }}@if ($trust['certificate']['kind'] === 'pdf') (PDF)@endif</a>
                    </div>
                @endif

                @if ($hasService)
                    <div class="trust-item" id="trust-service">
                        <x-site.icon name="wrench" />
                        <b>{{ ui('tr.service') }}</b>
                        @if (trv($trust['service']['text']) !== '')
                            <span class="trust-text">{{ trv($trust['service']['text']) }}</span>
                        @endif
                        @if (trv($trust['service']['address']) !== '')
                            <span class="trust-text"><strong>{{ ui('tr.address') }}:</strong> {{ trv($trust['service']['address']) }}</span>
                        @endif
                    </div>
                @endif

                @foreach (['clients' => 'tr.clients', 'pos' => 'tr.pos'] as $list => $titleKey)
                    @if ($trust[$list])
                        <div class="trust-item" id="trust-{{ $list }}">
                            <b>{{ ui($titleKey) }}</b>
                            <ul class="trust-logos">
                                @foreach ($trust[$list] as $logo)
                                    <li><img src="{{ asset($logo['path']) }}" alt="{{ $logo['name'] }}" title="{{ $logo['name'] }}" loading="lazy"></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach

                @if ($trust['metrology'])
                    <div class="trust-item" id="trust-metro">
                        <x-site.icon name="doc" />
                        <b>{{ ui('tr.metro') }}</b>
                        <ul class="trust-docs">
                            @foreach ($trust['metrology'] as $doc)
                                <li><a class="link" href="{{ asset($doc['path']) }}" target="_blank" rel="noopener">{{ trv($doc['title']) ?: ui('tr.metro') }}</a> <span>{{ strtoupper($doc['ext']) }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        @if ($photos || $facts)
            <div @class(['trust-cols', 'trust-cols-one' => ! $photos || ! $facts, 'trust-cols-first' => ! $hasGrid])>
                @if ($photos)
                    <div>
                        <h3>{{ ui('tr.photos') }}</h3>
                        <div class="photos">
                            @foreach ($photos as $photo)
                                <figure class="trust-photo">
                                    <img src="{{ asset($photo['path']) }}" alt="{{ trv($photo['caption']) }}" loading="lazy">
                                    @if (trv($photo['caption']) !== '')
                                        <figcaption>{{ trv($photo['caption']) }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if ($facts)
                    <div class="receipt" id="trust-maker">
                        <h3>{{ ui('tr.maker') }}</h3>
                        <dl>
                            @foreach ($facts as [$label, $value])
                                <div><dt>{{ $label }}</dt><dd class="num">{{ $value }}</dd></div>
                            @endforeach
                        </dl>
                        @if ($source !== '')
                            <p class="small">{{ $source }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>
@endif
