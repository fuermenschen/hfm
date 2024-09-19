<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @hasSection('title')

        <title>@yield('title') - {{ config('app.name') }}</title>
    @else
        <title>{{ config('app.name') }}</title>
    @endif

    <!-- SEO Information -->
    <meta name="description"
          content="Höhenmeter für Menschen: Ein Spendenlauf in Winterthur am 21. September 2024. Mit den Spenden werden Winterthurer Benefizpartner:innen unterstützt.">
    <meta name="keywords"
          content="Höhenmeter für Menschen, fundraising, charity event, Wohltätigkeit, Winterthur, sponsored run, Sponsorenlauf, Spendenlauf, Brühlgut Stiftung, Institut Kinderseele Schweiz, Tel 143, Dargebotene Hand, Roundtable, Round Table">
    <meta name="author" content="Round Table 25 Winterthur">
    <meta name="robots" content="index, follow">
    <meta name="revisit-after" content="1 day">
    <meta name="language" content="de">
    <meta name="distribution" content="global">
    <meta name="rating" content="general">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">
    <meta name="yandex" content="noindex, nofollow">
    <meta name="msnbot" content="index, follow">
    <meta name="alexabot" content="index, follow">
    <meta name="slurp" content="index, follow">
    <meta name="teoma" content="index, follow">
    <meta name="baiduspider" content="noindex, nofollow">
    <meta name="apple-mobile-web-app-title" content="Höhenmeter für Menschen">
    <meta name="og:title" content="Höhenmeter für Menschen">
    <meta name="og:description"
          content="Ein Spendenlauf in Winterthur für Winterthur am 21. September 2024.">
    <meta name="og:image" content="{{ Vite::asset("resources/images/logo_light.svg") }}">
    <meta name="og:url" content="https://hfm.rt25.ch">
    <meta name="og:type" content="website">
    <meta name="og:site_name" content="Höhenmeter für Menschen">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="57x57" href="{{ url(asset('icons/apple-icon-57x57.png')) }}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{ url(asset('icons/apple-icon-60x60.png')) }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ url(asset('icons/apple-icon-72x72.png')) }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ url(asset('icons/apple-icon-76x76.png')) }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ url(asset('icons/apple-icon-114x114.png')) }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ url(asset('icons/apple-icon-120x120.png')) }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ url(asset('icons/apple-icon-144x144.png')) }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ url(asset('icons/apple-icon-152x152.png')) }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url(asset('icons/apple-icon-180x180.png')) }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ url(asset('icons/android-icon-192x192.png')) }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url(asset('icons/favicon-32x32.png')) }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ url(asset('icons/favicon-96x96.png')) }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url(asset('icons/favicon-16x16.png')) }}">
    <link rel="manifest" href="{{ url(asset('icons/manifest.json')) }}">
    <meta name="msapplication-TileColor" content="#1B2E47">
    <meta name="msapplication-TileImage" content="{{ url(asset('icons/ms-icon-144x144.png')) }}">
    <meta name="theme-color" content="#1B2E47">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://use.typekit.net/enf1jch.css"> <!-- darkmode-on -->

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @livewireStyles
    @livewireScripts
    @wireUiScripts

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="h-full w-full bg-hfm-white dark:bg-hfm-dark text-hfm-dark dark:text-hfm-white">
<x-dialog />
<x-notifications />
@yield('body')
</body>
</html>
