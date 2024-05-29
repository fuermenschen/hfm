<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @hasSection('title')

        <title>@yield('title') - {{ config('app.name') }}</title>
    @else
        <title>{{ config('app.name') }}</title>
    @endif

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

<body class="h-full w-full bg-hfm-white text-hfm-dark">
<x-dialog />
@yield('body')
</body>
</html>
