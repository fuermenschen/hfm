<?php

use App\Components\AdminDonationTable;
use App\Components\AdminExternalUserTable;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    actingAs(User::factory()->create());
});

it('exports selected external users as csv', function (): void {
    $externalUser = ExternalUser::factory()->create();

    Livewire::test(AdminExternalUserTable::class)
        ->set('checkboxValues', [$externalUser->id])
        ->call('exportSelected', 'csv')
        ->assertFileDownloaded();
});

it('returns null for selected donation export without selection', function (): void {
    Livewire::test(AdminDonationTable::class)
        ->call('exportSelected', 'xlsx')
        ->assertReturned(null);
});

it('exports all external users as xlsx even when search is active', function (): void {
    ExternalUser::factory()->create([
        'first_name' => 'Anna',
    ]);

    ExternalUser::factory()->create([
        'first_name' => 'Bea',
    ]);

    Livewire::test(AdminExternalUserTable::class)
        ->set('search', 'Anna')
        ->call('exportAll', 'xlsx')
        ->assertFileDownloaded();
});

it('exports selected donations as csv', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $athleteIdentity = ExternalUser::factory()->create([
        'first_name' => 'Nea',
        'last_name' => 'Athlete',
    ]);
    $athleteRegistration = AthleteRegistration::factory()
        ->forEvent($donationEvent)
        ->forExternalUser($athleteIdentity)
        ->create();
    $donor = ExternalUser::factory()->create();

    $donation = Donation::query()->create([
        'donor_external_user_id' => $donor->id,
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 10,
        'amount_max' => 100,
        'amount_min' => 0,
        'comment' => 'Export test',
    ]);

    Livewire::test(AdminDonationTable::class)
        ->set('checkboxValues', [$donation->id])
        ->call('exportSelected', 'csv')
        ->assertFileDownloaded();
});

it('searches donation table by athlete registration external user name', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $matchingAthleteIdentity = ExternalUser::factory()->create([
        'first_name' => 'Alice',
        'last_name' => 'Runner',
    ]);
    $nonMatchingAthleteIdentity = ExternalUser::factory()->create([
        'first_name' => 'Bob',
        'last_name' => 'Walker',
    ]);

    $matchingRegistration = AthleteRegistration::factory()
        ->forEvent($donationEvent)
        ->forExternalUser($matchingAthleteIdentity)
        ->create();
    $nonMatchingRegistration = AthleteRegistration::factory()
        ->forEvent($donationEvent)
        ->forExternalUser($nonMatchingAthleteIdentity)
        ->create();

    $donor = ExternalUser::factory()->create();

    Donation::factory()
        ->forDonorExternalUser($donor)
        ->forAthleteRegistration($matchingRegistration)
        ->create();
    Donation::factory()
        ->forDonorExternalUser($donor)
        ->forAthleteRegistration($nonMatchingRegistration)
        ->create();

    Livewire::test(AdminDonationTable::class)
        ->set('search', 'Alice')
        ->assertSee('Alice R.')
        ->assertDontSee('Bob W.');
});
