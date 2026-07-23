@props(['assetUrl', 'assetUrlDark', 'imgAlt', 'beneficiaryUrl'])

@if (is_string($beneficiaryUrl) && $beneficiaryUrl !== '')
<a {{ $attributes->class(['flex aspect-3/1 w-20 items-center justify-center sm:w-28']) }} href="{{$beneficiaryUrl}}" target="_blank" rel="noopener noreferrer">
    <img src="{{ $assetUrl }}"
         alt="{{$imgAlt}}"
         class="h-full w-full object-contain dark:hidden" />
    <img src="{{ $assetUrlDark }}"
         alt="{{$imgAlt}}"
         class="hidden h-full w-full object-contain dark:block" />
</a>
@else
<span {{ $attributes->class(['flex aspect-3/1 w-20 items-center justify-center sm:w-28']) }}>
    <img src="{{ $assetUrl }}"
         alt="{{$imgAlt}}"
         class="h-full w-full object-contain dark:hidden" />
    <img src="{{ $assetUrlDark }}"
         alt="{{$imgAlt}}"
         class="hidden h-full w-full object-contain dark:block" />
</span>
@endif
