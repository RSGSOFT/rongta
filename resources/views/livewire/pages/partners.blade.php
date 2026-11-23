<div class="pb-wrap">
    <div class="pb-head">
        <h1>{{ ui('pp.h') }}</h1>
        <p class="pb-lead">{{ ui('ps.lead') }}</p>
    </div>

    {{-- Advantages and terms come from the admin panel. A block with no published lines is left out. --}}
    @if ($advantages->isNotEmpty())
        <section class="pb-kb-sec" id="partner-advantages">
            <h2>{{ ui('pp.adv.h') }}</h2>
            <ul class="pb-points">
                @foreach ($advantages as $point)
                    <li class="pb-point" wire:key="adv-{{ $point->id }}">
                        <h3>{{ $point->tr('title') }}</h3>
                        @if ($point->tr('body') !== '')
                            <p>{{ $point->tr('body') }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($terms->isNotEmpty())
        <section class="pb-kb-sec" id="partner-terms">
            <h2>{{ ui('pp.terms.h') }}</h2>
            <ol class="pb-terms">
                @foreach ($terms as $point)
                    <li wire:key="term-{{ $point->id }}">
                        <div>
                            <h3>{{ $point->tr('title') }}</h3>
                            @if ($point->tr('body') !== '')
                                <p>{{ $point->tr('body') }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    <section class="pb-sec" id="partner-form" tabindex="-1">
        <div class="pb-form-two">
            <div>
                <h2>{{ ui('pf.h') }}</h2>
                <p class="pb-lead" style="margin-top:12px">{{ ui('pf.lead') }}</p>
            </div>

            <div class="pb-panel" id="pf-wrap">
                @if ($sent)
                    <div class="pb-done" id="pf-done" tabindex="-1" role="status">
                        <h3>{{ ui('pf.sent.h') }}</h3>
                        <p>{{ ui('pf.sent.p') }}</p>
                        @if ($summary)
                            <p class="pb-small pb-muted" style="margin-top:10px">{{ ui('pf.done.sum', [
                                'company' => $summary['company'],
                                'type' => ui($summary['type'] === 'integrator' ? 'pf.type.int' : 'pf.type.res'),
                                'region' => $summaryRegion ? $summaryRegion->tr('name') : ui('pf.region.any'),
                            ]) }}</p>
                        @endif
                        <button type="button" class="pb-btn pb-btn-sm" id="pf-again" wire:click="again" style="margin-top:14px">{{ ui('pf.again') }}</button>
                    </div>
                @else
                    <form class="pb-form" id="partner-form-el" wire:submit="submitRequest" novalidate>
                        @foreach ([
                            ['company', 'pf.company', 'text', 'organization', null],
                            ['contact_name', 'pf.name', 'text', 'name', null],
                            ['phone', 'pf.phone', 'tel', 'tel', 'tel'],
                        ] as [$field, $labelKey, $inputType, $autocomplete, $inputmode])
                            <div class="pb-field">
                                <label for="pf-{{ $field }}">{{ ui($labelKey) }}</label>
                                <input class="pb-inp" type="{{ $inputType }}" id="pf-{{ $field }}" wire:model="{{ $field }}"
                                       autocomplete="{{ $autocomplete }}" maxlength="255" required
                                       @if ($inputmode) inputmode="{{ $inputmode }}" @endif
                                       @error($field) aria-invalid="true" aria-describedby="pf-{{ $field }}-err" @enderror>
                                <span class="pb-err" id="pf-{{ $field }}-err">@error($field){{ $message }}@enderror</span>
                            </div>
                        @endforeach

                        <div class="pb-field">
                            <label for="pf-region">{{ ui('dl.region') }}</label>
                            <select class="pb-inp" id="pf-region" wire:model="form_region"
                                    @error('form_region') aria-invalid="true" aria-describedby="pf-region-err" @enderror>
                                <option value="">{{ ui('pf.region.any') }}</option>
                                @foreach ($regions as $r)
                                    <option value="{{ $r->slug }}">{{ $r->tr('name') }}</option>
                                @endforeach
                            </select>
                            <span class="pb-err" id="pf-region-err">@error('form_region'){{ $message }}@enderror</span>
                        </div>

                        <div class="pb-field pb-full">
                            <label for="pf-type">{{ ui('pf.type') }}</label>
                            <select class="pb-inp" id="pf-type" wire:model="type"
                                    @error('type') aria-invalid="true" aria-describedby="pf-type-err" @enderror>
                                <option value="reseller">{{ ui('pf.type.res') }}</option>
                                <option value="integrator">{{ ui('pf.type.int') }}</option>
                            </select>
                            <span class="pb-err" id="pf-type-err">@error('type'){{ $message }}@enderror</span>
                        </div>

                        <div class="pb-field pb-full">
                            <label for="pf-msg">{{ ui('pf.msg') }}</label>
                            <textarea class="pb-inp" id="pf-msg" rows="3" maxlength="2000" wire:model="message"
                                      @error('message') aria-invalid="true" aria-describedby="pf-msg-err" @enderror></textarea>
                            <span class="pb-err" id="pf-msg-err">@error('message'){{ $message }}@enderror</span>
                        </div>

                        {{-- Honeypot: people never see or reach this field. --}}
                        <div class="pb-hp" aria-hidden="true">
                            <label for="pf-website">Website</label>
                            <input type="text" id="pf-website" wire:model="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="pb-field pb-full">
                            @error('form')
                                <p class="pb-note" id="pf-form-error" tabindex="-1" role="alert">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="pb-btn" id="pf-submit" wire:loading.attr="disabled" wire:target="submitRequest">{{ ui('pf.submit') }}</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </section>
</div>
