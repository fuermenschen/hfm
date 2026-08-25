@extends('layouts.public')

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
