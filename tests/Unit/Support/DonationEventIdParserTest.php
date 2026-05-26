<?php

use App\Models\DonationEvent;
use App\Support\DonationEventIdParser;
use Tests\TestCase;

uses(TestCase::class);

it('normalizes event ids from mixed supported inputs', function (): void {
    $eventA = DonationEvent::factory()->make(['id' => 7]);
    $eventB = DonationEvent::factory()->make(['id' => 12]);

    $normalized = app(DonationEventIdParser::class)([
        $eventA,
        '12',
        $eventB,
        7,
        '0007',
    ]);

    expect($normalized)->toBe([7, 12]);
});

it('drops invalid, non-numeric, and non-positive values', function (): void {
    $normalized = app(DonationEventIdParser::class)([
        null,
        '',
        'abc',
        0,
        -1,
        '0',
        '-5',
        [],
        new stdClass,
    ]);

    expect($normalized)->toBe([]);
});
