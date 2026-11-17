{{--
    48px pictogram set from the approved prototype (prototype-src/app1.js, PICS).
    <x-site.icon name="printer" />  Unknown names fall back to "scale".
--}}
@props(['name' => 'scale'])
@php
    $pics = [
        'label' => '<rect x="8" y="6" width="28" height="36" rx="3"/><path d="M14 14h16M14 20h10"/><path class="a" d="M14 28v8M18 28v8M23 28v8M27 28v8M30 28v8"/>',
        'display' => '<rect x="5" y="8" width="24" height="16" rx="3"/><rect x="19" y="24" width="24" height="16" rx="3"/><path class="a" d="M10 16h8M25 32h10"/>',
        'printer' => '<rect x="6" y="18" width="36" height="18" rx="4"/><path d="M14 18V8h20v10"/><path class="a" d="M14 30h20v12H14z"/>',
        'net' => '<circle cx="24" cy="10" r="5"/><circle cx="9" cy="37" r="5"/><circle cx="39" cy="37" r="5"/><path class="a" d="M24 15v10M24 25L12 33M24 25l12 8"/>',
        'camera' => '<rect x="6" y="14" width="36" height="24" rx="5"/><circle class="a" cx="24" cy="26" r="7"/><path d="M16 14l3-6h10l3 6"/>',
        'touch' => '<rect x="5" y="8" width="38" height="26" rx="4"/><path d="M18 42h12"/><circle class="a" cx="24" cy="21" r="5"/>',
        'drop' => '<path d="M24 6c8 10 12 16 12 23a12 12 0 0 1-24 0c0-7 4-13 12-23z"/><path class="a" d="M18 30a6 6 0 0 0 6 6"/>',
        'keys' => '<rect x="6" y="8" width="9" height="9" rx="2"/><rect x="19.5" y="8" width="9" height="9" rx="2"/><rect x="33" y="8" width="9" height="9" rx="2"/><rect x="6" y="21.500" width="9" height="9" rx="2"/><rect class="a" x="19.5" y="21.500" width="9" height="9" rx="2"/><rect x="33" y="21.500" width="9" height="9" rx="2"/><rect x="6" y="35" width="9" height="9" rx="2"/><rect x="19.5" y="35" width="9" height="9" rx="2"/><rect x="33" y="35" width="9" height="9" rx="2"/>',
        'pos' => '<rect x="4" y="26" width="20" height="12" rx="3"/><rect x="28" y="8" width="16" height="22" rx="3"/><path class="a" d="M24 32h6q6 0 6-6"/>',
        'fold' => '<path d="M6 40h36"/><path d="M38 40V16q0-6-6-6H20"/><path class="a" d="M14 6l6 4-6 4"/>',
        'hook' => '<path d="M24 4v10"/><rect x="10" y="14" width="28" height="12" rx="3"/><path d="M16 26v8M32 26v8"/><path class="a" d="M10 34h28q-2 10-14 10T10 34z"/>',
        'usb' => '<path d="M24 42V10"/><path d="M19 15l5-7 5 7"/><path class="a" d="M24 30l-9-6v-5M24 34l9-6v-4"/><circle cx="24" cy="42" r="2.5"/>',
        'flat' => '<rect x="4" y="24" width="40" height="12" rx="4"/><path d="M8 18h32"/><path class="a" d="M44 12v30M40 16l4-4 4 4M40 38l4 4 4-4" transform="translate(-4 -3)"/>',
        'os' => '<rect x="6" y="8" width="36" height="24" rx="4"/><path d="M16 40h16M24 32v8"/><path class="a" d="M14 20h8M26 20h8"/>',
        'drawer' => '<rect x="6" y="20" width="36" height="20" rx="3"/><path d="M12 20V8h24v12"/><path class="a" d="M20 30h8"/><path d="M17 13h14"/>',
        'shield' => '<path d="M24 5l15 6v11c0 10-6 17-15 21-9-4-15-11-15-21V11z"/><path class="a" d="M17 24l5 5 9-10"/>',
        'cert' => '<rect x="8" y="5" width="32" height="38" rx="3"/><path d="M15 14h18M15 21h18"/><circle class="a" cx="24" cy="32" r="5"/>',
        'wrench' => '<path d="M30 6a10 10 0 0 0-9 14L7 34l7 7 14-14a10 10 0 0 0 13-12l-7 6-5-5z"/><path class="a" d="M10 38l2-2"/>',
        'logos' => '<rect x="5" y="10" width="16" height="12" rx="2"/><rect x="27" y="10" width="16" height="12" rx="2"/><rect x="5" y="28" width="16" height="12" rx="2"/><rect class="a" x="27" y="28" width="16" height="12" rx="2"/>',
        'scale' => '<path d="M6 16h36"/><rect x="10" y="22" width="28" height="18" rx="4"/><path class="a" d="M17 31h14"/><path d="M24 16v6"/>',
        'doc' => '<path d="M12 5h17l9 9v29H12z"/><path d="M29 5v9h9"/><path class="a" d="M18 26h14M18 33h10"/>',
        'play' => '<circle cx="24" cy="24" r="18"/><path class="af" d="M20 15l14 9-14 9z"/>',
        'person' => '<circle cx="24" cy="15" r="8"/><path d="M8 42c2-10 8-14 16-14s14 4 16 14"/><path class="a" d="M20 34l4 4 4-4"/>',
        'list' => '<path d="M16 12h26M16 24h26M16 36h26"/><circle class="a" cx="8" cy="12" r="2.5"/><circle class="a" cx="8" cy="24" r="2.5"/><circle class="a" cx="8" cy="36" r="2.5"/>',
    ];
@endphp
<svg {{ $attributes->class('pic') }} viewBox="0 0 48 48" aria-hidden="true">{!! $pics[$name] ?? $pics['scale'] !!}</svg>
