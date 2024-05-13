@props(['assetUrl', 'imgAlt', 'beneficiaryName', 'beneficiaryUrl'])

<a href="{{$beneficiaryUrl}}" target="_blank" rel="noopener noreferrer"
   class="flex flex-row sm:flex-col space-x-sm sm:space-x-0 sm:space-y-sm w-auto sm:w-48 justify-center sm:justify-end items-center">
    <div class="w-16 h-auto sm:w-24">
        <img src="{{url(asset($assetUrl))}}"
             alt="{{$imgAlt}}"
             class="max-h-10 sm:mx-auto" />
    </div>
    <span class="text-right sm:text-center text-hfm-dark text-xs basis-44 sm:basis-auto">
                    {{ $beneficiaryName }}
                </span>
</a>
