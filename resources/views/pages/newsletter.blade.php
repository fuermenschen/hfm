@extends('layouts.public')

@section('content')
    <div>
        @component('components.page-title')
            Newsletter
        @endcomponent

        <div class="w-full max-w-2xl mx-auto text-left sm:text-center">
            Melde dich für unseren Newsletter an und bleibe über Neuigkeiten rund um Höhenmeter fuer Menschen und unseren Verein auf dem Laufenden.
        </div>

        <x-page-subtitle>
            Newsletter Anmeldung
        </x-page-subtitle>

        @livewire('newsletter-registration-form')
    </div>
@endsection
