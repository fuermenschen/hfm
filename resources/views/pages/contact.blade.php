@extends('layouts.public')

@section('content')
    @component('components.page-title')
        Kontakt
    @endcomponent

    <p>Am besten schreibst du uns eine E-Mail an <a
            href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>

@endsection
