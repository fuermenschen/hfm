<?php

use App\Actions\AssignStartNumbersAction;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;

function startNumberTestRegistration(DonationEvent $event, string $firstName, string $lastName, ?int $startNumber = null): AthleteRegistration
{
    $user = ExternalUser::factory()->asAthlete()->create([
        'first_name' => $firstName,
        'last_name' => $lastName,
    ]);

    return AthleteRegistration::factory()
        ->forEvent($event)
        ->forExternalUser($user)
        ->when($startNumber !== null, fn ($factory) => $factory->withStartNumber($startNumber))
        ->create();
}

it('assigns all registrations alphabetically counting up', function (): void {
    $event = DonationEvent::factory()->create();
    $zora = startNumberTestRegistration($event, 'Zora', 'Zimmermann');
    $ada = startNumberTestRegistration($event, 'Ada', 'Albright');
    $bernd = startNumberTestRegistration($event, 'Bernd', 'Berg');

    $assigned = resolve(AssignStartNumbersAction::class)($event, 10, false);

    expect($assigned)->toBe(3)
        ->and($ada->refresh()->start_number)->toBe(10)
        ->and($bernd->refresh()->start_number)->toBe(11)
        ->and($zora->refresh()->start_number)->toBe(12);
});

it('re-assigns existing numbers when assigning all', function (): void {
    $event = DonationEvent::factory()->create();
    $ada = startNumberTestRegistration($event, 'Ada', 'Albright', 99);
    $zora = startNumberTestRegistration($event, 'Zora', 'Zimmermann', 1);

    resolve(AssignStartNumbersAction::class)($event, 1, false);

    expect($ada->refresh()->start_number)->toBe(1)
        ->and($zora->refresh()->start_number)->toBe(2);
});

it('assigns only missing registrations and skips taken numbers', function (): void {
    $event = DonationEvent::factory()->create();
    $ada = startNumberTestRegistration($event, 'Ada', 'Albright', 2);
    $bernd = startNumberTestRegistration($event, 'Bernd', 'Berg');
    $zora = startNumberTestRegistration($event, 'Zora', 'Zimmermann');

    $assigned = resolve(AssignStartNumbersAction::class)($event, 1, true);

    expect($assigned)->toBe(2)
        ->and($ada->refresh()->start_number)->toBe(2)
        ->and($bernd->refresh()->start_number)->toBe(1)
        ->and($zora->refresh()->start_number)->toBe(3);
});

it('rejects an invalid first number', function (int $firstNumber): void {
    $event = DonationEvent::factory()->create();

    expect(fn () => resolve(AssignStartNumbersAction::class)($event, $firstNumber, false))
        ->toThrow(InvalidArgumentException::class);
})->with([[0], [-1], [65536]]);

it('assigns in first-name order to match the displayed privacy names', function (): void {
    $event = DonationEvent::factory()->create();
    $beat = startNumberTestRegistration($event, 'Beat', 'Aab');
    $anna = startNumberTestRegistration($event, 'Anna', 'Ziegler');

    resolve(AssignStartNumbersAction::class)($event, 1, false);

    expect($anna->refresh()->start_number)->toBe(1)
        ->and($beat->refresh()->start_number)->toBe(2);
});

it('returns zero when there is nothing to assign', function (): void {
    $event = DonationEvent::factory()->create();

    expect(resolve(AssignStartNumbersAction::class)($event, 1, true))->toBe(0);
});
