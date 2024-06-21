<div class="my-md">
    <svg class="relative left-0 stroke-slate-300 mb-6 mx-auto w-full h-1">
        <line x1="0" y1="0" x2="100%" y2="0" style="stroke-width:2" />
    </svg>
    <div class="flex flex-col md:flex-row space-y-sm md:justify-between md:items-baseline px-sm ">
        <div
            class="grid grid-cols-2 gap-4 justify-items-center md:flex md:flex-row md:space-x-7 md:justify-center md:flex-wrap">
            @foreach($footerItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    wire:key="{{ $item['name'] }}"
                    wire:navigate.hover
                    class="text-sm hover:text-hfm-light text-hfm-dark dark:text-hfm-white"
                >
                    {{ $item['name'] }}
                </a>
            @endforeach
            <a
                href="https://www.instagram.com/roundtable25winterthur/"
                class="text-sm hover:text-hfm-light text-hfm-dark dark:text-hfm-white"
                target="_blank"
            >
                Instagram
            </a>
        </div>
        <a class="items-center flex-col-reverse flex md:flex-row md:items-baseline md:space-x-4"
           href="https://www.rt25.ch"
           target="_blank">
            <span class="pt-12 md:pt-0 text-sm">Round Table 25 Winterthur</span>
            <x-logo-roundtable class="h-12 w-12 pt-6 md:pt-0" />
        </a>
    </div>
</div>
