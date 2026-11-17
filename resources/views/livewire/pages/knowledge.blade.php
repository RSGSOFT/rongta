@php
    $nav = $sections->map(fn ($s) => [$s['id'], $s['title']])->values()->all();
    $nav[] = ['kb-videos', 'kb.videos'];
    $nav[] = ['kb-faq', 'kb.faq'];
    if (! $searching) {
        $nav[] = ['kb-guide', 'kb.guide.short'];
    }
@endphp
<div class="pb-wrap">
    <div class="pb-head">
        <h1>{{ ui('kb.h') }}</h1>
        <p class="pb-lead">{{ ui('kb.lead') }}</p>
    </div>

    <div class="pb-kb-tools" role="search">
        <div class="pb-field">
            <label for="kb-search">{{ ui('kb.search') }}</label>
            <input class="pb-inp" type="search" id="kb-search" wire:model.live.debounce.300ms="q"
                   placeholder="{{ ui('kb.search.ph') }}" autocomplete="off" maxlength="80">
        </div>
        <div class="pb-field">
            <label for="kb-model">{{ ui('kb.pick') }}</label>
            <select class="pb-inp" id="kb-model" wire:model.live="model">
                <option value="">{{ ui('kb.pick.all') }}</option>
                @foreach ($models as $m)
                    <option value="{{ $m->slug }}">{{ $m->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <nav class="pb-kb-nav" aria-label="{{ ui('kb.nav') }}">
        @foreach ($nav as [$id, $key])
            <a class="pb-link" href="#{{ $id }}" id="kbnav-{{ $id }}" wire:key="kbnav-{{ $id }}">{{ ui($key) }}</a>
        @endforeach
    </nav>

    <div id="kb-results">
        <p class="pb-count" style="margin-top:8px">
            <span id="kb-count" aria-live="polite">{{ $filtering ? ui('kb.found', ['n' => $total]) : '' }}</span>
            @if ($filtering)
                <button type="button" class="pb-btn-q" id="kb-reset" wire:click="resetSearch">{{ ui('kb.reset') }}</button>
            @endif
        </p>

        @foreach ($sections as $type => $s)
            <section class="pb-kb-sec" id="{{ $s['id'] }}" tabindex="-1" wire:key="sec-{{ $type }}">
                <h2>{{ ui($s['title']) }} <span>{{ $s['documents']->count() }}</span></h2>
                <p class="pb-muted" style="margin-bottom:14px;max-width:70ch">{{ ui($s['description']) }}</p>
                @if ($s['documents']->isNotEmpty())
                    <ul class="files pb-files">
                        @foreach ($s['documents'] as $document)
                            <x-site.file-row :document="$document" wire:key="doc-{{ $document->id }}" />
                        @endforeach
                    </ul>
                @else
                    <p class="pb-empty">{{ ui('kb.none') }}</p>
                @endif
            </section>
        @endforeach

        <section class="pb-kb-sec" id="kb-videos" tabindex="-1">
            <h2>{{ ui('kb.videos') }} <span>{{ $videos->count() }}</span></h2>
            @if ($videos->isNotEmpty())
                <div class="pb-videos">
                    @foreach ($videos as $video)
                        <div class="pb-video {{ $video->url ? '' : 'pb-video-soon' }}" wire:key="video-{{ $video->id }}">
                            @if ($video->url)
                                <a class="pb-video-frame" href="{{ $video->url }}" target="_blank" rel="noopener"
                                   aria-label="{{ ui('kb.video.watch') }}: {{ $video->tr('title') }}">
                                    <div><x-site.icon name="play" />{{ ui('kb.video.watch') }}</div>
                                </a>
                            @else
                                <div class="pb-video-frame"><div><x-site.icon name="play" />{{ ui('kb.soon') }}</div></div>
                            @endif
                            <b>{{ $video->tr('title') }}</b>
                            @if ($video->family)
                                <span class="pb-small pb-muted">{{ $video->family->tr('name') }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="pb-empty">{{ ui('kb.none') }}</p>
            @endif
        </section>

        <section class="pb-kb-sec" id="kb-faq" tabindex="-1">
            <h2>{{ ui('kb.faq') }} <span>{{ $faqCount }}</span></h2>
            @forelse ($faq as $topic)
                <div class="pb-faq-topic" wire:key="faq-topic-{{ $topic->id }}">
                    <h3>{{ $topic->tr('name') }}</h3>
                    @foreach ($topic->items as $item)
                        {{-- The key changes with the search state so matches re-render opened. --}}
                        <details class="pb-acc" id="faq-{{ $item->id }}" wire:key="faq-{{ $item->id }}-{{ $searching ? 'o' : 'c' }}" @if ($searching) open @endif>
                            <summary>{{ $item->tr('question') }}</summary>
                            <div class="pb-acc-body">{{ $item->tr('answer') }}</div>
                        </details>
                    @endforeach
                </div>
            @empty
                <p class="pb-empty">{{ ui('kb.none') }}</p>
            @endforelse
        </section>

        @if (! $searching && ($guide->isNotEmpty() || $checks->isNotEmpty()))
            <section class="pb-kb-sec" id="kb-guide" tabindex="-1">
                <h2>{{ ui('kb.guide') }}</h2>
                <p class="pb-muted" style="max-width:70ch">{{ ui('kb.guide.lead') }}</p>
                @if ($guide->isNotEmpty())
                    <div class="pb-guide">
                        @foreach ($guide as $entry)
                            <div class="pb-guide-col {{ $entry->family?->isAurora() ? 'pb-guide-night' : '' }}" wire:key="guide-{{ $entry->id }}">
                                <h3>{{ $entry->family?->tr('name') }}</h3>
                                <dl>
                                    @foreach (['does', 'fits', 'limit', 'cost', 'peak'] as $row)
                                        @if ($entry->tr($row) !== '')
                                            <dt>{{ ui('g.'.$row) }}</dt>
                                            <dd>{{ $entry->tr($row) }}</dd>
                                        @endif
                                    @endforeach
                                </dl>
                                @if ($entry->family)
                                    <p style="margin-top:auto;padding-top:10px">
                                        <a class="pb-link" href="{{ lroute('catalog', ['family' => $entry->family->slug]) }}" id="guide-{{ $entry->family->slug }}">{{ ui('g.see') }}</a>
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                @if ($checks->isNotEmpty())
                    <h3 style="margin-top:28px">{{ ui('g.check') }}</h3>
                    <ul class="pb-checklist">
                        @foreach ($checks as $check)
                            <li wire:key="check-{{ $check->id }}">{{ $check->tr('text') }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif
    </div>
</div>
