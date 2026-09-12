{{--
    Every meta tag a page needs, in one component.

    Put <x-seo … /> in the <head> of every layout. It emits the description,
    canonical, robots directive, Open Graph and Twitter cards, the icons and
    the site-wide structured data — so a page only has to say what is different
    about itself.

    Props
      title       Page title. Also the og:title / twitter:title.
      description One or two sentences. Falls back to config('seo.description').
      image       Absolute URL, or a path from the public root. Falls back to
                  the site card.
      imageAlt    What the card shows, for screen readers and X.
      type        og:type — 'website' (default), 'article', 'profile'.
      canonical   Override the canonical URL. Defaults to the current URL with
                  query strings stripped, since none of ours change the content.
      noindex     true on anything private: dashboards, auth, unlisted pages.
      structured  Extra JSON-LD (array or array of arrays) merged in after the
                  site-wide Organization and WebSite records.
--}}

@props([
    'title'       => null,
    'description' => null,
    'image'       => null,
    'imageAlt'    => null,
    'type'        => 'website',
    'canonical'   => null,
    'noindex'     => false,
    'structured'  => [],
])

@php
    $seo = config('seo');

    $siteUrl = rtrim($seo['url'] ?? config('app.url'), '/');

    /** Absolute URL from a path, an already-absolute URL, or null. */
    $absolute = static function (?string $value) use ($siteUrl): ?string {
        if (! $value) {
            return null;
        }

        return str_starts_with($value, 'http') ? $value : $siteUrl . '/' . ltrim($value, '/');
    };

    $pageTitle = $title ?: ($seo['name'] . ' — ' . $seo['tagline']);
    $pageDesc  = trim($description ?: $seo['description']);

    // Canonical URLs carry no query string: ?q= and the like reorder existing
    // content rather than making a new page. The home page keeps its slash.
    $path    = trim(request()->path(), '/');
    $pageUrl = $canonical ?: ($path === '' ? $siteUrl : $siteUrl . '/' . $path);
    $pageImage   = $absolute($image) ?: $absolute($seo['image']['path']);
    $pageImgAlt  = $imageAlt ?: $seo['image']['alt'];

    // Site-wide records. The WebSite entry with its SearchAction is what lets
    // Google offer a search box against the site; alternateName is how the
    // spellings people actually type get tied to this brand.
    $graph = [
        [
            '@context'      => 'https://schema.org',
            '@type'         => 'Organization',
            '@id'           => $siteUrl . '/#organization',
            'name'          => $seo['name'],
            'alternateName' => $seo['aliases'],
            'url'           => $siteUrl . '/',
            'logo'          => [
                '@type'  => 'ImageObject',
                'url'    => $absolute('/icon-512.png'),
                'width'  => 512,
                'height' => 512,
            ],
            'description' => $seo['description'],
            'sameAs'      => array_values(array_filter([
                $seo['social']['instagram'] ?? null,
                $seo['social']['facebook'] ?? null,
                $seo['social']['tiktok'] ?? null,
                $seo['social']['linkedin'] ?? null,
                ($seo['social']['twitter'] ?? '') ? 'https://x.com/' . ltrim($seo['social']['twitter'], '@') : null,
            ])),
        ],
        [
            '@context'      => 'https://schema.org',
            '@type'         => 'WebSite',
            '@id'           => $siteUrl . '/#website',
            'url'           => $siteUrl . '/',
            'name'          => $seo['name'],
            'alternateName' => $seo['aliases'],
            'description'   => $seo['description'],
            'inLanguage'    => 'en',
            'publisher'     => ['@id' => $siteUrl . '/#organization'],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $siteUrl . '/stories?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];

    // A page may hand us one record or several.
    if ($structured !== []) {
        $graph = array_merge($graph, array_is_list($structured) ? $structured : [$structured]);
    }

    $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDesc }}">

@if ($noindex)
    {{-- Private or duplicate: keep it out of the index but let the crawler
         follow the links on it back to pages that should be there. --}}
    <meta name="robots" content="noindex, follow">
@else
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="{{ $pageUrl }}">
@endif

{{-- ── Open Graph: Facebook, WhatsApp, LinkedIn, Slack ──────────────── --}}
<meta property="og:site_name" content="{{ $seo['name'] }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:url" content="{{ $pageUrl }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDesc }}">
<meta property="og:locale" content="{{ $seo['locale'] }}">
<meta property="og:image" content="{{ $pageImage }}">
<meta property="og:image:secure_url" content="{{ $pageImage }}">
<meta property="og:image:width" content="{{ $seo['image']['width'] }}">
<meta property="og:image:height" content="{{ $seo['image']['height'] }}">
<meta property="og:image:alt" content="{{ $pageImgAlt }}">

{{-- ── X / Twitter ──────────────────────────────────────────────────── --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDesc }}">
<meta name="twitter:image" content="{{ $pageImage }}">
<meta name="twitter:image:alt" content="{{ $pageImgAlt }}">
@if ($seo['social']['twitter'])
    <meta name="twitter:site" content="{{ Str::start($seo['social']['twitter'], '@') }}">
    <meta name="twitter:creator" content="{{ Str::start($seo['social']['twitter'], '@') }}">
@endif

{{-- ── Icons and app metadata ───────────────────────────────────────── --}}
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="/icon-32.png">
<link rel="icon" type="image/png" sizes="192x192" href="/icon-192.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="apple-mobile-web-app-title" content="{{ $seo['name'] }}">
<meta name="application-name" content="{{ $seo['name'] }}">
<meta name="theme-color" content="{{ config('brand.primary.500') }}">
<meta name="format-detection" content="telephone=no">

@if ($seo['verification']['google'])
    <meta name="google-site-verification" content="{{ $seo['verification']['google'] }}">
@endif
@if ($seo['verification']['bing'])
    <meta name="msvalidate.01" content="{{ $seo['verification']['bing'] }}">
@endif

{{-- ── Structured data ──────────────────────────────────────────────── --}}
@foreach ($graph as $record)
    <script type="application/ld+json">{!! json_encode($record, $jsonFlags) !!}</script>
@endforeach
