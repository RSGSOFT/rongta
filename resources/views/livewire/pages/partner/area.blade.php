@php
    // What each partner type gets: [icon, title key, text key].
    $gets = $isIntegrator
        ? [
            ['doc', 'pa.i.sdk', 'pa.i.sdk.d'],
            ['scale', 'pa.i.test', 'pa.i.test.d'],
            ['person', 'pa.i.contact', 'pa.i.contact.d'],
            ['list', 'pa.i.listing', 'pa.i.listing.d'],
        ]
        : [
            ['list', 'pa.res.listing', 'pa.res.listing.d'],
            ['doc', 'pa.res.docs', 'pa.res.docs.d'],
            ['person', 'pa.res.contact', 'pa.res.contact.d'],
        ];
@endphp
<div class="pb-wrap">
    <div class="pb-head">
        <div class="pb-p-top">
            <h1>{{ ui('pa.h') }}</h1>
            <button type="button" class="pb-btn pb-btn-sm" id="logout" wire:click="logout">{{ ui('pa.logout') }}</button>
        </div>
        <p class="pb-lead">{{ ui('pa.hello', ['name' => $user->name]) }}</p>
        <div class="pb-tags" style="margin-top:12px">
            <span class="pb-tag pb-tag-acc" id="partner-type">{{ ui($isIntegrator ? 'pf.type.int' : 'pf.type.res') }}</span>
        </div>
    </div>

    <div class="pb-p-grid" id="{{ $isIntegrator ? 'view-integrator' : 'view-reseller' }}">
        <div class="pb-p-card">
            <h3>{{ ui($isIntegrator ? 'pa.i.h' : 'pa.res.h') }}</h3>
            <ul class="pb-gets">
                @foreach ($gets as [$icon, $titleKey, $textKey])
                    <li>
                        <x-site.icon :name="$icon" />
                        <div><b>{{ ui($titleKey) }}</b><span>{{ ui($textKey) }}</span></div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="pb-p-card">
            <h3>{{ ui('pa.i.terms.h') }}</h3>
            <dl class="pb-terms">
                <div>
                    <dt>{{ ui('pa.terms.label') }}</dt>
                    <dd>{{ ui('pa.terms.onreq') }}</dd>
                </div>
            </dl>
            <p class="pb-small pb-muted" style="margin-top:12px">{{ ui('pa.terms.note') }}</p>

            <div class="pb-btn-row" style="margin-top:18px">
                @if ($isIntegrator)
                    <a class="pb-btn pb-btn-sm" href="{{ lroute('knowledge') }}#kb-sdk" id="pa-sdk">{{ ui('pa.i.tosdk') }}</a>
                    <a class="pb-btn pb-btn-sm" href="{{ lroute('dealers', ['tab' => 'integrator']) }}" id="pa-listing">{{ ui('pa.todealers') }}</a>
                @else
                    <a class="pb-btn pb-btn-sm" href="{{ lroute('knowledge') }}" id="pa-kb">{{ ui('pa.tokb') }}</a>
                    <a class="pb-btn pb-btn-sm" href="{{ lroute('dealers') }}" id="pa-listing">{{ ui('pa.todealers') }}</a>
                @endif
            </div>

            <div style="margin-top:18px">
                <p class="pb-small" style="font-weight:600">{{ ui($isIntegrator ? 'pa.i.tech' : 'pa.contact') }}</p>
                <x-site-b.rsg-contact style="margin-top:4px" />
            </div>
        </div>
    </div>
</div>
