@props(['assetUrl', 'imgAlt', 'beneficiaryUrl'])

<a href="{{$beneficiaryUrl}}" target="_blank" rel="noopener noreferrer">
    <img src="{{url(asset($assetUrl))}}"
         alt="{{$imgAlt}}"
         class="max-h-12 max-w-32 w-full aspect-video" />
</a>
