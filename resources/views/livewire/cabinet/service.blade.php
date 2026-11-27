<section class="pb-cab-sec" id="cab-service" aria-labelledby="cab-service-h">
    <h2 id="cab-service-h">{{ ui('cab.svc.h') }}</h2>
    <p class="pb-muted">{{ ui('cab.svc.lead') }}</p>

    <div class="pb-panel pb-cab-desk">
        <form class="pb-sn-form" id="cab-find" wire:submit="find" novalidate>
            <div class="pb-field">
                <label for="cab-serial">{{ ui('sn.label') }}</label>
                <input class="pb-inp" type="text" id="cab-serial" wire:model="serial" maxlength="64" required
                       autocomplete="off" autocapitalize="characters" spellcheck="false"
                       @error('serial') aria-invalid="true" aria-describedby="cab-serial-err" @enderror>
                <span class="pb-err" id="cab-serial-err" role="alert">@error('serial'){{ $message }}@enderror</span>
            </div>
            <button type="submit" class="pb-btn" id="cab-find-btn" wire:loading.attr="disabled" wire:target="find">{{ ui('cab.svc.find') }}</button>
        </form>

        @if ($checked !== '')
            <div class="pb-done pb-sn-result" id="cab-scale" tabindex="-1" role="status">
                @if (! $scale)
                    <p id="cab-scale-none">{{ ui('sn.none', ['serial' => $checked]) }}</p>
                @else
                    @php $state = $scale->warrantyState(); @endphp
                    <h3 class="pb-num">{{ $scale->serial }}</h3>
                    <p class="pb-sn-ok">{{ ui('sn.official') }}</p>
                    <dl class="pb-sn-facts">
                        <dt>{{ ui('sn.model') }}</dt>
                        <dd>{{ $scale->scaleModel->name }}</dd>
                        <dt>{{ ui('sn.w.months') }}</dt>
                        <dd>{{ ui('sn.w.months.v', ['n' => $months]) }}</dd>
                        @if ($state !== 'none')
                            <dt>{{ ui('sn.w.from') }}</dt>
                            <dd class="pb-num">{{ $scale->warrantyStart()->format('d.m.Y') }}</dd>
                            <dt>{{ ui('sn.w.to') }}</dt>
                            <dd class="pb-num">{{ $scale->warrantyEnd()->format('d.m.Y') }}</dd>
                        @endif
                    </dl>
                    <p class="pb-sn-state pb-sn-{{ $state }}" id="cab-scale-state">{{ ui('sn.w.'.$state) }}</p>

                    @if ($full)
                        <h3>{{ ui('cab.svc.history') }}</h3>
                        @if ($request?->status === \App\Models\SerialDetailRequest::GRANTED)
                            <p class="pb-small pb-muted">{{ ui('cab.req.granted') }}</p>
                        @endif
                        <div class="pb-cab-scroll">
                            <table class="pb-cab-table" id="cab-history">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ ui('cab.col.date') }}</th>
                                        <th scope="col">{{ ui('cab.col.event') }}</th>
                                        <th scope="col">{{ ui('cab.col.details') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="pb-num">{{ $scale->imported_on->format('d.m.Y') }}</td>
                                        <th scope="row">{{ ui('cab.mv.import') }}</th>
                                        <td></td>
                                    </tr>
                                    @foreach ($scale->movements as $movement)
                                        <tr wire:key="movement-{{ $movement->id }}">
                                            <td class="pb-num">{{ $movement->happened_on->format('d.m.Y') }}</td>
                                            <th scope="row">{{ ui('cab.mv.'.$movement->type) }}</th>
                                            <td>
                                                @if ($movement->partner)
                                                    <div>{{ $movement->partner->tr('name') }}</div>
                                                @endif
                                                @if ($movement->serviceCase)
                                                    <div>{{ $movement->serviceCase->tr('title') }}</div>
                                                @endif
                                                @if ($movement->note)
                                                    <div class="pb-small pb-muted">{{ $movement->note }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="pb-note" id="cab-limited" style="margin-top:16px">{{ ui('cab.svc.limited') }}</p>

                        <div id="cab-request">
                            @if ($request?->status === 'new')
                                <p class="pb-small" id="cab-request-new" style="margin-top:12px">{{ ui('cab.req.new', ['date' => $request->created_at->format('d.m.Y')]) }}</p>
                            @else
                                @if ($request?->status === 'refused')
                                    <p class="pb-small" id="cab-request-refused" style="margin-top:12px">
                                        {{ ui('cab.req.refused', ['date' => $request->created_at->format('d.m.Y')]) }}
                                        @if ($request->answer) {{ ui('cab.req.answer', ['text' => $request->answer]) }} @endif
                                    </p>
                                @endif
                                <h3>{{ ui('cab.req.h') }}</h3>
                                <form class="pb-form pb-form-1" wire:submit="requestDetails" novalidate>
                                    <div class="pb-field">
                                        <label for="cab-request-note">{{ ui('cab.req.note') }}</label>
                                        <input class="pb-inp" type="text" id="cab-request-note" wire:model="requestNote" maxlength="500" autocomplete="off"
                                               @error('requestNote') aria-invalid="true" aria-describedby="cab-request-note-err" @enderror>
                                        <span class="pb-err" id="cab-request-note-err">@error('requestNote'){{ $message }}@enderror</span>
                                    </div>
                                    <div class="pb-field">
                                        <button type="submit" class="pb-btn pb-btn-sm" id="cab-request-btn" wire:loading.attr="disabled" wire:target="requestDetails">{{ ui('cab.req.btn') }}</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endif

                    <h3>{{ ui('cab.svc.add.h') }}</h3>
                    @if ($saved)
                        <p class="pb-note" id="cab-step-saved" role="status">{{ ui('cab.svc.add.done') }}</p>
                    @endif
                    <form class="pb-form" id="cab-step" wire:submit="addStep" novalidate style="margin-top:10px">
                        <div class="pb-field">
                            <label for="cab-step-type">{{ ui('cab.col.event') }}</label>
                            <select class="pb-inp" id="cab-step-type" wire:model="form.type"
                                    @error('form.type') aria-invalid="true" aria-describedby="cab-step-type-err" @enderror>
                                @foreach (\App\Models\SerialMovement::SERVICE_TYPES as $type)
                                    <option value="{{ $type }}">{{ ui('cab.mv.'.$type) }}</option>
                                @endforeach
                            </select>
                            <span class="pb-err" id="cab-step-type-err">@error('form.type'){{ $message }}@enderror</span>
                        </div>
                        <div class="pb-field">
                            <label for="cab-step-date">{{ ui('cab.col.date') }}</label>
                            <input class="pb-inp" type="date" id="cab-step-date" wire:model="form.happened_on" required
                                   min="{{ $scale->imported_on->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                                   @error('form.happened_on') aria-invalid="true" aria-describedby="cab-step-date-err" @enderror>
                            <span class="pb-err" id="cab-step-date-err">@error('form.happened_on'){{ $message }}@enderror</span>
                        </div>
                        <div class="pb-field pb-full">
                            <label for="cab-step-case">{{ ui('cab.svc.add.case') }}</label>
                            <select class="pb-inp" id="cab-step-case" wire:model="form.service_case_id"
                                    @error('form.service_case_id') aria-invalid="true" aria-describedby="cab-step-case-err" @enderror>
                                <option value="">{{ ui('cab.svc.add.case.none') }}</option>
                                @foreach ($cases as $case)
                                    <option value="{{ $case->id }}">{{ $case->tr('title') }}</option>
                                @endforeach
                            </select>
                            <span class="pb-err" id="cab-step-case-err">@error('form.service_case_id'){{ $message }}@enderror</span>
                        </div>
                        <div class="pb-field pb-full">
                            <label for="cab-step-note">{{ ui('cab.svc.add.note') }}</label>
                            <input class="pb-inp" type="text" id="cab-step-note" wire:model="form.note" maxlength="500" autocomplete="off"
                                   @error('form.note') aria-invalid="true" aria-describedby="cab-step-note-err" @enderror>
                            <span class="pb-err" id="cab-step-note-err">@error('form.note'){{ $message }}@enderror</span>
                        </div>
                        <div class="pb-field pb-full">
                            <button type="submit" class="pb-btn" id="cab-step-btn" wire:loading.attr="disabled" wire:target="addStep">{{ ui('cab.svc.add.btn') }}</button>
                        </div>
                    </form>
                @endif
            </div>
        @endif
    </div>

    @if ($recent->isNotEmpty())
        <h3 class="pb-cab-sub">{{ ui('cab.svc.mine.h') }}</h3>
        <div class="pb-cab-scroll">
            <table class="pb-cab-table" id="cab-recent">
                <thead>
                    <tr>
                        <th scope="col">{{ ui('sn.label') }}</th>
                        <th scope="col">{{ ui('sn.model') }}</th>
                        <th scope="col">{{ ui('cab.col.date') }}</th>
                        <th scope="col"><span class="pb-vh">{{ ui('cab.svc.open') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recent as $item)
                        <tr wire:key="recent-{{ $item->id }}">
                            <th scope="row" class="pb-num">{{ $item->serial }}</th>
                            <td>{{ $item->scaleModel->name }}</td>
                            <td class="pb-num">{{ \Illuminate\Support\Carbon::parse($item->last_service)->format('d.m.Y') }}</td>
                            <td><button type="button" class="pb-btn-q" wire:click="open('{{ $item->serial }}')">{{ ui('cab.svc.open') }}</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
