@extends('layouts.base')

@section('title', 'Resultate')

@section('body')
    <livewire:results :donation-event="$resultsEvent ?? null" />
@endsection
