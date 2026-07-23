@props(['img' => null])

@php
  // Pick a random hero image if none is provided by the caller.
  // Rationale: keeping this default here makes the component self-contained and reusable;
  // controllers/routes can still override by passing an explicit `img` prop
  // (e.g. @component('components.home-hero', ['img' => '7']) @endcomponent).
  // If you prefer strict separation, you can move this selection into the controller/route
  // and pass `$img` down; in that case remove this block to avoid double-randomizing.
  if ($img === null || $img === '') {
    $img = (string) random_int(2, 14);
  }

  $currentEventPartners ??= collect();

  $partnerLayoutClass = match ($currentEventPartners->count()) {
    1 => 'max-w-20 sm:max-w-28',
    2, 4 => 'max-w-[11.5rem] sm:max-w-[19rem]',
    default => 'max-w-72 sm:max-w-[31rem]',
  };

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
    {{ $currentDonationEvent?->title ?? 'Höhenmeter für Menschen' }}
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
      <div class="mx-auto w-full">
        <h3 class="text-xs opacity-90 sm:text-sm">Unsere Benefizpartner:innen</h3>

        <div class="mx-auto mt-4 flex flex-wrap justify-center gap-x-6 gap-y-4 sm:gap-x-20 {{ $partnerLayoutClass }}">
          @foreach ($currentEventPartners as $partner)
            <x-home-hero-partner :assetUrl="$partner->logoLightUrl()"
                                 :assetUrlDark="$partner->logoDarkUrl()"
                                 :imgAlt="'Logo '.$partner->name"
                                 :beneficiaryUrl="$partner->url" />
          @endforeach
        </div>
      </div>
    @endif
  </x-slot:partners>
</x-hero>
