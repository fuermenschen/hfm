@extends('layouts.public')

@section('content')
    <x-page-title>Portal</x-page-title>

    <div class="w-full max-w-4xl mx-auto space-y-10 text-left">
        <p class="text-center">
            {{ $externalUser->first_name !== '' ? 'Hallo '.$externalUser->first_name : 'Hallo' }}
        </p>

        <section class="space-y-4">
            <x-page-subtitle>Ich bin Sportler:in</x-page-subtitle>

            @if ($athleteRegistrationsByEvent->isEmpty())
                <p>Du hast aktuell keine Sportler:innen-Anmeldungen im Portal.</p>
            @else
                @foreach ($athleteRegistrationsByEvent as $eventId => $registrations)
                    <div class="space-y-3">
                        <x-page-subsubtitle>{{ $eventTitles[$eventId] ?? 'Unbekannter Event' }}</x-page-subsubtitle>
                        <ul role="list" class="divide-y divide-gray-900/10 dark:divide-gray-100/30">
                            @foreach ($registrations as $registration)
                                <li class="py-4 space-y-2">
                                    <div class="flex flex-wrap justify-between gap-2">
                                        <span class="font-semibold">{{ $registration->sportType?->name }}</span>
                                        <span class="text-sm text-gray-500">Verifiziert: {{ $registration->verified ? 'Ja' : 'Nein' }}</span>
                                    </div>

                                    @unless ($registration->verified)
                                        <form method="POST" action="{{ route('portal.athlete-registration.confirm.perform', $registration) }}">
                                            @csrf
                                            <flux:button type="submit" variant="primary" size="sm">
                                                Anmeldung bestätigen
                                            </flux:button>
                                        </form>
                                    @endunless

                                    @if ($registration->donations->isEmpty())
                                        <p class="text-sm text-gray-500">Noch keine Spenden für diesen Event.</p>
                                    @else
                                        <ul class="space-y-2 text-sm">
                                            @foreach ($registration->donations as $donation)
                                                <li class="flex flex-wrap justify-between gap-2">
                                                    <span>{{ $donation->donorExternalUser?->privacy_name ?? 'Unbekannte:r Spender:in' }}</span>
                                                    <span>Fr. {{ sprintf('%1.2f', $donation->amount_per_round) }} / Runde</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endif
        </section>

        <section class="space-y-4">
            <x-page-subtitle>Ich spende</x-page-subtitle>

            @if ($donationsAsDonorByEvent->isEmpty())
                <p>Du hast aktuell keine Spenden im Portal.</p>
            @else
                @foreach ($donationsAsDonorByEvent as $eventId => $donations)
                    <div class="space-y-3">
                        <x-page-subsubtitle>{{ $eventTitles[$eventId] ?? 'Unbekannter Event' }}</x-page-subsubtitle>
                        <ul role="list" class="divide-y divide-gray-900/10 dark:divide-gray-100/30">
                            @foreach ($donations as $donation)
                                <li class="py-4 flex flex-wrap justify-between gap-2">
                                    <span>
                                        {{ $donation->athleteRegistration?->externalUser?->privacy_name ?? 'Unbekannte:r Sportler:in' }}
                                    </span>
                                    <span>Fr. {{ sprintf('%1.2f', $donation->amount_per_round) }} / Runde</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endif
        </section>
    </div>
@endsection
