<a wire:navigate.hover {{ $attributes->merge(["class" => "text-hfm-red dark:text-hfm-lightred underline hover:text-hfm-light"]) }}>
    {{ $slot }}
</a>
