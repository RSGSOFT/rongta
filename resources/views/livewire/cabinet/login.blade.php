<div class="pb-wrap">
    <div class="pb-head">
        <h1>{{ ui('cab.login.h') }}</h1>
        <p class="pb-lead">{{ ui('cab.login.lead') }}</p>
    </div>

    <div class="pb-panel pb-login">
        <form class="pb-form pb-form-1" id="cab-login" wire:submit="login" novalidate>
            <div class="pb-field">
                <label for="cab-email">{{ ui('cab.email') }}</label>
                <input class="pb-inp" type="email" id="cab-email" wire:model="email" maxlength="255" required autocomplete="username"
                       @error('email') aria-invalid="true" aria-describedby="cab-email-err" @enderror>
                <span class="pb-err" id="cab-email-err" role="alert">@error('email'){{ $message }}@enderror</span>
            </div>
            <div class="pb-field">
                <label for="cab-password">{{ ui('cab.password') }}</label>
                <input class="pb-inp" type="password" id="cab-password" wire:model="password" maxlength="255" required autocomplete="current-password">
            </div>
            <label class="pb-check">
                <input type="checkbox" id="cab-remember" wire:model="remember">
                <span>{{ ui('cab.login.remember') }}</span>
            </label>
            <div class="pb-field">
                <button type="submit" class="pb-btn" id="cab-login-btn" wire:loading.attr="disabled" wire:target="login">{{ ui('cab.login.btn') }}</button>
            </div>
        </form>

        <p class="pb-small" style="margin-top:18px">{{ ui('cab.login.no') }} <a class="pb-link" href="{{ lroute('cabinet.register') }}" id="cab-to-register">{{ ui('cab.login.reg') }}</a></p>
        <p class="pb-small pb-muted" style="margin-top:8px">{{ ui('cab.login.forgot') }}</p>
    </div>
</div>
