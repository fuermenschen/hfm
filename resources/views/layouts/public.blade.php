@extends('layouts.base')

@section('body')
    <div class="min-h-screen w-[1100px] max-w-full mx-auto flex flex-col justify-between">
        <div>
            @livewire('public-menu')

            <div
                class="relative m-auto p-md pt-lg sm:justify-center sm:items-center w-full">
                @yield('content')
            </div>
        </div>

        @livewire('public-footer')
    </div>

    @isset($slot)
        {{ $slot }}
    @endisset
@endsection
