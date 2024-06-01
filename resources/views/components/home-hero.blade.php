<div class="w-full max-w-[600px] h-[calc(100vh-5rem)] min-h-[600px] flex flex-col justify-evenly text-center mx-auto">
    <div class="overflow-clip">
        <p class="text-sm sm:text-base">Samstag, 21. September 2024 in Winterthur</p>
        <h1 class="text-4xl font-extrabold sm:text-5xl lg:text-6xl mt-4 sm:mt-6 text-hfm-dark">Höhenmeter
            für&nbsp;Menschen</h1>
        <p class="mt-4 sm:mt-6 text-sm sm:text-lg leading-8 text-gray-600">Ein Spendenlauf für Winterthur. Wir rennen,
            fahren,
            rollen
            für
            lokale Benefizpartner:innen. Bist auch du am Start?</p>
        <div class="mt-4 sm:mt-8 flex items-center justify-center gap-x-6">
            <a href="#info"
               class="rounded-md bg-hfm-red px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-hfm-dark">Mehr
                dazu</a>
            <a href="{{ route('become-donator') }}" wire:navigate.hover
               class="text-xs sm:text-sm font-semibold leading-6 text-gray-900">Spender:in
                werden <span
                    aria-hidden="true">→</span></a>
        </div>
    </div>

    <div
        class="grid grid-cols-3 gap-x-4 sm:gap-x-8 gap-y-4 pb-md max-h-[25vh] w-full max-w-[600px] mx-auto overflow-clip">
        <h3 class="col-span-3 text-xs sm:text-sm">Unsere Benefizpartner:innen</h3>

        <x-home-hero-partner assetUrl="resources/images/bruehlgut_light.svg" imgAlt="Brühlgut Stiftung"
                             beneficiaryName="Brühlgut Stiftung"
                             beneficiaryUrl="https://bruehlgut.ch" />

        <x-home-hero-partner assetUrl="resources/images/iks_light.svg" imgAlt="Institut Kinderseele"
                             beneficiaryName="Institut Kinderseele Schweiz"
                             beneficiaryUrl="https://kinderseele.ch" />

        <x-home-hero-partner assetUrl="resources/images/143_light.svg" imgAlt="Dargebotene Hand"
                             beneficiaryName="Dargebotene Hand Winterthur"
                             beneficiaryUrl="https://143.ch" />
    </div>
</div>
