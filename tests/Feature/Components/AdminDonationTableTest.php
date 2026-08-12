<?php

use App\Components\AdminDonationTable;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Settings\EventSettings;
use Livewire\Livewire;

it('defaults to the current event', function (): void {
    $currentEvent = DonationEvent::factory()->year(2026)->create(['is_published' => true]);
    $otherEvent = DonationEvent::factory()->year(2025)->create(['is_published' => true]);

    $currentAthlete = ExternalUser::factory()->create(['first_name' => 'Current Athlete']);
    $otherAthlete = ExternalUser::factory()->create(['first_name' => 'Other Athlete']);
    $donor = ExternalUser::factory()->create();

    $currentRegistration = AthleteRegistration::factory()->forEvent($currentEvent)->forExternalUser($currentAthlete)->create();
    $otherRegistration = AthleteRegistration::factory()->forEvent($otherEvent)->forExternalUser($otherAthlete)->create();

    Donation::factory()->forDonorExternalUser($donor)->forAthleteRegistration($currentRegistration)->create();
    Donation::factory()->forDonorExternalUser($donor)->forAthleteRegistration($otherRegistration)->create();

    $settings = app(EventSettings::class);
    $settings->current_event_id = $currentEvent->id;
    $settings->save();

    Livewire::test(AdminDonationTable::class)
        ->assertSet('eventSlug', $currentEvent->slug)
        ->assertSee($currentAthlete->first_name)
        ->assertDontSee($otherAthlete->first_name);
});

it('filters donations by event and shows the event column', function (): void {
    $event2025 = DonationEvent::factory()->year(2025)->create();
    $event2026 = DonationEvent::factory()->year(2026)->create();
    $athlete2025 = ExternalUser::factory()->create(['first_name' => 'Athlete2025']);
    $athlete2026 = ExternalUser::factory()->create(['first_name' => 'Athlete2026']);
    $donor = ExternalUser::factory()->create();
    $registration2025 = AthleteRegistration::factory()->forEvent($event2025)->forExternalUser($athlete2025)->create();
    $registration2026 = AthleteRegistration::factory()->forEvent($event2026)->forExternalUser($athlete2026)->create();

    Donation::factory()
        ->forDonorExternalUser($donor)
        ->forAthleteRegistration($registration2025)
        ->create();
    Donation::factory()
        ->forDonorExternalUser($donor)
        ->forAthleteRegistration($registration2026)
        ->create();

    Livewire::test(AdminDonationTable::class)
        ->assertSee('Anlass')
        ->assertSee($athlete2025->first_name)
        ->assertSee($athlete2026->first_name)
        ->set('eventSlug', $event2026->slug)
        ->assertDontSee($athlete2025->first_name)
        ->assertSee($athlete2026->first_name);
});

it('clears donation selection when the event changes', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = AthleteRegistration::factory()->forEvent($event)->create();
    $donation = Donation::factory()
        ->forAthleteRegistration($registration)
        ->create();

    Livewire::test(AdminDonationTable::class)
        ->set('checkboxValues', [$donation->id])
        ->set('eventSlug', $event->slug)
        ->assertSet('checkboxValues', []);
});

it('shows all donations again when the event filter is cleared', function (): void {
    $event = DonationEvent::factory()->create();
    $athlete = ExternalUser::factory()->create(['first_name' => 'VisibleAthlete']);
    $registration = AthleteRegistration::factory()->forEvent($event)->forExternalUser($athlete)->create();

    Donation::factory()
        ->forAthleteRegistration($registration)
        ->create();

    Livewire::test(AdminDonationTable::class)
        ->set('eventSlug', 'invalid')
        ->assertDontSee($athlete->first_name)
        ->assertSee('Keine Spenden für diesen Anlass vorhanden.')
        ->set('eventSlug', null)
        ->assertSee($athlete->first_name);
});
