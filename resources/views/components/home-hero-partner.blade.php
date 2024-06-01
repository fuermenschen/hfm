@php use Illuminate\Support\Facades\Vite; @endphp
@props(['assetUrl', 'assetUrlDark', 'imgAlt', 'beneficiaryUrl'])

<a href="{{$beneficiaryUrl}}" target="_blank" rel="noopener noreferrer">
    <img src="{{ Vite::asset($assetUrl) }}"
         alt="{{$imgAlt}}"
         class="max-h-12 max-w-32 w-full aspect-video dark:hidden" />
    <img src="{{ Vite::asset($assetUrlDark) }}"
         alt="{{$imgAlt}}"
         class="max-h-12 max-w-32 w-full aspect-video hidden dark:block" />
</a>
