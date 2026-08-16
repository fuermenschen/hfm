@extends('printables.base')

@props(['athlete'])

@section('body')
    <style>

        .name {
            position: absolute;
            width: 4.5cm;
            top: 17.1cm;
            left: 3.3cm;
            font-size: 14px;
            font-weight: bold;
        }

        .partner {
            position: absolute;
            width: 5.5cm;
            top: 17.1cm;
            left: 8.4cm;
            font-size: 14px;
            font-weight: bold;
        }

    </style>

    <div class="name">{{ $athlete->privacy_name.' ('.$athlete->public_id_string.')' }}</div>

    <div class="partner">{{ $partnerName }}.</div>

@endsection
