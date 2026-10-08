@props(['event', 'closesAt', 'role'])

<div class="border-hfm-red/40 bg-hfm-red/10 mt-6 mb-9 rounded-lg border px-9 py-6">
    @if ($event->registration_opens_at === null || $closesAt === null)
        <p class="text-hfm-red font-semibold">
            Die Anmeldung als {{ $role }} ist für diesen Anlass aktuell nicht verfügbar.
        </p>
    @elseif (now($event->timezone)->lessThan($event->registration_opens_at))
        <p class="text-hfm-red font-semibold">Die Anmeldung als {{ $role }} ist aktuell noch nicht offen.</p>
        <p class="mt-1">
            Die Anmeldung öffnet am {{ $event->registration_opens_at->translatedFormat('j. F Y') }} um {{ $event->registration_opens_at->format('H:i') }} Uhr.
        </p>
    @else
        <p class="text-hfm-red font-semibold">Die Anmeldung als {{ $role }} ist für diesen Anlass geschlossen.</p>
    @endif
    <p class="mt-1">Über unseren Newsletter erhältst du Neuigkeiten zu kommenden Anlässen.</p>
</div>
