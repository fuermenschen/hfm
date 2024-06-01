@php use Illuminate\Support\Facades\Vite; @endphp
<div {{$attributes}}>
    <img src="{{ Vite::asset('resources/images/rt_simple_light.svg') }}"
         alt="Roundtable 25 Winterthur" class="dark:hidden">
    <img src="{{ Vite::asset('resources/images/rt_simple_dark.svg') }}"
         alt="Roundtable 25 Winterthur" class="hidden dark:block">
</div>
