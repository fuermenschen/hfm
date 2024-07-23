@extends('printables.base')

@props(['athlete'])

@php
    $partnerName = "";

    if (str_contains($athlete->partner->name, "alle")) {
    $partnerName = "allen drei Benefizpartner:innen zu gleichen Teilen";
    } elseif (str_contains($athlete->partner->name, "Brühlgut")) {
    $partnerName = "der Brühlgut Stiftung";
    } elseif (str_contains($athlete->partner->name, "Kinderseele")) {
    $partnerName = "dem Institut Kinderseele Schweiz";
    } elseif (str_contains($athlete->partner->name, "143")) {
    $partnerName = "der Dargebotenen Hand (Tel 143)";
    } else {
    throw new Exception("Unknown partner name: " . $athlete->partner->name);
    }


@endphp

@section('body')
    <style>

        .name {
            position: absolute;
            width: 4.5cm;
            top: 16.1cm;
            left: 2.4cm;
            font-size: 12px;
            font-weight: bold;
        }

        .partner {
            position: absolute;
            width: 5.5cm;
            top: 16.1cm;
            left: 8.2cm;
            font-size: 12px;
            font-weight: bold;
        }

    </style>

    <div class="name">{{ $athlete->privacy_name. " (" . $athlete->public_id_string . ")" }}</div>

    <div class="partner">{{ $partnerName }}.</div>

@endsection
