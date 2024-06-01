@php use Illuminate\Support\Facades\Vite; @endphp
<div>
    <img src="{{ Vite::asset("resources/images/logo_light.svg") }}" alt="logo light"
        {{$attributes->class("dark:hidden")->merge() }}/>
    <img src="{{ Vite::asset("resources/images/logo_dark.svg") }}" alt="logo dark"
        {{$attributes->class("hidden dark:block")->merge() }}/>
</div>
