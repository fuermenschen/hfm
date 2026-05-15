@extends('layouts.public')

@section('content')
    <x-page-title>Portal</x-page-title>

    <div class="w-full max-w-2xl mx-auto space-y-4 text-left sm:text-center">
        <p>
            {{ auth('external')->user()?->first_name ? 'Hallo '.auth('external')->user()->first_name : 'Hallo' }}
        </p>

        <p>
            Willkommen im neuen Portal.
        </p>

        <p class="text-sm text-zinc-600 dark:text-zinc-300">
            Diese Seite ist aktuell in Aufbau. Deine Sportler:innen- und Spender:innen-Daten bleiben bis zur Umstellung weiterhin unter den bestehenden Links erreichbar.
        </p>
    </div>
@endsection
