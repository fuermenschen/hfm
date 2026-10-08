<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    @hasSection('title')
        <title>
            @yield('title')
            - {{ config('app.name') }}
        </title>
    @else
        <title>{{ $currentDonationEvent?->title ?? config('app.name') }}</title>
    @endif

    @php
        $defaultMetaDescription =
            $currentDonationEvent?->contentPlainText(
                'seo.meta_description_md',
                'Höhenmeter für Menschen: Ein Spendenlauf in Winterthur für lokale Benefizpartner:innen.',
            ) ?: 'Höhenmeter für Menschen: Ein Spendenlauf in Winterthur für lokale Benefizpartner:innen.';
        $defaultOgDescription =
            $currentDonationEvent?->contentPlainText(
                'seo.og_description_md',
                'Ein Spendenlauf in Winterthur für lokale Benefizpartner:innen.',
            ) ?: 'Ein Spendenlauf in Winterthur für lokale Benefizpartner:innen.';
    @endphp

    <!-- SEO Information -->
    <meta name="description" content="@yield('meta_description', e($defaultMetaDescription))" />
    <meta name="author" content="Verein für Menschen" />
    <meta name="robots" content="{{ request()->attributes->get('robots', 'index, follow') }}" />
    <meta name="yandex" content="noindex, nofollow" />
    <meta name="baiduspider" content="noindex, nofollow" />
    <meta name="apple-mobile-web-app-title" content="Höhenmeter für Menschen" />
    @unless (request()->attributes->has('robots'))
        <meta property="og:title" content="@yield('title', e($currentDonationEvent?->title ?? config('app.name')))" />
        <meta
            property="og:description"
            content="@yield('og_description', $__env->yieldContent('meta_description', e($defaultOgDescription)))"
        />
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
