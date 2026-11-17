{{--
  Data table in a white panel; scrolls sideways inside itself on narrow screens.
  Slots: head (the <th> cells), default (the <tr> rows), optional toolbar (search / filters above),
  optional footer (pagination below). The "empty" text is shown when :count="0" is passed.
--}}
@props(['count' => null, 'empty' => 'Ничего не найдено.'])
<div {{ $attributes->class('mb-5 rounded-l border border-line bg-label shadow-card') }}>
    @isset($toolbar)
        <div class="flex flex-wrap items-end gap-3 border-b border-line p-3 sm:px-4">{{ $toolbar }}</div>
    @endisset
    <div class="overflow-x-auto">
        <table class="adm-table">
            @isset($head)
                <thead><tr>{{ $head }}</tr></thead>
            @endisset
            <tbody>{{ $slot }}</tbody>
        </table>
    </div>
    @if ($count === 0)
        <p class="px-4 py-8 text-center text-steel">{{ $empty }}</p>
    @endif
    @isset($footer)
        <div class="border-t border-line px-3 py-2.5 sm:px-4">{{ $footer }}</div>
    @endisset
</div>
