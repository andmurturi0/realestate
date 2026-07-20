<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php($branding = \App\Models\Setting::allAsArray())
        <title inertia>{{ $branding['agency_name'] ?? '' }}</title>
        @if (! empty($branding['favicon_path']))
            <link rel="icon" href="{{ \Illuminate\Support\Facades\Storage::disk('supabase')->url($branding['favicon_path']) }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        {{-- No Inertia SSR in this app, so Vue's <Head> tags only exist after
        client-side hydration — crawlers (Facebook, Twitter) never see them.
        The property detail page's OG/Twitter/JSON-LD are rendered here
        instead, from the already route-bound model (no extra query). --}}
        @if (request()->routeIs('properties.show') && ($property = request()->route('property')) && $property->status->value === 'published')
            {{-- The block-style raw-PHP directive (open/close pair) is deliberately
            avoided below: Blade's raw-php-block regex matches from the FIRST such
            opening token anywhere in the file (line 7's inline form above) to the
            NEXT closing token anywhere after it, corrupting everything in between.
            Inline single-statement form has no closing token, so it can't collide. --}}
            @php($ogTitle = $property->getTranslation('title', 'sq'))
            @php($ogDescription = \Illuminate\Support\Str::limit(strip_tags($property->getTranslation('description', 'sq') ?? ''), 200))
            @php($ogImage = $property->images()->where('is_primary', true)->first()?->url ?? $property->images()->first()?->url)
            <meta name="description" content="{{ $ogDescription }}">
            <meta property="og:type" content="website">
            <meta property="og:title" content="{{ $ogTitle }}">
            <meta property="og:description" content="{{ $ogDescription }}">
            <meta property="og:url" content="{{ url()->current() }}">
            @if ($ogImage)
                <meta property="og:image" content="{{ $ogImage }}">
            @endif
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $ogTitle }}">
            <meta name="twitter:description" content="{{ $ogDescription }}">
            @if ($ogImage)
                <meta name="twitter:image" content="{{ $ogImage }}">
            @endif
            <script type="application/ld+json">
                {!! json_encode([
                    '@@context' => 'https://schema.org',
                    '@@type' => 'RealEstateListing',
                    'name' => $ogTitle,
                    'description' => $ogDescription,
                    'url' => url()->current(),
                    'image' => $ogImage ? [$ogImage] : [],
                    'offers' => [
                        '@@type' => 'Offer',
                        'price' => number_format($property->price / 100, 2, '.', ''),
                        'priceCurrency' => 'EUR',
                    ],
                ]) !!}
            </script>
        @endif

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
