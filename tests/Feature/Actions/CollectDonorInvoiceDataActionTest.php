<?php

use App\Actions\CollectDonorInvoiceDataAction;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use App\Models\Partner;

it('collects confirmed and unconfirmed donations in integer cents', function (): void {
    $donor = ExternalUser::factory()->create();
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create(['name' => 'ACME']);
    $athlete = ExternalUser::factory()->create(['first_name' => 'Alice', 'last_name' => 'Doe']);
    $registration = AthleteRegistration::factory()
        ->forEvent($event)
        ->forExternalUser($athlete)
        ->withPartner($partner)
        ->create(['rounds_done' => 3]);
    $secondRegistration = AthleteRegistration::factory()
        ->forEvent($event)
        ->forExternalUser(ExternalUser::factory()->create())
        ->withPartner($partner)
        ->create(['rounds_done' => 3]);
    $invoice = DonorEventInvoice::factory()->forExternalUser($donor)->forEvent($event)->create();

    Donation::factory()->forPair($donor, $registration)->create([
        'amount_per_round' => 2.50,
        'amount_min' => 10.00,
        'amount_max' => null,
        'verified' => true,
    ]);
    Donation::factory()->forPair($donor, $secondRegistration)->create([
        'amount_per_round' => 20.00,
        'amount_min' => null,
        'amount_max' => 50.00,
        'verified' => false,
    ]);

    $lines = app(CollectDonorInvoiceDataAction::class)($invoice);

    expect($lines)->toHaveCount(2)
        ->and($lines[0])->toMatchArray([
            'athlete' => 'Alice D.',
            'partner' => 'ACME',
            'rounds' => 3,
            'amount_per_round_cents' => 250,
            'subtotal_cents' => 750,
            'min_cents' => 1000,
            'max_cents' => null,
            'total_cents' => 1000,
        ])
        ->and($lines[1])->toMatchArray([
            'subtotal_cents' => 6000,
            'max_cents' => 5000,
            'total_cents' => 5000,
        ]);
});

it('does not collect donations from another event', function (): void {
    $donor = ExternalUser::factory()->create();
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $invoice = DonorEventInvoice::factory()->forExternalUser($donor)->forEvent($event)->create();
    $registration = AthleteRegistration::factory()->forEvent($event)->create(['rounds_done' => 2]);
    $otherRegistration = AthleteRegistration::factory()->forEvent($otherEvent)->create(['rounds_done' => 9]);

    Donation::factory()->forPair($donor, $registration)->create([
        'amount_per_round' => 3,
        'amount_min' => null,
        'amount_max' => null,
    ]);
    Donation::factory()->forPair($donor, $otherRegistration)->create([
        'amount_per_round' => 8,
        'amount_min' => null,
        'amount_max' => null,
    ]);

    $lines = app(CollectDonorInvoiceDataAction::class)($invoice);

    expect($lines)->toHaveCount(1)
        ->and($lines[0]['total_cents'])->toBe(600);
});
