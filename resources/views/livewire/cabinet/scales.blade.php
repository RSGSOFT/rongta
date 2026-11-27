<section class="pb-cab-sec" id="cab-sales" aria-labelledby="cab-sales-h">
    <h2 id="cab-sales-h">{{ ui('cab.sales.h') }}</h2>
    <p class="pb-muted">{{ ui('cab.sales.lead') }}</p>

    @if ($total === 0)
        <p class="pb-empty" id="cab-sales-none">{{ ui('cab.sales.none') }}</p>
    @else
        <div class="pb-field pb-cab-search">
            <label for="cab-sales-search">{{ ui('cab.sales.search') }}</label>
            <input class="pb-inp" type="search" id="cab-sales-search" wire:model.live.debounce.300ms="search" maxlength="64" autocomplete="off" spellcheck="false">
        </div>

        @if ($scales->isEmpty())
            <p class="pb-empty">{{ ui('cab.sales.notfound') }}</p>
        @else
            <div class="pb-cab-scroll">
                <table class="pb-cab-table" id="cab-sales-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ ui('sn.label') }}</th>
                            <th scope="col">{{ ui('sn.model') }}</th>
                            <th scope="col">{{ ui('cab.col.handed') }}</th>
                            <th scope="col">{{ ui('cab.col.until') }}</th>
                            <th scope="col">{{ ui('sn.w.h') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($scales as $scale)
                            @php $state = $scale->warrantyState(); @endphp
                            <tr wire:key="scale-{{ $scale->id }}">
                                <th scope="row" class="pb-num">{{ $scale->serial }}</th>
                                <td>{{ $scale->scaleModel->name }}</td>
                                <td class="pb-num">{{ $scale->warrantyStart()?->format('d.m.Y') }}</td>
                                <td class="pb-num">{{ $scale->warrantyEnd()?->format('d.m.Y') }}</td>
                                <td><span class="pb-tag @if ($state === 'active') pb-tag-acc @endif">{{ ui('cab.w.'.$state) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($scales->hasPages())
                <div class="pb-btn-row" style="margin-top:14px">
                    <button type="button" class="pb-btn pb-btn-sm" wire:click="previousPage" @disabled($scales->onFirstPage())>{{ ui('cab.prev') }}</button>
                    <button type="button" class="pb-btn pb-btn-sm" wire:click="nextPage" @disabled(! $scales->hasMorePages())>{{ ui('cab.next') }}</button>
                </div>
            @endif
        @endif
    @endif
</section>
