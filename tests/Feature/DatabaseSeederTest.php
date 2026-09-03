<?php

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Settings\InvoiceSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Collection;

use function Pest\Laravel\seed;

it('seeds invoice settings defaults unconditionally', function (): void {
    $settings = app(InvoiceSettings::class);
    $settings->creditor_name = 'Existing Organisation';
    $settings->qr_iban = 'CH1234567890123456789';
    $settings->save();

    seed(DatabaseSeeder::class);

    $settings = app(InvoiceSettings::class);

    expect($settings->qr_iban)->toBe('CH2030700114903053924')
        ->and($settings->creditor_name)->toBe('Verein für Menschen')
        ->and($settings->creditor_care_of)->toBe('Kai Frehner')
        ->and($settings->creditor_street)->toBe('Rössligasse')
        ->and($settings->creditor_building_number)->toBe('6')
        ->and($settings->creditor_postal_code)->toBe('8400')
        ->and($settings->creditor_city)->toBe('Winterthur');
});

it('chooses a portal smoke donation without an existing donor pair', function (): void {
    $event = DonationEvent::factory()->create();
    $portalUser = ExternalUser::factory()->create();
    $athleteA = ExternalUser::factory()->create();
    $athleteB = ExternalUser::factory()->create();
    $otherDonor = ExternalUser::factory()->create();
    $registrationA = AthleteRegistration::factory()->forEvent($event)->forExternalUser($athleteA)->create();
    $registrationB = AthleteRegistration::factory()->forEvent($event)->forExternalUser($athleteB)->create();

    Donation::factory()->forPair($otherDonor, $registrationA)->create();
    Donation::factory()->forPair($portalUser, $registrationA)->create();
    Donation::factory()->forPair($otherDonor, $registrationB)->create();

    $seeder = new class extends DatabaseSeeder
    {
        /** @param Collection<int, AthleteRegistration> $registrations */
        public function runPortalScenario(ExternalUser $portalUser, Collection $registrations, DonationEvent $event): void
        {
            $this->seedPortalSmokeScenario($portalUser, $registrations, $event);
        }
    };

    $seeder->runPortalScenario($portalUser, collect([$registrationA, $registrationB]), $event);

    expect(Donation::query()->where('donor_external_user_id', $portalUser->id)->where('athlete_registration_id', $registrationA->id)->count())->toBe(1)
        ->and(Donation::query()->where('donor_external_user_id', $portalUser->id)->where('athlete_registration_id', $registrationB->id)->count())->toBe(1);
});
