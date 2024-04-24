@extends('layouts.base')

@section('body')
    <div class="min-h-screen bg-gray-100">
        @livewire('public-menu')

        <div class="relative sm:justify-center sm:items-center bg-hfm-white border-solid border-2 border-black">
            @yield('content')
        </div>
    </div>

    @isset($slot)
        {{ $slot }}
    @endisset
@endsection
