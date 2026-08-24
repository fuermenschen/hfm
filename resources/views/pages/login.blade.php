@extends('layouts.public')

@section('content')
    <x-page-title>Login</x-page-title>
    <div class="mx-auto w-full max-w-2xl text-left sm:text-center">
        Bist du bereits als Sportler:in oder Spender:in registriert und möchtest dich einloggen? Gib unten deine
        E-Mail-Adresse ein und erhalte einen Login-Link per E-Mail.
    </div>

    @livewire('login-form')

@endsection
