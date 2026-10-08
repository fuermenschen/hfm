@extends('layouts.public')

@section('title', 'Newsletter')
@section('meta_description', 'Abonniere den Newsletter von Höhenmeter für Menschen und erhalte Neuigkeiten zum Spendenlauf und Verein.')

@section('content')
    <div>
        @component('components.page-title')
            Newsletter
        @endcomponent

        <div class="mx-auto w-full max-w-2xl text-left sm:text-center">
            Melde dich für unseren Newsletter an und bleibe über Neuigkeiten rund um Höhenmeter für Menschen und unseren
            Verein auf dem Laufenden.
        </div>

        <x-page-subtitle> Newsletter Anmeldung </x-page-subtitle>

        @livewire('newsletter-registration-form')
    </div>
@endsection
