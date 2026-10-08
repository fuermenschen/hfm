@extends('layouts.public')

@section('title', 'Impressum')
@section('meta_description', 'Impressum des Vereins für Menschen und der Website Höhenmeter für Menschen.')

@section('content')
    @component('components.page-title')
        Impressum
    @endcomponent

    <p>Diese Website ist ein Projekt von:</p>
    <p>Verein für Menschen</p>
    <p>c/o Kai Frehner</p>
    <p>Rössligasse 6</p>
    <p>8405 Winterthur</p>
@endsection
