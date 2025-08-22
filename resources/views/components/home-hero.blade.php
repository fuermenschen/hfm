@props(['img' => null])

@php
  // Pick a random hero image if none is provided by the caller.
  // Rationale: keeping this default here makes the component self-contained and reusable;
  // controllers/routes can still override by passing an explicit `img` prop
  // (e.g. @component('components.home-hero', ['img' => '7']) @endcomponent).
  // If you prefer strict separation, you can move this selection into the controller/route
  // and pass `$img` down; in that case remove this block to avoid double-randomizing.
  if ($img === null || $img === '') {
    $img = (string) random_int(1, 14);
  }
@endphp

<x-hero :img="$img">
  <x-slot:kicker>
    <time datetime="2025-09-13">Samstag, 13. September 2025</time> in Winterthur
  </x-slot:kicker>

  <x-slot:title>
    Höhenmeter für&nbsp;Menschen
  </x-slot:title>

  <x-slot:copy>
    Ein Spendenlauf für Winterthur. Wir rennen, fahren, rollen – für lokale Benefizpartner:innen. Bist auch du am Start?
  </x-slot:copy>

  <x-slot:ctas>
    <a href="#info"
       class="rounded-md bg-hfm-red px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-hfm-dark dark:hover:bg-hfm-light">Mehr dazu</a>
    <a href="{{ route('become-donator') }}"
       class="text-xs sm:text-sm font-semibold leading-6">Spender:in werden <span aria-hidden="true">→</span></a>
  </x-slot:ctas>

  <x-slot:partners>
    <div class="grid grid-cols-3 gap-x-4 sm:gap-x-8 gap-y-4 w-full mx-auto">
      <h3 class="col-span-3 text-xs sm:text-sm opacity-90">Unsere Benefizpartner:innen</h3>

      <x-home-hero-partner class="partner-logo"
                           assetUrl="resources/images/bruehlgut_light.svg"
                           assetUrlDark="resources/images/bruehlgut_dark.svg"
                           imgAlt="Logo Brühlgut Stiftung"
                           beneficiaryName="Brühlgut Stiftung"
                           beneficiaryUrl="https://www.xn--brhlgut-o2a.ch/" />

      <x-home-hero-partner class="partner-logo"
                           assetUrl="resources/images/iks_light.svg"
                           assetUrlDark="resources/images/iks_dark.svg"
                           imgAlt="Logo Institut Kinderseele Schweiz"
                           beneficiaryName="Institut Kinderseele Schweiz"
                           beneficiaryUrl="https://kinderseele.ch" />

      <x-home-hero-partner class="partner-logo"
                           assetUrl="resources/images/143_light.svg"
                           assetUrlDark="resources/images/143_dark.svg"
                           imgAlt="Logo Tel. 143 &ndash; Die Dargebotene Hand"
                           beneficiaryName="Tel. 143 &ndash; Die Dargebotene Hand"
                           beneficiaryUrl="https://143.ch" />
    </div>
  </x-slot:partners>
</x-hero>











