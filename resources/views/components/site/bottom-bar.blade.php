{{-- Fixed phone bottom bar: Catalog, Dealers, Support, Call (with a call popover). Hidden above 760px by CSS. --}}
@php

    $page = \App\Support\Locales::currentPage();
    $current = in_array($page, ['model', 'compare'], true) ? 'catalog' : $page;

    $c = \App\Support\RsgContacts::confirmed();

    $icons = [
        'catalog' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'dealers' => '<path d="M12 22s7-6.5 7-12a7 7 0 0 0-14 0c0 5.500 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
        'knowledge' => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 21V5M9 8h6"/>',
        'call' => '<path d="M5 4h4l2 5-2.500 1.500a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A15 15 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
    ];
    $links = [
        'catalog' => [lroute('catalog'), 'nav.catalog'],
        'dealers' => [lroute('dealers'), 'nav.dealers'],
        'knowledge' => [lroute('knowledge'), 'bar.support'],
    ];
@endphp
<div x-data="{ open: false }" x-on:keydown.escape.window="open = false">
    <nav class="bar" aria-label="{{ ui('bar.label') }}">
        @foreach ($links as $id => [$url, $key])
            <a href="{{ $url }}" id="bar-{{ $id }}" @if ($current === $id) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true">{!! $icons[$id] !!}</svg><span>{{ ui($key) }}</span>
            </a>
        @endforeach
        <button type="button" id="bar-call" aria-controls="callpop" aria-expanded="false" x-bind:aria-expanded="open.toString()" x-on:click="open = ! open">
            <svg viewBox="0 0 24 24" aria-hidden="true">{!! $icons['call'] !!}</svg><span>{{ ui('bar.call') }}</span>
        </button>
    </nav>
    <div class="callpop" id="callpop" role="dialog" aria-label="{{ ui('bar.call') }}" x-show="open" x-cloak x-on:click.outside="if (! $event.target.closest('#bar-call')) open = false">
        <button type="button" class="callpop-x" id="callpop-x" aria-label="{{ ui('a11y.close') }}" x-on:click="open = false">×</button>
        <span class="small muted">{{ ui('call.title') }}</span>
        @if ($c)
            <b class="num">{{ $c['phone'] }}</b>
            @if ($c['telegram'] !== '')
                <span class="small">Telegram: <a href="https://t.me/{{ $c['telegram'] }}" rel="noopener">{{ '@'.$c['telegram'] }}</a></span>
            @endif
            <a class="btn btn-s" id="callpop-tel" href="tel:{{ $c['tel'] }}">{{ ui('call.btn', ['phone' => $c['phone']]) }}</a>
        @else
            <p style="margin-top:6px"><x-site.contact /></p>
            <a class="btn btn-p" id="callpop-dealers" href="{{ lroute('dealers') }}">{{ ui('cta.dealer') }}</a>
        @endif
    </div>
</div>
