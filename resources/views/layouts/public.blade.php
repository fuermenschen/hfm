@extends('layouts.base')

@section('body')
    <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-between">
        <div>
            @livewire('public-menu')

            <div
                class="relative m-auto w-full p-9 pt-12 sm:items-center sm:justify-center"
                style="--content-pt: 48px; --content-pb: 36px"
            >
                @yield('content')
            </div>
        </div>

        @livewire('public-footer')
    </div>

    @isset($slot)
        {{ $slot }}
    @endisset
@endsection
