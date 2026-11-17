{{-- Seven-segment readout digits (decorative; the wrapper carries the text alternative). <x-site.seg7 value="1.250" /> --}}
@props(['value' => ''])
@php
    $segmap = ['0' => 'abcdef', '1' => 'bc', '2' => 'abged', '3' => 'abgcd', '4' => 'fgbc', '5' => 'afgcd', '6' => 'afgedc', '7' => 'abc', '8' => 'abcdefg', '9' => 'abcdfg', '-' => 'g', ' ' => ''];
    $segs = [['a', 3, 0, 12, 4], ['b', 14, 3, 4, 14], ['c', 14, 19, 4, 14], ['d', 3, 32, 12, 4], ['e', 0, 19, 4, 14], ['f', 0, 3, 4, 14], ['g', 3, 16, 12, 4]];
    $chars = [];
    foreach (str_split((string) $value) as $ch) {
        if ($ch === '.' && $chars) {
            $chars[count($chars) - 1]['dp'] = true;
        } else {
            $chars[] = ['c' => $ch, 'dp' => false];
        }
    }
    $w = 24;
    $out = '';
    foreach ($chars as $n => $o) {
        $on = $segmap[$o['c']] ?? '';
        $out .= '<g transform="translate('.($n * $w + 4).',2) skewX(-5)">';
        foreach ($segs as $s) {
            $out .= '<rect class="'.(str_contains($on, $s[0]) ? 'on' : 'off').'" x="'.$s[1].'" y="'.$s[2].'" width="'.$s[3].'" height="'.$s[4].'" rx="1.6"/>';
        }
        $out .= ($o['dp'] ? '<rect class="on" x="19.5" y="32" width="4" height="4" rx="1"/>' : '').'</g>';
    }
@endphp
<svg class="seg" viewBox="0 0 {{ count($chars) * $w + 4 }} 40" aria-hidden="true">{!! $out !!}</svg>
