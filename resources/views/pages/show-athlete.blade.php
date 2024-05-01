@props(['login_token'])

@extends('layouts.public')

@section('content')
    @livewire('athlete-details', ['login_token' => $login_token])
@endsection
