@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-2xl text-center">
        @component('components.page-title')
            Anmeldung bestätigt
        @endcomponent

        <div class="mt-8 rounded-xl border border-green-200 bg-green-50 px-6 py-8 text-green-900">
            <p class="text-lg font-semibold">Danke.</p>
            <p class="mt-2">Deine Registrierung als Sportler:in ist bestätigt.</p>
        </div>
    </div>
@endsection
