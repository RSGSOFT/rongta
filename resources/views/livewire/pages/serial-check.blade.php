<div class="pb-wrap">
    <div class="pb-head">
        <h1>{{ ui('sn.h') }}</h1>
        <p class="pb-lead">{{ ui('sn.lead') }}</p>
    </div>

    <div class="pb-panel pb-sn">
        <form class="pb-sn-form" id="sn-form" wire:submit="check" novalidate>
            <div class="pb-field">
                <label for="sn-serial">{{ ui('sn.label') }}</label>
                <input class="pb-inp" type="text" id="sn-serial" wire:model="serial" maxlength="64" required
                       autocomplete="off" autocapitalize="characters" spellcheck="false"
                       @error('serial') aria-invalid="true" aria-describedby="sn-serial-err" @enderror>
                <span class="pb-err" id="sn-serial-err" role="alert">@error('serial'){{ $message }}@enderror</span>
            </div>
            <button type="submit" class="pb-btn" id="sn-check" wire:loading.attr="disabled" wire:target="check">{{ ui('sn.btn') }}</button>
        </form>

        @if ($checked !== '')
            <div class="pb-done pb-sn-result" id="sn-result" tabindex="-1" role="status">
                @if ($found)
                    @php
                        $state = $found->warrantyState();
                        $model = $found->scaleModel;
                    @endphp
                    <h2 class="num">{{ $found->serial }}</h2>
                    <p class="pb-sn-ok">{{ ui('sn.official') }}</p>
                    <dl class="pb-sn-facts">
                        <dt>{{ ui('sn.model') }}</dt>
                        <dd>
                            @if ($model->is_published)
                                <a class="pb-link" href="{{ lroute('model', ['slug' => $model->slug]) }}" id="sn-model">{{ $model->name }}</a>
                            @else
                                {{ $model->name }}
                            @endif
                        </dd>
                        <dt>{{ ui('sn.w.months') }}</dt>
                        <dd>{{ ui('sn.w.months.v', ['n' => $months]) }}</dd>
                        @if ($state !== 'none')
                            <dt>{{ ui('sn.w.from') }}</dt>
                            <dd class="num">{{ $found->warrantyStart()->format('d.m.Y') }}</dd>
                            <dt>{{ ui('sn.w.to') }}</dt>
                            <dd class="num">{{ $found->warrantyEnd()->format('d.m.Y') }}</dd>
                        @endif
                    </dl>
                    <p class="pb-sn-state pb-sn-{{ $state }}" id="sn-state">{{ ui('sn.w.'.$state) }}</p>
                    @if ($terms !== '')
                        <h3>{{ ui('sn.terms') }}</h3>
                        <p class="pb-muted">{{ $terms }}</p>
                    @endif
                @else
                    <p id="sn-none">{{ ui('sn.none', ['serial' => $checked]) }}</p>
                    <p style="margin-top:12px"><a class="pb-link" href="{{ lroute('dealers') }}">{{ ui('cta.dealer') }}</a></p>
                @endif
            </div>
        @endif
    </div>
</div>
