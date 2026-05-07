@props(['assetUrl', 'assetUrlDark', 'imgAlt', 'beneficiaryUrl'])

@if (is_string($beneficiaryUrl) && $beneficiaryUrl !== '')
<a {{ $attributes }} href="{{$beneficiaryUrl}}" target="_blank" rel="noopener noreferrer">
    <img src="{{ $assetUrl }}"
         alt="{{$imgAlt}}"
         class="max-h-12 max-w-32 w-full aspect-video dark:hidden" />
    <img src="{{ $assetUrlDark }}"
         alt="{{$imgAlt}}"
         class="max-h-12 max-w-32 w-full aspect-video hidden dark:block" />
</a>
@else
<span {{ $attributes }}>
    <img src="{{ $assetUrl }}"
         alt="{{$imgAlt}}"
         class="max-h-12 max-w-32 w-full aspect-video dark:hidden" />
    <img src="{{ $assetUrlDark }}"
         alt="{{$imgAlt}}"
         class="max-h-12 max-w-32 w-full aspect-video hidden dark:block" />
</span>
@endif
