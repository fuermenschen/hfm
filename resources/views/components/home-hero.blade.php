@props(['img' => null, 'publicDonationEvent' => null, 'publicEventLifecycle' => null])

@php
    use App\Enums\PublicEventLifecycle;

    // Pick a random hero image if none is provided by the caller.
    // Rationale: keeping this default here makes the component self-contained and reusable;
    // controllers/routes can still override by passing an explicit `img` prop
    // (e.g. @component('components.home-hero', ['img' => '7']) @endcomponent).
    // If you prefer strict separation, you can move this selection into the controller/route
    // and pass `$img` down; in that case remove this block to avoid double-randomizing.
    if ($img === null || $img === '') {
        $img = (string) random_int(2, 14);
    }

    $publicEventPartners ??= collect();

    $partnerLayoutClass = match ($publicEventPartners->count()) {
        1 => 'max-w-24 sm:max-w-36',
        2, 4 => 'max-w-[13.5rem] sm:max-w-[23rem]',
        default => 'max-w-84 sm:max-w-[37rem]',
    };

    $eventDate = $publicDonationEvent?->starts_at;
    $eventDateTime = $eventDate?->format('Y-m-d');
    $eventDateLabel = $eventDate?->translatedFormat('j. F Y');
    $eventCity = $publicDonationEvent?->location_city;
    $canRegister = $publicDonationEvent !== null && $publicDonationEvent->id === $currentDonationEvent?->id;
@endphp

<x-hero
    :img="$img"
    :show-badge="$canRegister && $publicEventLifecycle !== PublicEventLifecycle::Completed && ($publicDonationEvent->athleteRegistrationIsOpen() || $publicDonationEvent->donorRegistrationIsOpen())"
>
    <x-slot:kicker>
        @if ($eventDateLabel !== null && $eventCity !== null)
            {{
                match ($publicEventLifecycle) {
                    PublicEventLifecycle::Upcoming => 'Bevorstehender Anlass',
                    PublicEventLifecycle::Live => 'Findet jetzt statt',
                    PublicEventLifecycle::Completed => 'Rückblick',
                }
            }} ·
            <time datetime="{{ $eventDateTime }}">{{ $eventDateLabel }}</time>
            in {{ $eventCity }}
        @else
            Ein Spendenlauf für Winterthur
        @endif
    </x-slot:kicker>

    <x-slot:title>{{ $publicDonationEvent?->title ?? 'Höhenmeter für Menschen' }}</x-slot:title>

    <x-slot:copy>
        @if ($publicEventLifecycle === PublicEventLifecycle::Completed)
            Dieser Anlass ist abgeschlossen. Danke an alle Sportler:innen, Spender:innen und Unterstützer:innen.
        @elseif ($publicEventLifecycle === PublicEventLifecycle::Live)
            Der Anlass findet jetzt statt. Verfolge die aktuellen Resultate.
        @elseif ($publicDonationEvent !== null)
            {!! $publicDonationEvent->contentInlineMarkdown('hero.copy_md') !!}
        @else
            Aktuell ist noch kein Anlass veröffentlicht. Über unseren Newsletter informieren wir dich über kommende
            Anlässe.
        @endif
    </x-slot:copy>

    <x-slot:ctas>
        @if ($publicDonationEvent !== null)
            <a
                href="#info"
                class="bg-hfm-red hover:bg-hfm-dark dark:hover:bg-hfm-light rounded-md px-3.5 py-2.5 text-xs font-semibold text-white shadow-sm sm:text-sm"
            >Mehr dazu</a>
            @if ($canRegister && $publicDonationEvent->donorRegistrationIsOpen())
                <a href="{{ route('become-donor') }}" class="text-xs leading-6 font-semibold sm:text-sm"
                    >Spender:in werden <span aria-hidden="true">→</span></a>
            @elseif ($canRegister && $publicDonationEvent->athleteRegistrationIsOpen())
                <a href="{{ route('become-athlete') }}" class="text-xs leading-6 font-semibold sm:text-sm"
                    >Sportler:in werden <span aria-hidden="true">→</span></a>
            @endif
        @else
            <a
                href="{{ route('newsletter') }}"
                class="bg-hfm-red hover:bg-hfm-dark dark:hover:bg-hfm-light rounded-md px-3.5 py-2.5 text-xs font-semibold text-white shadow-sm sm:text-sm"
            >Newsletter abonnieren</a>
            <a href="{{ route('contact') }}" class="text-xs leading-6 font-semibold sm:text-sm"
                >Kontakt <span aria-hidden="true">→</span></a>
        @endif
    </x-slot:ctas>

    <x-slot:partners>
        @if ($publicDonationEvent !== null && $publicEventPartners->isNotEmpty())
            <div class="mx-auto w-full">
                <h3 class="text-xs opacity-90 sm:text-sm">Unsere Benefizpartner:innen</h3>

                <div class="mx-auto mt-4 flex flex-wrap justify-center gap-x-6 gap-y-4 sm:gap-x-20 {{ $partnerLayoutClass }}">
                    @foreach ($publicEventPartners as $partner)
                        <x-home-hero-partner
                            :assetUrl="$partner->logoLightUrl()"
                            :assetUrlDark="$partner->logoDarkUrl()"
                            :imgAlt="'Logo '.$partner->name"
                            :beneficiaryUrl="$partner->url"
                        />
                    @endforeach
                </div>
            </div>
        @endif
    </x-slot:partners>
</x-hero>
