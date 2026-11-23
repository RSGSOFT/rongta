@php
    $symbol = setting('logo.symbol', 'images/rongta-symbol.png');
    $tabs = ['reseller' => 'dl.tab.res', 'integrator' => 'dl.tab.int'];
    $title = $current ? $current->tr('name') : ui('dl.allregions');
@endphp
<div class="pb-wrap">
    <div class="pb-head">
        <h1>{{ ui('dl.h') }}</h1>
        <p class="pb-lead">{{ ui('dl.lead') }}</p>
    </div>

    {{-- Tabs: arrow keys move between them, as a tablist should. --}}
    <div class="pb-tabs" role="tablist" aria-label="{{ ui('dl.tabs') }}"
         x-data
         x-on:keydown.arrow-right.prevent="($event.target.nextElementSibling ?? $el.firstElementChild).focus(); document.activeElement.click()"
         x-on:keydown.arrow-left.prevent="($event.target.previousElementSibling ?? $el.lastElementChild).focus(); document.activeElement.click()">
        @foreach ($tabs as $kind => $key)
            <button type="button" role="tab" id="tab-{{ $kind }}" wire:key="tab-{{ $kind }}"
                    wire:click="setTab('{{ $kind }}')"
                    aria-selected="{{ $tab === $kind ? 'true' : 'false' }}"
                    tabindex="{{ $tab === $kind ? '0' : '-1' }}"
                    aria-controls="dealer-panel">{{ ui($key) }}</button>
        @endforeach
    </div>

    {{-- Schematic tile map (hidden on phones, where the select below takes over). --}}
    <div class="pb-map" role="group" aria-label="{{ ui('dl.map') }}">
        @foreach ($regions as $r)
            @php $n = $counts[$r->id] ?? 0; @endphp
            <button type="button" wire:key="tile-{{ $r->slug }}" id="tile-{{ $r->slug }}"
                    class="pb-tile {{ $n ? '' : 'pb-tile-zero' }}"
                    wire:click="setRegion('{{ $region === $r->slug ? '' : $r->slug }}')"
                    aria-pressed="{{ $region === $r->slug ? 'true' : 'false' }}"
                    style="grid-column: {{ (int) $r->map_col }}; grid-row: {{ (int) $r->map_row }}"
                    title="{{ $r->tr('name') }}">
                <span>{{ $r->tr('short') }}</span>
                <small>{{ ui('dl.count', ['n' => $n]) }}</small>
            </button>
        @endforeach
    </div>
    <div class="pb-map-tools">
        <span>{{ ui('dl.maphint') }}</span>
        @if ($current)
            <button type="button" class="pb-btn-q" id="region-all" wire:click="setRegion('')">{{ ui('dl.allregions') }}</button>
        @endif
    </div>

    <div class="pb-region-pick pb-field">
        <label for="region-select">{{ ui('dl.region') }}</label>
        <select class="pb-inp" id="region-select" wire:model.live="region">
            <option value="">{{ ui('dl.allregions') }}</option>
            @foreach ($regions as $r)
                <option value="{{ $r->slug }}">{{ $r->tr('name') }} ({{ $counts[$r->id] ?? 0 }})</option>
            @endforeach
        </select>
    </div>

    <div id="dealer-panel" role="tabpanel" aria-labelledby="tab-{{ $tab }}">
        <p class="pb-count" aria-live="polite">
            <span>{{ $title }}: {{ ui($tab === 'reseller' ? 'dl.n.res' : 'dl.n.int', ['n' => $partners->count()]) }}</span>
        </p>

        <div class="pb-dealers">
            {{-- RSG, the official distributor, is always first. --}}
            <article class="pb-dealer pb-dealer-official" wire:key="dealer-rsg">
                <img src="{{ asset($symbol) }}" width="183" height="132" alt="" aria-hidden="true">
                <div>
                    <div class="pb-tags" style="margin-bottom:6px"><span class="pb-tag pb-tag-acc">{{ ui('dl.official') }}</span></div>
                    <h3>RSG</h3>
                    <p class="pb-where">{{ ui('dl.rsg.where') }}</p>
                    <div class="pb-tags" style="margin-top:8px">
                        @foreach (['sales', 'install', 'service', 'integration'] as $offer)
                            <span class="pb-tag">{{ ui('offer.'.$offer) }}</span>
                        @endforeach
                    </div>
                </div>
                <x-site-b.rsg-contact class="pb-official-contact" />
            </article>

            @forelse ($partners as $p)
                <article class="pb-dealer {{ $p->is_placeholder ? 'pb-dealer-sample' : '' }}" wire:key="dealer-{{ $p->id }}">
                    @if ($p->is_placeholder)
                        <div class="pb-tags"><span class="pb-tag pb-tag-sample">{{ ui('dl.sample') }}</span></div>
                    @endif
                    <h3>{{ $p->tr('name') }}</h3>
                    @php $where = collect([$p->region?->tr('name'), $p->city?->tr('name')])->filter()->implode(', '); @endphp
                    @if ($where !== '')
                        <p class="pb-where">{{ $where }}</p>
                    @endif
                    @if ($p->offers)
                        <div class="pb-tags">
                            @foreach ($p->offers as $offer)
                                <span class="pb-tag">{{ ui('offer.'.$offer) }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if ($p->is_placeholder)
                        {{-- A sample entry never shows a phone number, even if one is stored. --}}
                        <p class="pb-small pb-muted">{{ ui('dl.sample.contact') }}</p>
                    @else
                        @if ($p->tr('address') !== '')
                            <p class="pb-small">{{ $p->tr('address') }}</p>
                        @endif
                        @if ($p->phone)
                            <a class="pb-tel pb-num" href="tel:{{ preg_replace('/[^\d+]/', '', $p->phone) }}">{{ $p->phone }}</a>
                        @endif
                        @if ($p->telegram)
                            <span class="pb-small">Telegram: <a class="pb-link" href="https://t.me/{{ ltrim($p->telegram, '@') }}" rel="noopener">{{ '@'.ltrim($p->telegram, '@') }}</a></span>
                        @endif
                    @endif
                </article>
            @empty
                <div class="pb-empty" style="grid-column:1/-1"><p>{{ ui('dl.empty') }}</p></div>
            @endforelse
        </div>
    </div>

    {{-- Resellers and integrators apply on the For partners page. --}}
    <section class="pb-sec">
        <div class="pb-panel pb-partner-cta">
            <div>
                <h2>{{ ui('ps.h') }}</h2>
                <p class="pb-lead">{{ ui('ps.lead') }}</p>
            </div>
            <a class="pb-btn pb-btn-sm" href="{{ lroute('partners') }}" id="dl-partner">{{ ui('ps.form') }}</a>
        </div>
    </section>
</div>
