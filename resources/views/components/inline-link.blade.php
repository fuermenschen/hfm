<a
    {{ $attributes->merge(["class" => "text-hfm-red dark:text-hfm-lightred underline hover:text-hfm-light"]) }}
    @if ($attributes->has(['target']))
        target="{{ $attributes->get('target') }}"
    @else
        wire:navigate.hover
    @endif
>{{ $slot }}</a>
