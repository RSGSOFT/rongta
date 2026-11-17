{{--
  Small copy of the Dealers page tile map. Data: regions (collection), tile ([col, row] of the region being edited
  or null), label (text on the edited tile), current (id to highlight in list mode, optional).
--}}
@php
    $cols = \App\Livewire\Admin\Partners\Options::MAP_COLS;
    $rows = max(3, (int) $regions->max('map_row'), $tile[1] ?? 0);
    $tile = $tile ?? null;
    $label = $label ?? '';
@endphp
<div class="overflow-x-auto">
    <div class="grid min-w-[420px] gap-1.5" role="img"
         aria-label="Схема карты регионов: {{ $cols }} колонок, занято клеток: {{ $regions->count() }}"
         style="grid-template-columns: repeat({{ $cols }}, minmax(0, 1fr)); grid-template-rows: repeat({{ $rows }}, 2.75rem)">
        @foreach ($regions as $r)
            @if ($r->map_col >= 1 && $r->map_col <= $cols)
                <div wire:key="map-{{ $r->id }}" title="{{ $r->tr('name', 'ru') }}"
                     class="flex items-center overflow-hidden rounded-m border border-line bg-label px-1.5 text-[11px] font-semibold leading-tight text-steel"
                     style="grid-column: {{ (int) $r->map_col }}; grid-row: {{ (int) $r->map_row }}">
                    <span class="truncate">{{ $r->tr('short', 'ru') ?: $r->slug }}</span>
                </div>
            @endif
        @endforeach
        @if ($tile)
            <div wire:key="map-current-{{ $tile[0] }}-{{ $tile[1] }}"
                 class="z-10 flex items-center overflow-hidden rounded-m border-2 border-accent-ink bg-accent px-1.5 text-[11px] font-bold leading-tight text-on-accent"
                 style="grid-column: {{ $tile[0] }}; grid-row: {{ $tile[1] }}">
                <span class="truncate">{{ $label !== '' ? $label : 'Этот регион' }}</span>
            </div>
        @endif
    </div>
</div>
