@php
    $sets = [
        'family' => [ui('f.family'), ['all' => ui('f.all')] + $families->mapWithKeys(fn ($f) => [$f->slug => $f->tr('name')])->all()],
        'cap' => [ui('f.cap'), ['all' => ui('f.any'), '15' => ui('val.cap.15'), '30' => ui('val.cap.30')]],
        'label' => [ui('f.print'), ['all' => ui('f.any'), 'yes' => ui('f.print.yes'), 'no' => ui('f.print.no')]],
        'conn' => [ui('f.conn'), ['all' => ui('f.any')] + $connNames],
    ];
@endphp
<div>
    <div class="wrap">
        <div class="page-head">
            <h1>{{ ui('cat.h') }}</h1>
            <p class="lead">{{ ui('cat.lead') }}</p>
        </div>

        <div class="cat-layout">
            <form class="filters" id="filters" aria-label="{{ ui('f.label') }}" wire:submit.prevent>
                <div class="filters-grid">
                    @foreach ($sets as $name => [$legend, $options])
                        <fieldset>
                            <legend>{{ $legend }}</legend>
                            <div class="chips">
                                @foreach ($options as $value => $text)
                                    <label class="chip" for="f-{{ $name }}-{{ $value }}" wire:key="f-{{ $name }}-{{ $value }}">
                                        <input type="radio" name="f-{{ $name }}" id="f-{{ $name }}-{{ $value }}" value="{{ $value }}" wire:model.live="{{ $name }}">
                                        <span>{{ $text }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
                <p class="small muted">{{ ui('f.connnote') }}</p>
            </form>

            <div id="cat-results">
                <div class="cat-count">
                    <span id="cat-count" aria-live="polite">{{ ui('cat.count', ['n' => $count, 'all' => $total]) }}</span>
                    @if ($filtered)
                        <button type="button" class="btn-q small" id="f-reset" wire:click="resetFilters">{{ ui('cat.reset') }}</button>
                    @endif
                </div>

                @if ($cap !== 'all')
                    <p class="note" style="margin-bottom:18px">{{ ui('cat.capnote') }}</p>
                @endif

                @if ($count === 0)
                    <div class="empty">
                        <p>{{ ui('cat.empty') }}</p>
                        <button type="button" class="btn btn-s btn-sm" id="f-reset2" wire:click="resetFilters">{{ ui('cat.reset') }}</button>
                    </div>
                @else
                    @foreach ($groups as $group)
                        <section class="fam-group" aria-labelledby="fh-{{ $group['family']->slug }}" wire:key="grp-{{ $group['family']->slug }}">
                            <div @class(['fam-head', 'night' => $group['family']->isAurora()])>
                                <h2 id="fh-{{ $group['family']->slug }}">{{ $group['family']->tr('name') }}</h2>
                                <p>{{ $group['family']->tr('line') }}</p>
                            </div>
                            <div class="cards">
                                @foreach ($group['models'] as $model)
                                    <x-site.model-card :model="$model" where="cat" :cap="$cap" wire:key="card-{{ $model->slug }}" />
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
