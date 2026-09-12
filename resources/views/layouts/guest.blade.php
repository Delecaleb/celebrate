@props([
    'title'       => null,
    'description' => null,
    'ogImage'     => null,
    'ogImageAlt'  => null,
    'ogType'      => 'website',
    'noindex'     => false,
    'structured'  => [],
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- A celebration page passes its own description, cover photo and
             visibility; anything else falls back to the site defaults. --}}
        <x-seo
            :title="$title ?: config('seo.name')"
            :description="$description"
            :image="$ogImage"
            :imageAlt="$ogImageAlt"
            :type="$ogType"
            :noindex="$noindex"
            :structured="$structured"
        />

        <!-- Fonts. Outfit and Plus Jakarta Sans are what theme.css actually
             asks for — without them every .ds page falls back to system-ui. -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <x-brand-tokens />

        {{-- Alpine hides these until it boots; without the rule every x-cloak
             element flashes on first paint. --}}
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <x-impersonation-banner />

        <div class="min-h-screen bg-white">
           {{ $slot }}
        </div>
    </body>
</html>
