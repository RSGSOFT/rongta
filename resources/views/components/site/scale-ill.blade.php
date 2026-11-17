{{--
    Scale picture. Shows the model's real photo when it has one; otherwise the schematic SVG
    drawing ported from the approved prototype (prototype-src/app1.js, ILL).

    <x-site.scale-ill :model="$scaleModel" />
    <x-site.scale-ill :model="$scaleModel" view-box="84 0 144 120" />      detail crop (always the drawing)
    <x-site.scale-ill kind="pole" :opts="['head' => 'wide', 'blank' => true]" name="RLS1000/RLS1100A" />
--}}
@props(['model' => null, 'viewBox' => null, 'kind' => null, 'opts' => [], 'name' => null, 'photo' => true])
@php
    $kind = $kind ?? $model?->kind ?? 'comm';
    $o = array_merge((array) ($model?->illustration ?? []), (array) $opts);
    $name = $name ?? $model?->name ?? '';

    $image = null;
    if ($photo && $viewBox === null && $model && filled($model->image)) {
        $path = (string) $model->image;
        $image = preg_match('#^(https?:)?//#', $path) || str_starts_with($path, '/')
            ? $path
            : (is_file(public_path($path)) ? asset($path) : asset('storage/'.ltrim($path, '/')));
    }

    $svg = '';
    if ($image === null) {
        $shadow = fn ($cx, $w, $y = 184) => '<ellipse class="i-shadow" cx="'.$cx.'" cy="'.$y.'" rx="'.($w / 2).'" ry="6"/>';
        $platter = fn ($x, $y, $w, $baseTop) => '<rect class="i-neck" x="'.($x + $w / 2 - 16).'" y="'.($y + 6).'" width="32" height="'.($baseTop - $y - 5).'"/><rect class="i-steel" x="'.$x.'" y="'.$y.'" width="'.$w.'" height="8" rx="4"/>';
        $disp = fn ($x, $y, $w, $h, $txt = '', $fs = 10) => '<rect class="i-dark" x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" rx="3"/>'
            .($txt !== '' ? '<text class="i-led" x="'.($x + $w - 5).'" y="'.round($y + $h / 2 + $fs * 0.36, 2).'" text-anchor="end" font-size="'.$fs.'">'.$txt.'</text>' : '');
        $keys = function ($x, $y, $cols, $rows, $s, $gap) {
            $out = '';
            for ($r = 0; $r < $rows; $r++) {
                for ($c = 0; $c < $cols; $c++) {
                    $out .= '<rect class="i-key" x="'.($x + $c * ($s + $gap)).'" y="'.($y + $r * ($s + $gap)).'" width="'.$s.'" height="'.$s.'" rx="1.5"/>';
                }
            }

            return $out;
        };
        $paper = function ($x, $y, $w, $h) {
            $out = '<rect class="i-paper" x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" rx="1.5"/>';
            for ($i = 0; $i < 5; $i++) {
                $out .= '<line class="i-bar" x1="'.($x + 5 + $i * 3.2).'" y1="'.($y + $h - 9).'" x2="'.($x + 5 + $i * 3.2).'" y2="'.($y + $h - 3).'"/>';
            }

            return $out.'<line class="i-bar" x1="'.($x + 4).'" y1="'.($y + 5).'" x2="'.($x + $w - 5).'" y2="'.($y + 5).'"/>';
        };
        $fruit = fn ($cx, $cy) => '<circle class="i-acc" cx="'.$cx.'" cy="'.$cy.'" r="9"/><circle class="i-acc" cx="'.($cx + 17).'" cy="'.($cy + 1).'" r="8"/><circle class="i-acc" cx="'.($cx + 8).'" cy="'.($cy - 9).'" r="8"/>'
            .'<path class="i-leaf" d="M'.($cx + 8).' '.($cy - 17).'q3 -6 8 -6"/>';
        $btns = function ($x, $y, $n) {
            $out = '';
            for ($i = 0; $i < $n; $i++) {
                $out .= '<circle class="i-key" cx="'.($x + $i * 17).'" cy="'.$y.'" r="5.5"/>';
            }

            return $out;
        };
        $ui = function ($x, $y, $cols, $rows, $s, $gap, $hi) {
            $out = '';
            $n = 0;
            for ($r = 0; $r < $rows; $r++) {
                for ($c = 0; $c < $cols; $c++) {
                    $out .= '<rect class="'.($n === $hi ? 'i-uihi' : 'i-ui').'" x="'.($x + $c * ($s + $gap)).'" y="'.($y + $r * ($s + $gap)).'" width="'.$s.'" height="'.$s.'" rx="2.5"/>';
                    $n++;
                }
            }

            return $out;
        };

        $cam = ! empty($o['cam']);

        $svg = match ($kind) {
            'pole' => $shadow(120, 196).'<rect class="i-body" x="160" y="60" width="10" height="64"/>'
                .(($o['head'] ?? null) === 'box'
                    ? '<rect class="i-body" x="116" y="12" width="92" height="54" rx="6"/>'.$disp(122, 18, 80, 22, '1.250', 13).$disp(122, 44, 80, 16, '15000', 10)
                    : '<rect class="i-body" x="88" y="20" width="124" height="42" rx="6"/>'.$disp(94, 26, 112, 30, ! empty($o['blank']) ? '' : '1.250  12000  15000', 10.5))
                .'<rect class="i-body" x="38" y="130" width="164" height="48" rx="9"/>'.$keys(50, 141, 12, 3, 6, 3)
                .'<rect class="i-soft" x="166" y="139" width="28" height="30" rx="3"/>'.$paper(190, 144, 26, 20)
                .$platter(32, 110, 176, 130).(($o['fruit'] ?? true) === false ? '' : $fruit(92, 100)),

            'flat' => $shadow(120, 204).'<rect class="i-body" x="24" y="136" width="192" height="42" rx="9"/>'
                .'<path class="i-body" d="M146 136V104q0-8 8-8h54q8 0 8 8v32z"/>'.$disp(153, 103, 56, 16, '1.250', 10).$keys(154, 124, 6, 1, 6, 3)
                .$keys(36, 148, 9, 2, 6, 3).'<rect class="i-soft" x="158" y="146" width="34" height="24" rx="3"/>'.$paper(188, 149, 26, 20)
                .$platter(18, 116, 122, 136).$fruit(66, 106),

            'hang' => $shadow(120, 130, 190).'<path class="i-line" d="M120 2v16"/><rect class="i-body" x="62" y="18" width="116" height="76" rx="9"/>'
                .$disp(70, 26, 100, 20, '1.250  15000', 10.5).$keys(73, 54, 10, 3, 6, 3).$paper(170, 60, 26, 20)
                .'<path class="i-line" d="M90 94v54M150 94v54"/>'.$fruit(110, 142)
                .'<path class="i-steel" d="M58 148h124q-6 28-62 28t-62-28z"/>',

            'c3' => $shadow(112, 180).'<path class="i-line" d="M200 158c22 0 26 14 14 20s-18 8-6 12"/><rect class="i-dark" x="204" y="186" width="16" height="9" rx="2.5"/>'
                .'<rect class="i-body" x="36" y="140" width="164" height="32" rx="9"/>'
                .'<rect class="i-lcd" x="48" y="147" width="58" height="18" rx="2"/><text class="i-lcdt" x="101" y="160.5" text-anchor="end" font-size="11">1.250</text>'.$btns(128, 156, 3)
                .$platter(30, 120, 176, 140).$fruit(84, 110),

            'c2ai' => $shadow(120, 190).'<path class="i-arm" d="M198 140V54q0-12-12-12h-28"/>'
                .'<rect class="i-dark" x="126" y="32" width="36" height="18" rx="6"/><circle class="i-dark" cx="144" cy="52" r="5"/>'
                .'<path class="i-cone" d="M144 58L70 116M144 58L162 116"/>'
                .'<rect class="i-body" x="32" y="140" width="176" height="34" rx="9"/>'.$disp(44, 147, 66, 20, '1.250', 12).$btns(134, 157, 4)
                .$platter(26, 120, 176, 140).$fruit(98, 110)
                .'<path class="i-aur" d="M82 92h-6v6M134 92h6v6M82 124h-6v-6"/>',

            'aipc' => $shadow(120, 204).'<rect class="i-body" x="150" y="100" width="16" height="56"/>'
                .'<rect class="i-body" x="66" y="10" width="158" height="98" rx="7"/><rect class="i-dark" x="73" y="17" width="144" height="84" rx="3"/>'
                .$ui(81, 25, 4, 3, 17, 5, 5).'<text class="i-led" x="210" y="40" text-anchor="end" font-size="11">1.250</text><rect class="i-ui" x="174" y="50" width="36" height="6" rx="2"/><rect class="i-ui" x="174" y="62" width="36" height="6" rx="2"/><rect class="i-uihi" x="174" y="80" width="36" height="13" rx="3"/>'
                .($cam ? '<rect class="i-dark" x="92" y="108" width="28" height="11" rx="5"/><circle class="i-dark" cx="106" cy="121" r="4"/><path class="i-cone" d="M106 126L62 138M106 126L134 138"/>' : '')
                .'<rect class="i-body" x="20" y="154" width="200" height="28" rx="9"/><rect class="i-soft" x="172" y="158" width="36" height="20" rx="3"/>'
                .(! empty($o['receipt'])
                    ? '<rect class="i-paper" x="206" y="150" width="16" height="30" rx="1.5"/><line class="i-bar" x1="210" y1="157" x2="218" y2="157"/><line class="i-bar" x1="210" y1="163" x2="218" y2="163"/><line class="i-bar" x1="210" y1="169" x2="216" y2="169"/>'
                    : $paper(202, 158, 26, 20))
                .$platter(14, 136, 146, 154).$fruit(60, 126)
                .($cam ? '<path class="i-aur" d="M46 106h-6v6M90 106h6v6"/>' : ''),

            'y2l' => $shadow(120, 204).'<rect class="i-opt" x="50" y="26" width="38" height="26" rx="3"/>'
                .'<rect class="i-body" x="148" y="92" width="70" height="90" rx="9"/><rect class="i-soft" x="158" y="126" width="50" height="9" rx="3"/>'.$paper(168, 131, 30, 26)
                .'<rect class="i-body" x="94" y="8" width="128" height="88" rx="7"/><rect class="i-dark" x="101" y="15" width="114" height="74" rx="3"/>'
                .$ui(108, 22, 3, 3, 15, 5, 4).'<text class="i-led" x="208" y="36" text-anchor="end" font-size="10">1.250</text><rect class="i-ui" x="172" y="46" width="36" height="6" rx="2"/><rect class="i-uihi" x="172" y="70" width="36" height="12" rx="3"/>'
                .'<rect class="i-dark" x="106" y="96" width="24" height="10" rx="5"/><path class="i-cone" d="M118 108L60 136M118 108L132 136"/>'
                .'<rect class="i-body" x="18" y="154" width="140" height="28" rx="9"/>'.$platter(12, 136, 134, 154).$fruit(58, 126)
                .'<path class="i-aur" d="M44 106h-6v6M88 106h6v6"/>',

            // 'comm' and any unknown kind
            default => $shadow(120, 190)
                .(! empty($o['dual']) ? '<rect class="i-body" x="142" y="84" width="62" height="30" rx="5"/>'.$disp(148, 90, 50, 16, '1.250', 10).'<rect class="i-neck" x="168" y="114" width="10" height="8"/>' : '')
                .'<rect class="i-body" x="32" y="138" width="176" height="36" rx="9"/>'.$disp(44, 146, 66, 20, '1.250', 12).$btns(134, 156, 4)
                .$platter(26, 118, 188, 138).$fruit(84, 108),
        };
    }
@endphp
@if ($image !== null)
    <img {{ $attributes->class('ill-photo') }} src="{{ $image }}" alt="{{ $name }}" loading="lazy">
@else
    <svg {{ $attributes->class('ill') }} viewBox="{{ $viewBox ?? '0 0 240 210' }}" role="img" aria-label="{{ ui('ill.alt', ['name' => $name]) }}">{!! $svg !!}</svg>
@endif
