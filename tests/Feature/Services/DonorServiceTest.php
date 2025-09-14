<?php

use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donator;
use App\Models\Partner;
use App\Models\SportType;
use App\Services\DonorService;

it('resolves DonorService from the container', function (): void {
    $service = app(DonorService::class);

    expect($service)->toBeInstanceOf(DonorService::class);
});

it('resolves the same instance (singleton)', function (): void {
    $first = app(DonorService::class);
    $second = app(DonorService::class);

    expect(spl_object_id($first))->toBe(spl_object_id($second));
});

// Dataset for invoice line calculations
// rounds, amount_per_round, min, max, expected_total, expected_subtotal
// Use edge cases for min/max caps and rounding

dataset('invoice_line_cases', [
    // No caps, simple multiplication
    ['rounds' => 10, 'perRound' => 2.5, 'min' => null, 'max' => null, 'expectedTotal' => 25.00, 'expectedSubtotal' => 25.00],

    // Min applies because subtotal below min
    ['rounds' => 3, 'perRound' => 2.0, 'min' => 10.0, 'max' => null, 'expectedTotal' => 10.00, 'expectedSubtotal' => 6.00],

    // Max applies because subtotal above max
    ['rounds' => 20, 'perRound' => 3.0, 'min' => null, 'max' => 50.0, 'expectedTotal' => 50.00, 'expectedSubtotal' => 60.00],

    // Both caps present, subtotal between -> unchanged
    ['rounds' => 5, 'perRound' => 4.0, 'min' => 10.0, 'max' => 30.0, 'expectedTotal' => 20.00, 'expectedSubtotal' => 20.00],

    // Both caps present, below min -> min used
    ['rounds' => 1, 'perRound' => 5.0, 'min' => 10.0, 'max' => 30.0, 'expectedTotal' => 10.00, 'expectedSubtotal' => 5.00],

    // Both caps present, above max -> max used
    ['rounds' => 20, 'perRound' => 2.0, 'min' => 10.0, 'max' => 30.0, 'expectedTotal' => 30.00, 'expectedSubtotal' => 40.00],

    // Zero rounds -> subtotal 0, min may lift
    ['rounds' => 0, 'perRound' => 7.0, 'min' => null, 'max' => null, 'expectedTotal' => 0.00, 'expectedSubtotal' => 0.00],
    ['rounds' => 0, 'perRound' => 7.0, 'min' => 5.0, 'max' => null, 'expectedTotal' => 5.00, 'expectedSubtotal' => 0.00],
]);

// Group collectInvoiceData tests

describe('collectInvoiceData', function () {
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

        $donator = Donator::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);

        Donation::query()->create([
            'donator_id' => $donator->id,
            'athlete_id' => $athlete->id,
            'amount_per_round' => $perRound,
            'amount_min' => $min,
            'amount_max' => $max,
        ]);

        $service = app(DonorService::class);
        $lines = $service->collectInvoiceData($donator);

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

    it('handles multiple donations for the same donator', function (): void {
        $partner = Partner::query()->create(['name' => 'Globex']);
        $athlete1 = Athlete::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Roe',
            'partner_id' => $partner->id,
            'sport_type_id' => SportType::query()->create(['name' => 'Swim'])->id,
            'verified' => true,
            'rounds_done' => 12,
        ]);
        $athlete2 = Athlete::factory()->create([
            'first_name' => 'Carol',
            'last_name' => 'Moe',
            'partner_id' => $partner->id,
            'sport_type_id' => SportType::query()->create(['name' => 'Bike'])->id,
            'verified' => true,
            'rounds_done' => 3,
        ]);

        $donator = Donator::factory()->create();

        Donation::query()->create([
            'donator_id' => $donator->id,
            'athlete_id' => $athlete1->id,
            'amount_per_round' => 2.0,
            'amount_min' => null,
            'amount_max' => 30.0,
        ]);
        Donation::query()->create([
            'donator_id' => $donator->id,
            'athlete_id' => $athlete2->id,
            'amount_per_round' => 5.0,
            'amount_min' => 20.0,
            'amount_max' => null,
        ]);

        $service = app(DonorService::class);
        $lines = $service->collectInvoiceData($donator);

        expect($lines)->toHaveCount(2)
            // First donation: 12 * 2 = 24 (under max 30)
            ->and($lines[0]['total'])->toBe(24.00)
            // Second donation: 3 * 5 = 15 -> min 20 applies
            ->and($lines[1]['total'])->toBe(20.00);
    });
});
