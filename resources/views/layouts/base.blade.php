<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @php
        use App\Enums\PublicEventLifecycle;

        $publicEditionPage = isset($publicPageTitle);
        $metadataEvent = $publicEditionPage ? ($publicDonationEvent ?? null) : $currentDonationEvent;
        $sectionTitle = trim($__env->yieldContent('title'));
        $siteName = config('app.name');
        $defaultMetaDescription = 'Höhenmeter für Menschen: Ein Spendenlauf in Winterthur für lokale Benefizpartner:innen.';
        $defaultOgDescription = 'Ein Spendenlauf in Winterthur für lokale Benefizpartner:innen.';
        $sectionMetaDescription = trim($__env->yieldContent('meta_description'));
        $sectionOgDescription = trim($__env->yieldContent('og_description'));
        $eventMetaDescription = $metadataEvent?->contentPlainText('seo.meta_description_md');
        $eventOgDescription = $metadataEvent?->contentPlainText('seo.og_description_md');

        if ($publicEditionPage) {
            $pageTitle = $publicPageTitle;
            $metaDescription = $metadataEvent === null
                ? ($sectionMetaDescription ?: $defaultMetaDescription)
                : ($eventMetaDescription ?: $sectionMetaDescription ?: $defaultMetaDescription);
            $ogDescription = $metadataEvent === null
                ? ($sectionOgDescription ?: $sectionMetaDescription ?: $defaultOgDescription)
                : ($eventOgDescription ?: $eventMetaDescription ?: $sectionOgDescription ?: $sectionMetaDescription ?: $defaultOgDescription);
        } else {
            $pageTitle = $sectionTitle !== '' ? $sectionTitle : ($currentDonationEvent?->title ?? $siteName);
            $metaDescription = $sectionMetaDescription ?: $eventMetaDescription ?: $defaultMetaDescription;
            $ogDescription = $sectionOgDescription ?: $sectionMetaDescription ?: $eventOgDescription ?: $defaultOgDescription;
        }

        $documentTitle = $publicEditionPage
            ? ($pageTitle === $siteName || str_starts_with($pageTitle, $siteName.' ·') ? $pageTitle : $pageTitle.' - '.$siteName)
            : ($sectionTitle !== '' ? $pageTitle.' - '.$siteName : $pageTitle);
        $ogTitle = trim($__env->yieldContent('og_title')) ?: ($publicEditionPage
            ? $pageTitle
            : ($sectionTitle !== '' ? $sectionTitle : ($currentDonationEvent?->title ?? $siteName)));

        if ($publicEditionPage && $metadataEvent !== null) {
            $lifecycleDescription = match ($publicEventLifecycle ?? null) {
                PublicEventLifecycle::Upcoming => 'Bevorstehender Anlass',
                PublicEventLifecycle::Live => 'Der Anlass findet jetzt statt',
                PublicEventLifecycle::Completed => 'Vergangener Anlass',
                default => null,
            };
            $eventFacts = sprintf(
                '%s%s in %s am %s.',
                $lifecycleDescription === null ? '' : $lifecycleDescription.' · ',
                $metadataEvent->title,
                $metadataEvent->location_city,
                $metadataEvent->starts_at->translatedFormat('j. F Y'),
            );
            if (($publicEventLifecycle ?? null) === PublicEventLifecycle::Completed) {
                $metaDescription = sprintf(
                    '%s in %s am %s. Dieser Anlass ist abgeschlossen.',
                    $metadataEvent->title,
                    $metadataEvent->location_city,
                    $metadataEvent->starts_at->translatedFormat('j. F Y'),
                );
                $ogDescription = $metaDescription;
            } else {
                $metaDescription = trim($metaDescription.' '.$eventFacts);
                $ogDescription = trim($ogDescription.' '.$eventFacts);
            }
        }
    @endphp
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $documentTitle }}</title>

    <!-- SEO Information -->
    <meta name="description" content="{{ $metaDescription }}" />
    <meta name="author" content="Verein für Menschen" />
    <meta name="robots" content="{{ request()->attributes->get('robots', 'index, follow') }}" />
    <meta name="yandex" content="noindex, nofollow" />
    <meta name="baiduspider" content="noindex, nofollow" />
    <meta name="apple-mobile-web-app-title" content="Höhenmeter für Menschen" />
    @unless (request()->attributes->has('robots'))
        <meta property="og:title" content="{{ $ogTitle }}" />
        <meta property="og:description" content="{{ $ogDescription }}" />
        <meta property="og:image" content="{{ asset('favicons/social_media_preview.png') }}" />
        <meta property="og:image:type" content="image/png" />
        <meta property="og:image:width" content="1201" />
        <meta property="og:image:height" content="631" />
        <meta property="og:image:alt" content="Logo Höhenmeter für Menschen" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="Höhenmeter für Menschen" />
        <meta property="og:locale" content="de_CH" />
    @endunless

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="57x57" href="{{ url(asset('favicons/apple-icon-57x57.png')) }}" />
    <link rel="apple-touch-icon" sizes="60x60" href="{{ url(asset('favicons/apple-icon-60x60.png')) }}" />
    <link rel="apple-touch-icon" sizes="72x72" href="{{ url(asset('favicons/apple-icon-72x72.png')) }}" />
    <link rel="apple-touch-icon" sizes="76x76" href="{{ url(asset('favicons/apple-icon-76x76.png')) }}" />
    <link rel="apple-touch-icon" sizes="114x114" href="{{ url(asset('favicons/apple-icon-114x114.png')) }}" />
    <link rel="apple-touch-icon" sizes="120x120" href="{{ url(asset('favicons/apple-icon-120x120.png')) }}" />
    <link rel="apple-touch-icon" sizes="144x144" href="{{ url(asset('favicons/apple-icon-144x144.png')) }}" />
    <link rel="apple-touch-icon" sizes="152x152" href="{{ url(asset('favicons/apple-icon-152x152.png')) }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url(asset('favicons/apple-icon-180x180.png')) }}" />
    <link rel="icon" type="image/png" sizes="192x192" href="{{ url(asset('favicons/android-icon-192x192.png')) }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url(asset('favicons/favicon-32x32.png')) }}" />
    <link rel="icon" type="image/png" sizes="96x96" href="{{ url(asset('favicons/favicon-96x96.png')) }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url(asset('favicons/favicon-16x16.png')) }}" />
    <meta name="msapplication-TileColor" content="#1B2E47" />
    <meta name="msapplication-TileImage" content="{{ url(asset('favicons/ms-icon-144x144.png')) }}" />
    <meta name="theme-color" content="#1B2E47" />

    <!-- Fonts -->
    <link rel="stylesheet" href="https://use.typekit.net/enf1jch.css" />
    <!-- darkmode-on -->

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
    @stack('head')
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}" />
</head>

<body class="bg-hfm-white dark:bg-hfm-dark text-hfm-dark dark:text-hfm-white h-full w-full">
    @yield('body')
    @persist('toast')
        <flux:toast />
    @endpersist
    @livewireScripts
    @fluxScripts
</body>
</html>
