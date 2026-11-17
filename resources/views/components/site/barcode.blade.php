{{-- Decorative barcode for the printed-label motif. <x-site.barcode seed="rongta-hero" :n="46" /> --}}
@props(['seed' => 'rongta', 'n' => 46])
@php
    $h = 0;
    foreach (str_split((string) $seed) as $ch) {
        $h = ($h * 31 + ord($ch)) % 9973;
    }
    $x = 0;
    $out = '';
    for ($i = 0; $i < $n; $i++) {
        $h = ($h * 73 + 17) % 9973;
        $w = 1 + ($h % 3);
        if ($i % 2 === 0) {
            $out .= '<rect x="'.$x.'" y="0" width="'.$w.'" height="30"/>';
        }
        $x += $w;
    }
@endphp
<svg class="bars" viewBox="0 0 {{ $x }} 30" preserveAspectRatio="none" aria-hidden="true">{!! $out !!}</svg>
