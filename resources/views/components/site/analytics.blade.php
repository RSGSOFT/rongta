{{-- Search engine verification and analytics, from admin settings (App\Support\SearchTools).
     Verification tags always; analytics scripts only in production. Values are pattern-checked. --}}
@php $search = \App\Support\SearchTools::all(); @endphp
@if ($search['google_verification'] !== '')
    <meta name="google-site-verification" content="{{ $search['google_verification'] }}">
@endif
@if ($search['yandex_verification'] !== '')
    <meta name="yandex-verification" content="{{ $search['yandex_verification'] }}">
@endif
@if (\App\Support\SearchTools::trackingEnabled())
    @if ($search['ga4_id'] !== '')
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $search['ga4_id'] }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',@js($search['ga4_id']));</script>
    @endif
    @if ($search['metrica_id'] !== '')
        <script>(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})(window,document,'script','https://mc.yandex.ru/metrika/tag.js','ym');ym({{ (int) $search['metrica_id'] }},'init',{clickmap:true,trackLinks:true,accurateTrackBounce:true});</script>
    @endif
@endif
