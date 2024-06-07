@props(['url'])
@php use Illuminate\Support\Facades\Vite; @endphp
<tr>
<td class="header">
<a href="{{ $url }}" class="logo" id="logo-light">
<img src="{{ Vite::asset("resources/images/logo_light.svg") }}" style="width: 100%; height: auto;" alt="logo">
</a>
<a href="{{ $url }}" class="logo" style="display: none" id="logo-dark">
<img src="{{ Vite::asset("resources/images/logo_dark.svg") }}" style="width: 100%; height: auto;" alt="logo">
</a>
</td>
</tr>
