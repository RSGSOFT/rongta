<div class="pb-wrap">
    <div class="pb-head">
        <div class="pb-p-top">
            <h1>{{ ui('cab.h') }}</h1>
            <form method="post" action="{{ lroute('cabinet.logout') }}">
                @csrf
                <button type="submit" class="pb-btn pb-btn-sm" id="cab-logout">{{ ui('cab.logout') }}</button>
            </form>
        </div>
        <p class="pb-lead" id="cab-who">{{ $company }} · {{ $account->contact_name }}</p>
    </div>

    @if (! $sells && ! $services)
        <p class="pb-note" id="cab-noroles">{{ ui('cab.noroles') }}</p>
    @endif

    @if ($sells)
        <livewire:cabinet.scales />
    @endif

    @if ($services)
        <livewire:cabinet.service />
    @endif
</div>
