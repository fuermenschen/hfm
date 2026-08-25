@extends('layouts.portal')

@section('title', 'Profil')

@section('content')
    <div class="space-y-8">
        <header>
            <flux:heading size="xl" level="1">Dein Profil</flux:heading>
            <flux:text class="mt-2 text-base">Halte deine Wohnadresse und Telefonnummer aktuell.</flux:text>
        </header>

        <livewire:portal-profile-form />
    </div>
@endsection
