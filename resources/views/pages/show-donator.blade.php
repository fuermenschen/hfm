@props(['login_token', 'donation_id'])

@extends('layouts.public')

@section('content')
    @livewire('donator-details', ['login_token' => $login_token, 'donation_id' => $donation_id ?? null])
@endsection
