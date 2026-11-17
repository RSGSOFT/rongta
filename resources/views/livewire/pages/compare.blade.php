<div>
    <div class="wrap">
        <div class="page-head">
            <p class="crumb"><a href="{{ lroute('catalog') }}">{{ ui('nav.catalog') }}</a> / {{ ui('nav.compare') }}</p>
            <h1>{{ ui('cmp.h') }}</h1>
            {{-- The limit is 4 models, 3 on phones; the number is corrected in the browser. --}}
            <p class="lead">{!! str_replace('{n}', '<span x-data x-text="$store.compare.limit()">'.\App\Livewire\Pages\Compare::MAX.'</span>', e(ui('cmp.lead'))) !!}</p>
        </div>

        @if ($models->isEmpty())
            <div class="empty">
                <p>{{ ui('cmp.empty') }}</p>
                <a class="btn btn-s btn-sm" href="{{ lroute('catalog') }}" id="cmp-to-cat">{{ ui('cta.catalog') }}</a>
            </div>
        @else
            <div class="cmp-tools">
                <label class="cmp" for="cmp-diff"><input type="checkbox" id="cmp-diff" wire:model.live="onlyDiff"> {{ ui('cmp.onlydiff') }}</label>
                <a class="link small" href="{{ lroute('catalog') }}" id="cmp-addmore">{{ ui('cmp.addmore') }}</a>
            </div>

            <div class="tbl-scroll" tabindex="0" role="region" aria-label="{{ ui('cmp.h') }}">
                <table class="cmp-t">
                    <thead>
                        <tr>
                            <th scope="col"><span class="vh">{{ ui('cmp.param') }}</span></th>
                            @foreach ($models as $m)
                                <th scope="col" wire:key="col-{{ $m->slug }}">
                                    <div class="cmp-col">
                                        <span class="cmp-pic"><x-site.scale-ill :model="$m" /></span>
                                        <b><a href="{{ lroute('model', ['slug' => $m->slug]) }}" id="cmp-link-{{ $m->slug }}">{{ $m->name }}</a></b>
                                        <span class="small muted" style="font-weight:400">{{ $m->tr('type') }}</span>
                                        <button type="button" class="btn-q small" id="cmp-x-{{ $m->slug }}" style="align-self:flex-start"
                                            x-data x-on:click="$store.compare.toggle(@js($m->slug))"
                                            aria-label="{{ ui('cmp.remove') }}: {{ $m->name }}">{{ ui('cmp.remove') }}</button>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $g => $group)
                            <tr class="grp" wire:key="g-{{ $g }}"><th colspan="{{ $models->count() + 1 }}" scope="colgroup">{{ $group['label'] }}</th></tr>
                            @foreach ($group['rows'] as $r => $row)
                                <tr @class(['diff' => $row['diff']]) wire:key="r-{{ $g }}-{{ $r }}">
                                    <th scope="row">{{ $row['label'] }}</th>
                                    @foreach ($row['cells'] as $cell)
                                        <td>@if (array_key_exists('text', $cell)){{ $cell['text'] }}@else<x-site.spec-value :value="$cell['value']" />@endif</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="legend">
                <span><i></i>{{ ui('cmp.legend.diff') }}</span>
                <span>{{ ui('cmp.legend.dash') }}</span>
                <span>{{ ui('cmp.legend.tbc') }}</span>
            </p>

            <div class="buy-band" style="margin-top:32px">
                <div><h3>{{ ui('buy.h') }}</h3><p>{{ ui('site.buy.lead') }}</p></div>
                <a class="btn btn-p" href="{{ lroute('dealers') }}" id="cmp-cta">{{ ui('cta.dealer') }}</a>
            </div>
        @endif

        <div style="height:48px"></div>
    </div>
</div>
