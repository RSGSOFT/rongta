<div class="pb-wrap">
    <div class="pb-head">
        <h1>{{ ui('pa.h') }}</h1>
        <p class="pb-lead">{{ ui('pa.login.lead') }}</p>
    </div>

    <div class="pb-panel pb-login">
        <form class="pb-form pb-form-1" id="login-form" wire:submit="authenticate" novalidate>
            @error('login')
                <p class="pb-note" id="login-error" role="alert">{{ $message }}</p>
            @enderror
            <div class="pb-field">
                <label for="login-user">{{ ui('pa.user') }}</label>
                <input class="pb-inp" type="text" id="login-user" wire:model="login" autocomplete="username"
                       autocapitalize="none" spellcheck="false" maxlength="255" required
                       @error('login') aria-invalid="true" aria-describedby="login-error" @enderror>
            </div>
            <div class="pb-field">
                <label for="login-pass">{{ ui('pa.pass') }}</label>
                <input class="pb-inp" type="password" id="login-pass" wire:model="password" autocomplete="current-password"
                       maxlength="255" required
                       @error('login') aria-invalid="true" aria-describedby="login-error" @enderror>
            </div>
            <div class="pb-btn-row">
                <button type="submit" class="pb-btn" id="login-submit" wire:loading.attr="disabled" wire:target="authenticate">{{ ui('pa.login') }}</button>
            </div>
        </form>
    </div>

    <p class="pb-muted" style="margin-top:24px;max-width:60ch">
        {{ ui('pa.nologin') }}
        <a class="pb-link" href="{{ lroute('dealers') }}#partner-form" id="pa-toform">{{ ui('ps.form') }}</a>
    </p>
</div>
