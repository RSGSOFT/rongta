{{--
    RSG phone and Telegram. App\Support\RsgContacts decides whether they are confirmed;
    until then no number is printed: a made-up number must never look real.
--}}
@php
    $contacts = \App\Support\RsgContacts::confirmed();
@endphp
<div {{ $attributes }}>
    @if ($contacts)
        <a class="pb-tel pb-num" href="tel:{{ $contacts['tel'] }}">{{ $contacts['phone'] }}</a>
        @if ($contacts['telegram'] !== '')
            <div class="pb-small pb-muted">Telegram: <a class="pb-link" href="https://t.me/{{ $contacts['telegram'] }}" rel="noopener">{{ '@'.$contacts['telegram'] }}</a></div>
        @endif
    @else
        <span class="pb-small pb-muted">{{ ui('dl.contact.tbc') }}</span>
    @endif
</div>
