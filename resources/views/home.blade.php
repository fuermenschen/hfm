@props(['athleteCount', 'donationCount'])

@extends('layouts.public')

@section('content')

    @component('components.home-hero') @endcomponent

    @component('components.home-content', ['athleteCount' => $athleteCount, 'donationCount' => $donationCount]) @endcomponent

@endsection
