<div class="pb-wrap">
    <div class="pb-head">
        <h1>{{ ui('cab.reg.h') }}</h1>
        <p class="pb-lead">{{ ui('cab.reg.lead') }}</p>
    </div>

    <div class="pb-panel pb-cab-reg">
        @if ($sentEmail !== '')
            <div class="pb-done" id="cab-done" tabindex="-1" role="status">
                <h3>{{ ui('cab.reg.done.h') }}</h3>
                <p>{{ ui('cab.reg.done', ['email' => $sentEmail]) }}</p>
                <p style="margin-top:14px"><a class="pb-link" href="{{ lroute('cabinet.login') }}">{{ ui('cab.login.h') }}</a></p>
            </div>
        @else
            <form class="pb-form" id="cab-register" wire:submit="register" novalidate>
                @foreach ([
                    ['company', 'cab.reg.company', 'text', 'organization'],
                    ['contact_name', 'cab.reg.name', 'text', 'name'],
                    ['phone', 'cab.reg.phone', 'tel', 'tel'],
                    ['email', 'cab.email', 'email', 'username'],
                    ['password', 'cab.password', 'password', 'new-password'],
                    ['password_confirmation', 'cab.reg.password2', 'password', 'new-password'],
                ] as [$field, $label, $inputType, $autocomplete])
                    <div class="pb-field">
                        <label for="cab-{{ $field }}">{{ ui($label) }}</label>
                        <input class="pb-inp" type="{{ $inputType }}" id="cab-{{ $field }}" wire:model="{{ $field }}" maxlength="255" required autocomplete="{{ $autocomplete }}"
                               @if ($field === 'password') aria-describedby="cab-password-hint cab-password-err" @endif
                               @error($field) aria-invalid="true" @if ($field !== 'password') aria-describedby="cab-{{ $field }}-err" @endif @enderror>
                        @if ($field === 'password')
                            <span class="pb-small pb-muted" id="cab-password-hint">{{ ui('cab.reg.password.hint') }}</span>
                        @endif
                        <span class="pb-err" id="cab-{{ $field }}-err">@error($field){{ $message }}@enderror</span>
                    </div>
                @endforeach

                <fieldset class="pb-field pb-full pb-cab-roles" @error('roles') aria-invalid="true" @enderror>
                    <legend>{{ ui('cab.reg.roles') }}</legend>
                    <label class="pb-check">
                        <input type="checkbox" id="cab-sales" wire:model="sales" @error('roles') aria-invalid="true" aria-describedby="cab-roles-err" @enderror>
                        <span>{{ ui('cab.reg.sales') }}</span>
                    </label>
                    <label class="pb-check">
                        <input type="checkbox" id="cab-service" wire:model="service">
                        <span>{{ ui('cab.reg.service') }}</span>
                    </label>
                    <span class="pb-err" id="cab-roles-err">@error('roles'){{ $message }}@enderror</span>
                </fieldset>

                <div class="pb-hp" aria-hidden="true">
                    <label for="cab-website">Website</label>
                    <input type="text" id="cab-website" wire:model="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="pb-field pb-full">
                    @error('form')
                        <p class="pb-note" id="cab-form-error" tabindex="-1" role="alert">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="pb-btn" id="cab-register-btn" wire:loading.attr="disabled" wire:target="register">{{ ui('cab.reg.btn') }}</button>
                </div>
            </form>

            <p class="pb-small" style="margin-top:18px">{{ ui('cab.reg.have') }} <a class="pb-link" href="{{ lroute('cabinet.login') }}">{{ ui('cab.login.btn') }}</a></p>
        @endif
    </div>
</div>
