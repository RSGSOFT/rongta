@php
    // The "schematic, a photo will replace it" note only for the prototype drawing.
    $photos = filled($model->image) || filled($model->drawing);
    $sourceUrl = (string) ($model->source_url ?? '');
@endphp
<div>
    {{-- Hero: AURORA models get the dark treatment. No price line (project rule). --}}
    <section @class(['m-hero', 'night' => $model->is_aurora])>
        <div class="wrap">
            <p class="crumb"><a href="{{ lroute('catalog') }}">{{ ui('nav.catalog') }}</a>@if ($family) / <a href="{{ lroute('catalog', ['family' => $family->slug]) }}">{{ $family->tr('name') }}</a>@endif</p>
            <div class="m-grid">
                <div>
                    <span class="m-code">{{ $model->name }}</span>
                    <h1>{{ $model->tr('headline') ?: $model->name }}</h1>
                    @if ($model->tr('intro') !== '')
                        <p class="lead">{{ $model->tr('intro') }}</p>
                    @endif
                    <div class="btn-row">
                        <a class="btn btn-p" href="{{ lroute('dealers') }}" id="m-cta">{{ ui('cta.dealer') }}</a>
                        <x-site.compare-tick :model="$model" where="model" />
                    </div>
                </div>

                <div id="gal-{{ $model->slug }}" x-data="{ i: 0 }">
                    <div class="gal-main">
                        @foreach ($views as $n => $view)
                            <div x-show="i === {{ $n }}" @if ($n > 0) x-cloak @endif>
                                @if ($view['src'])
                                    <img class="ill-photo" src="{{ $view['src'] }}" alt="{{ $model->name }}: {{ $view['caption'] }}" @if ($n > 0) loading="lazy" @endif>
                                @else
                                    <x-site.scale-ill :model="$model" :view-box="$view['viewBox']" :photo="false" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <p class="gal-cap">
                        @foreach ($views as $n => $view)
                            <span x-show="i === {{ $n }}" @if ($n > 0) x-cloak @endif>{{ $view['caption'] }}@unless ($photos). {{ ui('view.note') }}@endunless</span>
                        @endforeach
                    </p>
                    @if (count($views) > 1)
                        <div class="gal-thumbs" role="group" aria-label="{{ ui('view.label') }}">
                            @foreach ($views as $n => $view)
                                <button type="button" id="gal-{{ $model->slug }}-{{ $n }}" x-on:click="i = {{ $n }}"
                                    aria-pressed="{{ $n === 0 ? 'true' : 'false' }}" x-bind:aria-pressed="(i === {{ $n }}).toString()" aria-label="{{ $view['caption'] }}">
                                    @if ($view['src'])
                                        <img class="ill-photo" src="{{ $view['src'] }}" alt="" loading="lazy">
                                    @else
                                        <x-site.scale-ill :model="$model" :view-box="$view['viewBox']" :photo="false" aria-hidden="true" />
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($model->features->isNotEmpty())
        <section class="sec-tight">
            <div class="wrap">
                <h2 class="vh">{{ ui('m.features') }}</h2>
                <div class="feats">
                    @foreach ($model->features as $feature)
                        <div class="feat">
                            <x-site.icon :name="$feature->icon ?: 'scale'" />
                            <div><h3>{{ $feature->tr('title') }}</h3><p>{{ $feature->tr('text') }}</p></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="sec-tight">
        <div class="wrap m-two">
            <div>
                <h2 style="margin-bottom:10px">{{ ui('m.specs') }}</h2>
                @forelse ($specGroups as $group => $specs)
                    <details class="acc spec" id="sp-{{ $model->slug }}-{{ $group }}" @if ($loop->first) open @endif>
                        <summary>{{ ui('grp.'.$group) }}</summary>
                        <div class="acc-body" style="max-width:none">
                            <table class="spec-t">
                                @foreach ($specs as $spec)
                                    <tr><th scope="row">{{ ui('spec.'.$spec->key) }}</th><td><x-site.spec-value :value="$spec->value" /></td></tr>
                                @endforeach
                            </table>
                        </div>
                    </details>
                @empty
                    <p class="tbc">{{ ui('val.tbc') }}</p>
                @endforelse
                @if ($sourceUrl !== '')
                    <p class="src">{{ ui('m.src') }} <a href="{{ $sourceUrl }}" rel="noopener nofollow" target="_blank" style="overflow-wrap:anywhere">{{ preg_replace('#^https?://(www\.)?#', '', $sourceUrl) }}</a></p>
                @endif
            </div>

            <div>
                <h2 style="font-size:1.7rem;margin-bottom:12px">{{ ui('m.downloads') }}</h2>
                @if ($documents->isNotEmpty())
                    <ul class="files">
                        @foreach ($documents as $document)
                            <x-site.file-row :document="$document" />
                        @endforeach
                    </ul>
                @else
                    <p class="muted">{{ ui('site.m.nofiles') }}</p>
                @endif
                <p class="small" style="margin-top:10px"><a class="link" href="{{ lroute('knowledge') }}" id="m-kb">{{ ui('m.allfiles') }}</a></p>

                {{-- POS software logos supplied in the admin panel (setting "trust"); nothing until there are some. --}}
                @php $posLogos = \App\Support\Trust::all()['pos']; @endphp
                @if ($posLogos)
                    <h2 style="font-size:1.7rem;margin:32px 0 6px">{{ ui('m.works') }}</h2>
                    <p class="small muted">{{ ui('m.works.note') }}</p>
                    <ul class="slots slots-logos" id="m-works" style="list-style:none;padding:0">
                        @foreach ($posLogos as $logo)
                            <li class="slot"><img src="{{ asset($logo['path']) }}" alt="{{ $logo['name'] }}" title="{{ $logo['name'] }}" loading="lazy"></li>
                        @endforeach
                    </ul>
                @endif
                <p class="small" style="margin-top:10px"><a class="link" href="{{ lroute('dealers', ['tab' => 'integrator']) }}" id="m-integrators">{{ ui('m.integrators') }}</a></p>
            </div>
        </div>
    </section>

    <x-site.trust compact />

    @if ($similar->isNotEmpty())
        <section class="sec-tight">
            <div class="wrap">
                <h2 style="font-size:1.7rem;margin-bottom:16px">{{ ui('m.similar') }}</h2>
                <div class="cards">
                    @foreach ($similar as $other)
                        <x-site.model-card :model="$other" where="sim" wire:key="sim-{{ $other->slug }}" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="sec-tight">
        <div class="wrap">
            <div class="buy-band">
                <div><h2 style="font-size:1.9rem">{{ ui('m.buy.h', ['name' => $model->name]) }}</h2><p>{{ ui('site.buy.lead') }}</p></div>
                <a class="btn btn-p" href="{{ lroute('dealers') }}" id="m-cta2">{{ ui('cta.dealer') }}</a>
            </div>
        </div>
    </section>
</div>
