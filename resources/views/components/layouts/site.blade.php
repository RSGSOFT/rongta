@php
    // Admin-editable brand colours. Only well-formed hex values are written into the page.
    $cssVars = collect([
        '--accent' => setting('accent'),
        '--accent-ink' => setting('accent_ink'),
        '--accent-soft' => setting('accent_soft'),
    ])->filter(fn ($v) => is_string($v) && preg_match('/^#[0-9A-Fa-f]{6}$/', $v));

    $compare = \App\Livewire\Pages\Compare::slugs();

    // Page data for <head>: title, description (the site's about line when a page has none),
    // image (raster photo URL), noindex, schema (the page's JSON-LD nodes).
    $pageTitle = (isset($title) && $title !== '' ? $title.' · ' : '').config('app.name');
    $pageDescription = isset($description) && trim((string) $description) !== '' ? (string) $description : ui('ftr.about');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <x-site.seo :title="$pageTitle" :description="$pageDescription" :image="$image ?? null" :noindex="$noindex ?? false" :schema="$schema ?? []" />
    <x-site.analytics />

    <link rel="icon" type="image/png" href="{{ asset('images/rongta-symbol.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700&family=Sofia+Sans+Condensed:wght@500;600;700;800&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if ($cssVars->isNotEmpty())
        <style>:root{ @foreach ($cssVars as $name => $value){{ $name }}:{{ $value }};@endforeach }</style>
    @endif

    {{-- Compare list state shared by every "Compare" tick, the tray and the compare page.
         Registered before Alpine starts (Alpine ships with Livewire). --}}
    <script>
        window.rongta = { compare: @json($compare), limitMsg: @json(ui('cmp.limit')) };
        document.addEventListener('alpine:init', function () {
            Alpine.store('compare', {
                list: window.rongta.compare,
                has: function (slug) { return this.list.indexOf(slug) >= 0; },
                // Up to 4 models side by side, 3 on phones.
                limit: function () {
                    try { return window.matchMedia('(max-width: 760px)').matches ? 3 : 4; } catch (e) { return 4; }
                },
                toggle: function (slug, box) {
                    var want = box ? box.checked : ! this.has(slug);
                    if (want === this.has(slug)) return;
                    if (want && this.list.length >= this.limit()) {
                        if (box) box.checked = false;
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: window.rongta.limitMsg.replace('{n}', this.limit()) } }));
                        return;
                    }
                    this.list = want ? this.list.concat([slug]) : this.list.filter(function (s) { return s !== slug; });
                    Livewire.dispatch('compare-toggle', { slug: slug, on: want });
                },
                clear: function () {
                    this.list = [];
                    Livewire.dispatch('compare-clear');
                }
            });
        });
        // The server has the final say (session list); keep ticks and the tray spacing in step with it.
        window.addEventListener('compare-updated', function (e) {
            if (window.Alpine && e.detail && Array.isArray(e.detail.slugs)) Alpine.store('compare').list = e.detail.slugs;
        });
    </script>
</head>
<body @class(['min-h-screen bg-paper font-body text-ink antialiased selection:bg-accent selection:text-on-accent'])
    x-data x-bind:class="{ 'has-tray': $store.compare.list.length > 0 }">
    <a class="skip" href="#app">{{ ui('site.skip') }}</a>

    <x-site.header />

    <main id="app" tabindex="-1" style="outline:none">
        {{ $slot }}
    </main>

    <x-site.footer />

    <livewire:site.compare-tray />

    <x-site.bottom-bar />

    <livewire:chat.widget />

    <div id="toast" role="status" aria-live="polite"
        x-data="{ message: '', timer: null }"
        x-on:toast.window="message = ($event.detail && $event.detail.message) || ''; clearTimeout(timer); timer = setTimeout(function () { message = '' }, 3600)">
        <div x-show="message" x-text="message" x-cloak></div>
    </div>
</body>
</html>
