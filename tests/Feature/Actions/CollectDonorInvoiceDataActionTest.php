<?php

use App\Actions\CollectDonorInvoiceDataAction;
use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Partner;
use App\Models\SportType;

it('resolves CollectDonorInvoiceDataAction from the container', function (): void {
    $action = app(CollectDonorInvoiceDataAction::class);

    expect($action)->toBeInstanceOf(CollectDonorInvoiceDataAction::class);
});

dataset('invoice_line_cases', [
    ['rounds' => 10, 'perRound' => 2.5, 'min' => null, 'max' => null, 'expectedTotal' => 25.00, 'expectedSubtotal' => 25.00],
    ['rounds' => 3, 'perRound' => 2.0, 'min' => 10.0, 'max' => null, 'expectedTotal' => 10.00, 'expectedSubtotal' => 6.00],
    ['rounds' => 20, 'perRound' => 3.0, 'min' => null, 'max' => 50.0, 'expectedTotal' => 50.00, 'expectedSubtotal' => 60.00],
    ['rounds' => 5, 'perRound' => 4.0, 'min' => 10.0, 'max' => 30.0, 'expectedTotal' => 20.00, 'expectedSubtotal' => 20.00],
    ['rounds' => 1, 'perRound' => 5.0, 'min' => 10.0, 'max' => 30.0, 'expectedTotal' => 10.00, 'expectedSubtotal' => 5.00],
    ['rounds' => 20, 'perRound' => 2.0, 'min' => 10.0, 'max' => 30.0, 'expectedTotal' => 30.00, 'expectedSubtotal' => 40.00],
    ['rounds' => 0, 'perRound' => 7.0, 'min' => null, 'max' => null, 'expectedTotal' => 0.00, 'expectedSubtotal' => 0.00],
    ['rounds' => 0, 'perRound' => 7.0, 'min' => 5.0, 'max' => null, 'expectedTotal' => 5.00, 'expectedSubtotal' => 0.00],
]);

describe('collect donor invoice data', function () {
    it('calculates line totals and applies min/max caps', function (
        int $rounds,
        float $perRound,
        ?float $min,
        ?float $max,
        float $expectedTotal,
        float $expectedSubtotal,
    ): void {
        $partner = Partner::query()->create(['name' => 'ACME']);
        $athlete = Athlete::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Doe',
            'partner_id' => $partner->id,
            'sport_type_id' => SportType::query()->create(['name' => 'Run'])->id,
            'verified' => true,
            'rounds_done' => $rounds,
        ]);

        $donor = Donor::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);

        Donation::query()->create([
            'donator_id' => $donor->id,
            'athlete_id' => $athlete->id,
            'amount_per_round' => $perRound,
            'amount_min' => $min,
            'amount_max' => $max,
        ]);

        $lines = app(CollectDonorInvoiceDataAction::class)($donor);

        expect($lines)->toHaveCount(1);

        $line = $lines[0];

        expect($line['athlete'])->toBe('Alice D.')
            ->and($line['partner'])->toBe('ACME')
            ->and($line['rounds'])->toBe($rounds)
            ->and($line['amount_per_round'])->toBe(round($perRound, 2))
            ->and($line['subtotal'])->toBe(round($expectedSubtotal, 2))
            ->and($line['min'])->toBe($min !== null ? round($min, 2) : null)
            ->and($line['max'])->toBe($max !== null ? round($max, 2) : null)
            ->and($line['total'])->toBe(round($expectedTotal, 2));
    })->with('invoice_line_cases');

    it('handles multiple donations for the same donor', function (): void {
        $partner = Partner::query()->create(['name' => 'Globex']);
        SportType::query()->insert([
            ['name' => 'Swim'],
            ['name' => 'Bike'],
        ]);

        $athletes = Athlete::factory()->createMany([
            [
                'first_name' => 'Bob',
                'last_name' => 'Roe',
                'partner_id' => $partner->id,
                'sport_type_id' => SportType::first()->id,
                'verified' => true,
                'rounds_done' => 12,
            ],
            [
                'first_name' => 'Carol',
                'last_name' => 'Moe',
                'partner_id' => $partner->id,
                'sport_type_id' => SportType::skip(1)->first()->id,
                'verified' => true,
                'rounds_done' => 3,
            ],
        ]);

        $donor = Donor::factory()->create();

        Donation::query()->insert([
            [
                'donator_id' => $donor->id,
                'athlete_id' => $athletes[0]->id,
                'amount_per_round' => 2.0,
                'amount_min' => null,
                'amount_max' => 30.0,
            ],
            [
                'donator_id' => $donor->id,
                'athlete_id' => $athletes[1]->id,
                'amount_per_round' => 5.0,
                'amount_min' => 20.0,
                'amount_max' => null,
            ],
        ]);

        $lines = app(CollectDonorInvoiceDataAction::class)($donor);

        expect($lines)->toHaveCount(2)
            ->and($lines[0]['total'])->toBe(24.00)
            ->and($lines[1]['total'])->toBe(20.00);
    });
});
