@php
    $contactsReal = \App\Support\RsgContacts::areConfirmed();
    $hero = (array) setting('hero', []);
@endphp
<div>
    @foreach ($sections as $section)
        @switch($section)
            @case('hero')
                <section class="hero" wire:key="sec-hero">
                    <div class="wrap hero-grid">
                        <div>
                            <h1>{{ ui($hero['title_key'] ?? 'hero.h1') }}</h1>
                            <p class="lead">{{ ui($hero['lead_key'] ?? 'hero.lead') }}</p>
                            <div class="btn-row">
                                <a class="btn btn-p" href="{{ lroute('dealers') }}" id="hero-cta">{{ ui('cta.dealer') }}</a>
                                <a class="btn btn-s" href="{{ lroute('catalog') }}" id="hero-catalog">{{ ui('cta.catalog') }}</a>
                            </div>
                            <p class="hero-contact">@if ($contactsReal){{ ui($hero['contact_key'] ?? 'hero.contact') }} @endif<x-site.contact /></p>
                        </div>
                        {{-- Decorative motif: a scale's own display and a printed label. Not a product price: no currency anywhere. --}}
                        <div class="rig" wire:ignore>
                            <div class="readout" role="img" aria-label="{{ ui($hero['readout_alt_key'] ?? 'hero.readout.alt') }}" data-readout>
                                <div class="readout-win"><span class="readout-cap">{{ ui('ro.weight') }}</span><div data-ro="weight"><x-site.seg7 value="1.250" /></div></div>
                                <div class="readout-win"><span class="readout-cap">{{ ui('ro.unit') }}</span><x-site.seg7 value="12000" /></div>
                                <div class="readout-win"><span class="readout-cap">{{ ui('ro.total') }}</span><div data-ro="total"><x-site.seg7 value="15000" /></div></div>
                            </div>
                            <div class="rig-scale"><x-site.scale-ill kind="pole" :opts="['head' => 'wide', 'blank' => true]" name="RLS1000/RLS1100A" /></div>
                            <div class="plabel hero-label" aria-hidden="true">
                                <span class="cap">Rongta Uzbekistan</span>
                                <b>{{ ui('lbl.product') }}</b>
                                <dl><dt>{{ ui('lbl.weight') }}</dt><dd>1.250</dd><dt>{{ ui('lbl.unit') }}</dt><dd>12&nbsp;000</dd></dl>
                                <div class="sum num">15&nbsp;000</div>
                                <x-site.barcode seed="rongta-hero" :n="46" />
                                <div class="cap">{{ ui('lbl.sample') }}</div>
                            </div>
                        </div>
                    </div>
                </section>
                @break

            @case('chooser')
                @if ($options->isNotEmpty())
                    <section class="sec-tight" wire:key="sec-chooser">
                        <div class="wrap">
                            <div class="chooser">
                                <div class="chooser-top">
                                    <div><h2 id="chooser-h">{{ ui('ch.h') }}</h2></div>
                                    <div class="seg-tabs" role="group" aria-labelledby="chooser-h">
                                        @foreach ($options as $o)
                                            <button type="button" id="ch-{{ $o->key }}" wire:click="pick(@js($o->key))" aria-pressed="{{ $o->is($option) ? 'true' : 'false' }}">{{ ui('ch.'.$o->key) }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="chooser-out" id="chooser-out" aria-live="polite" wire:key="chooser-out-{{ $option->key }}">
                                    <div>
                                        <p class="small muted">{{ ui('ch.rec') }}</p>
                                        <h3>{{ $option->family?->tr('name') }}</h3>
                                        <p>{{ $option->tr('why') }}</p>
                                        @if ($option->family)
                                            <p style="margin-top:14px"><a class="link" href="{{ lroute('catalog', ['family' => $option->family->slug]) }}" id="ch-all">{{ ui('ch.all') }}</a></p>
                                        @endif
                                    </div>
                                    <div class="chooser-models">
                                        @foreach ($option->scaleModels as $m)
                                            <a class="mini" href="{{ lroute('model', ['slug' => $m->slug]) }}" id="mini-{{ $m->slug }}">
                                                <span class="mini-pic"><x-site.scale-ill :model="$m" /></span>
                                                <span>
                                                    <b>{!! str_replace('/', '/<wbr>', e($m->name)) !!}</b>
                                                    <span>{{ trv($m->pivot->note) ?: $m->tr('type') }}</span>
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif
                @break

            @case('families')
                <section class="sec" wire:key="sec-families">
                    <div class="wrap">
                        <div class="sec-head"><div><h2>{{ ui('fam.h') }}</h2><p>{{ ui('fam.lead') }}</p></div></div>
                        <div class="fam-rows">
                            @foreach ($families as $family)
                                <a class="fam-row" href="{{ lroute('catalog', ['family' => $family->slug]) }}" id="fam-{{ $family->slug }}">
                                    <span class="fam-pic"><x-site.scale-ill :kind="$family->pic ?: $family->scaleModels->first()->kind" :opts="['cam' => true]" :name="$family->tr('name')" /></span>
                                    <h3>{{ $family->tr('name') }}</h3>
                                    <p>{{ $family->tr('line') }}<span class="fam-models" style="display:block;margin-top:8px;color:var(--ink)">{{ $family->scaleModels->pluck('name')->implode(', ') }}</span></p>
                                    <span class="fam-go">{{ ui('fam.go', ['n' => $family->scaleModels->count()]) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
                @break

            @case('aurora')
                @if ($aurora->isNotEmpty())
                    <section class="sec night" wire:key="sec-aurora">
                        <div class="wrap aurora-grid">
                            <div>
                                <h2>{{ ui('au.h') }}</h2>
                                <p class="lead" style="margin-top:14px">{{ ui('au.lead') }}</p>
                                <div class="aurora-list">
                                    @foreach ($aurora as $m)
                                        <a href="{{ lroute('model', ['slug' => $m->slug]) }}" id="au-{{ $m->slug }}"><b>{{ $m->name }}</b><span>{{ $m->tr('type') }}</span></a>
                                    @endforeach
                                </div>
                                @if ($auroraFamily)
                                    <div class="btn-row" style="margin-top:24px"><a class="btn btn-s" href="{{ lroute('catalog', ['family' => $auroraFamily->slug]) }}" id="au-all">{{ ui('au.all') }}</a></div>
                                @endif
                            </div>
                            <div class="aurora-pic"><x-site.scale-ill :model="$auroraPic" /></div>
                        </div>
                    </section>
                @endif
                @break

            @case('trust')
                <x-site.trust wire:key="sec-trust" />
                @break

            @case('knowledge')
                <section class="sec" wire:key="sec-knowledge">
                    <div class="wrap kt">
                        <div>
                            <h2>{{ ui('kt.h') }}</h2>
                            <p class="lead" style="margin-top:12px">{{ ui('kt.lead') }}</p>
                            <div class="btn-row" style="margin-top:20px"><a class="btn btn-s" href="{{ lroute('knowledge') }}" id="kt-open">{{ ui('kt.open') }}</a></div>
                        </div>
                        <div class="kt-links">
                            @foreach ([['kb-manuals', 'kb.manuals', 'kt.n.manuals'], ['kb-software', 'kb.software', 'kt.n.software'], ['kb-faq', 'kb.faq', 'kt.n.faq'], ['kb-guide', 'kb.guide.short', 'kt.n.guide']] as [$anchor, $title, $note])
                                <a href="{{ lroute('knowledge') }}#{{ $anchor }}" id="kt-{{ $anchor }}">{{ ui($title) }}<span>{{ ui($note) }}</span></a>
                            @endforeach
                        </div>
                    </div>
                </section>
                @break

            @case('partners')
                <section class="pstrip" wire:key="sec-partners">
                    <div class="wrap pstrip-in">
                        <div><h2>{{ ui('ps.h') }}</h2><p>{{ ui('ps.lead') }}</p></div>
                        <div class="btn-row">
                            <a class="btn btn-s" href="{{ lroute('partners') }}" id="ps-form">{{ ui('ps.form') }}</a>
                        </div>
                    </div>
                </section>
                @break
        @endswitch
    @endforeach
</div>
