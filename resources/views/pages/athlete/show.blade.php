@props(['athlete'])
@extends('layouts.public')
@section('content')
    <div>
        Hallo {{ $athlete->first_name }}. Du hast dich entschieden, am Anlass folgendes zu
        tun: {{ $athlete->sport_type }}. Viel Erfolg!
    </div>
@endsection
