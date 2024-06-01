@props(['url'])
@php use Illuminate\Support\Facades\Vite; @endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;" class="logo">
<img src="{{ Vite::asset("resources/images/logo_dark.svg") }}" style="width: 100%; height: auto;" alt="logo">
</a>
</td>
</tr>
