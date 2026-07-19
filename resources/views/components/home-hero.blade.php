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

  $currentEventPartners ??= collect();

  $eventDate = $currentDonationEvent?->starts_at;
  $eventDateTime = $eventDate?->format('Y-m-d');
  $eventDateLabel = $eventDate?->translatedFormat('j. F Y');
  $eventCity = $currentDonationEvent?->location_city;

  $noActiveEventMessage = match ($currentDonationEventIssue ?? null) {
    'missing_current_event' => 'Aktuell ist kein Anlass als aktiv konfiguriert. Wir arbeiten daran und informieren bald über den nächsten Anlass.',
    'current_event_not_found' => 'Der aktuell konfigurierte Anlass wurde nicht gefunden. Wir aktualisieren die Seite in Kürze.',
    'current_event_unpublished' => 'Der aktuell konfigurierte Anlass ist noch nicht veröffentlicht. Informationen folgen in Kürze.',
    default => 'Aktuell sind keine Anlassinformationen verfügbar. Bitte versuche es später erneut.',
  };
@endphp

<x-hero :img="$img">
  <x-slot:kicker>
    @if ($eventDateLabel !== null && $eventCity !== null)
      <time datetime="{{ $eventDateTime }}">{{ $eventDateLabel }}</time> in {{ $eventCity }}
    @else
      Informationen zum nächsten Anlass
    @endif
  </x-slot:kicker>

  <x-slot:title>
    Höhenmeter für&nbsp;Menschen
  </x-slot:title>

  <x-slot:copy>
    @if ($currentDonationEvent !== null)
      {!! $currentDonationEvent->contentInlineMarkdown('hero.copy_md') !!}
    @else
      {{ $noActiveEventMessage }}
    @endif
  </x-slot:copy>

  <x-slot:ctas>
    @if ($currentDonationEvent !== null)
      <a href="#info"
         class="rounded-md bg-hfm-red px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-hfm-dark dark:hover:bg-hfm-light">Mehr dazu</a>
      <a href="{{ route('become-donor') }}"
         class="text-xs sm:text-sm font-semibold leading-6">Spender:in werden <span aria-hidden="true">→</span></a>
    @else
      <a href="{{ route('newsletter') }}"
         class="rounded-md bg-hfm-red px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-hfm-dark dark:hover:bg-hfm-light">Newsletter abonnieren</a>
      <a href="{{ route('contact') }}"
         class="text-xs sm:text-sm font-semibold leading-6">Kontakt <span aria-hidden="true">→</span></a>
    @endif
  </x-slot:ctas>

  <x-slot:partners>
    @if ($currentDonationEvent !== null && $currentEventPartners->isNotEmpty())
      <div class="grid grid-cols-3 gap-x-4 sm:gap-x-8 gap-y-4 w-full mx-auto">
        <h3 class="col-span-3 text-xs sm:text-sm opacity-90">Unsere Benefizpartner:innen</h3>

        @foreach ($currentEventPartners as $partner)
          <x-home-hero-partner class="partner-logo"
                               :assetUrl="$partner->logoLightUrl()"
                               :assetUrlDark="$partner->logoDarkUrl()"
                               :imgAlt="'Logo '.$partner->name"
                               :beneficiaryUrl="$partner->url" />
        @endforeach
      </div>
    @endif
  </x-slot:partners>
</x-hero>

