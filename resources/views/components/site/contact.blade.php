{{--
    RSG contact details. App\Support\RsgContacts decides whether they are confirmed; until
    then nothing fake is shown: the line reads "to be confirmed" instead.
    <x-site.contact />               phone link + Telegram
    <x-site.contact only="phone" />  phone link only
--}}
@props(['only' => null])
@php
    $c = \App\Support\RsgContacts::confirmed();
@endphp
@if (! $c)
    <span {{ $attributes->class('tbc') }} data-contact="pending">{{ ui('site.contacts.tbc') }}</span>
@else
    <a {{ $attributes->class('num') }} href="tel:{{ $c['tel'] }}">{{ $c['phone'] }}</a> @if ($only !== 'phone' && $c['telegram'] !== '')<span>Telegram: <a href="https://t.me/{{ $c['telegram'] }}" rel="noopener">{{ '@'.$c['telegram'] }}</a></span>@endif
@endif
