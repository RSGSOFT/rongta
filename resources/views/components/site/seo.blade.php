{{-- Search and link-preview tags for public pages: robots, canonical, hreflang, Open Graph, Twitter card, JSON-LD.
     image: absolute URL of a raster photo for this page (null = the site logo). schema: the page's own JSON-LD nodes. --}}
@props(['title', 'description', 'image' => null, 'noindex' => false, 'schema' => []])
@php
    $locale = app()->getLocale();
    $canonical = \App\Support\Seo::canonical();
    $ogImage = $image ?: \App\Support\Seo::logoUrl();
@endphp
@if ($noindex)
    <meta name="robots" content="noindex, follow">
@endif
<link rel="canonical" href="{{ $canonical }}">
@foreach (\App\Support\Seo::alternates() as $hreflang => $href)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
@endforeach

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:locale" content="{{ \App\Support\Seo::OG_LOCALES[$locale] ?? 'ru_RU' }}">
@foreach (\App\Support\Seo::OG_LOCALES as $altLocale => $ogLocale)
    @if ($altLocale !== $locale)
        <meta property="og:locale:alternate" content="{{ $ogLocale }}">
    @endif
@endforeach
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ $title }}">
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $ogImage }}">

{{ \App\Support\Seo::script($schema) }}
