<div class="my-9">
    <svg class="relative left-0 mx-auto mb-6 h-1 w-full stroke-slate-300">
        <line x1="0" y1="0" x2="100%" y2="0" style="stroke-width:2" />
    </svg>
    <div class="flex flex-col space-y-6 px-6 md:flex-row md:items-baseline md:justify-between">
        <div class="grid grid-cols-2 justify-items-center gap-4 md:flex md:flex-row md:flex-wrap md:justify-center md:space-x-7">
            <a
                href="{{ route('contact') }}"
                wire:navigate.hover
                class="hover:text-hfm-light text-hfm-dark dark:text-hfm-white text-sm"
            >
                Kontakt
            </a>
            <a
                href="{{ route('impressum') }}"
                wire:navigate.hover
                class="hover:text-hfm-light text-hfm-dark dark:text-hfm-white text-sm"
            >
                Impressum
            </a>
            <a
                href="{{ route('privacy') }}"
                wire:navigate.hover
                class="hover:text-hfm-light text-hfm-dark dark:text-hfm-white text-sm"
            >
                Datenschutz
            </a>
            <a
                href="{{ route('association') }}"
                wire:navigate.hover
                class="hover:text-hfm-light text-hfm-dark dark:text-hfm-white text-sm"
            >
                Verein
            </a>
            <a
                href="{{ route('newsletter') }}"
                wire:navigate.hover
                class="hover:text-hfm-light text-hfm-dark dark:text-hfm-white text-sm"
            >
                Newsletter
            </a>
        </div>
        <a
            class="flex flex-col-reverse items-center md:flex-row md:items-baseline md:space-x-4"
            href="{{ route('association') }}"
            wire:navigate.hover
        >
            <span class="pt-12 text-sm md:pt-0">Verein für Menschen</span>
        </a>
    </div>
</div>
