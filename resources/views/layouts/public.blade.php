@extends('layouts.base')

@section('body')
    <div class="min-h-screen w-[1000px] max-w-full mx-auto">
        @livewire('public-menu')

        <div
            class="relative m-auto p-sm sm:justify-center sm:items-center">
            @yield('content')
        </div>

        @livewire('public-footer')
    </div>

    @isset($slot)
        {{ $slot }}
    @endisset
@endsection
