<?php

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

it('shows truthful copy when the registration window is not open', function (string $route, string $role, string $closingField, string $expectedIntro, ?string $opensAt, ?string $closesAt, string $expectedStatus, string $expectedDetail): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $event = DonationEvent::factory()->create([
        'title' => 'Anlass 2026',
        'starts_at' => '2026-09-12 12:00:00',
        'ends_at' => '2026-09-12 16:00:00',
        'registration_opens_at' => $opensAt,
        $closingField => $closesAt,
    ]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    get(route($route))
        ->assertSeeText('Anlass 2026')
        ->assertSeeText('12. September 2026')
        ->assertSeeText($expectedIntro)
        ->assertSeeText("Die Anmeldung als {$role} {$expectedStatus}.")
        ->assertSeeText($expectedDetail)
        ->assertSeeText('Newsletter Anmeldung')
        ->assertDontSeeText('Hier bist du goldrichtig zur Anmeldung.')
        ->assertDontSeeText('Wir informieren dich sofort, sobald die Anmeldung startet.')
        ->assertDontSeeText('Mit welcher E-Mail-Adresse möchtest du dich anmelden?');
})->with([
    'athlete' => ['become-athlete', 'Sportler:in', 'athlete_registration_closes_at', 'Du möchtest als Sportler:in dein Bestes geben und damit Winterthurer Benefizpartner:innen unterstützen?'],
    'donor' => ['become-donor', 'Spender:in', 'donor_registration_closes_at', 'Du lässt lieber andere schwitzen und möchtest als Spender:in einen Beitrag für Winterthurer Benefizpartner:innen leisten?'],
])->with([
    'before opening' => ['2026-09-13 09:30:00', '2026-09-14 16:00:00', 'ist aktuell noch nicht offen', 'Die Anmeldung öffnet am 13. September 2026 um 09:30 Uhr.'],
    'after deadline' => ['2026-09-01 00:00:00', '2026-09-12 13:59:59', 'ist für diesen Anlass geschlossen', 'Über unseren Newsletter erhältst du Neuigkeiten zu kommenden Anlässen.'],
    'missing opening' => [null, '2026-09-13 16:00:00', 'ist für diesen Anlass aktuell nicht verfügbar', 'Über unseren Newsletter erhältst du Neuigkeiten zu kommenden Anlässen.'],
    'missing deadline' => ['2026-09-01 00:00:00', null, 'ist für diesen Anlass aktuell nicht verfügbar', 'Über unseren Newsletter erhältst du Neuigkeiten zu kommenden Anlässen.'],
]);

it('shows an open form regardless of event timing or the other registration deadline', function (string $route, string $closingField, string $startsAt, string $endsAt): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $event = DonationEvent::factory()->create([
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'registration_opens_at' => '2026-09-01 00:00:00',
        'athlete_registration_closes_at' => '2026-09-12 13:59:59',
        'donor_registration_closes_at' => '2026-09-12 13:59:59',
        $closingField => '2026-09-12 14:00:00',
    ]);
    AthleteRegistration::factory()->forEvent($event)->verified()->create();
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    get(route($route))
        ->assertSeeText('Mit welcher E-Mail-Adresse möchtest du dich anmelden?')
        ->assertDontSeeText('Newsletter Anmeldung')
        ->assertDontSeeText('ist für diesen Anlass geschlossen.');
})->with([
    'athlete' => ['become-athlete', 'athlete_registration_closes_at'],
    'donor' => ['become-donor', 'donor_registration_closes_at'],
])->with([
    'upcoming event' => ['2026-09-13 12:00:00', '2026-09-13 16:00:00'],
    'live event' => ['2026-09-12 12:00:00', '2026-09-12 16:00:00'],
    'completed event' => ['2026-09-11 12:00:00', '2026-09-11 16:00:00'],
    'exact event end' => ['2026-09-12 12:00:00', '2026-09-12 14:00:00'],
]);
