@extends('layouts.base')

@section('title', 'Resultate')
@section('meta_description', 'Verfolge Spenden, Runden und Höhenmeter beim Spendenlauf Höhenmeter für Menschen in Winterthur.')

@section('body')
    <livewire:results :donation-event="$resultsEvent ?? null" />
@endsection
