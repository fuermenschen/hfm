<?php

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BrowserTestCase;
use Tests\TestCase;

function eventGroupTestEvent(bool $ended = false): DonationEvent
{
    return DonationEvent::factory()->create([
        'starts_at' => now('Europe/Zurich')->subDay(),
        'ends_at' => $ended ? now('Europe/Zurich')->subSecond() : now('Europe/Zurich')->addDay(),
    ]);
}

function eventGroupTestRegistration(DonationEvent $donationEvent, ?ExternalUser $externalUser = null): AthleteRegistration
{
    return AthleteRegistration::factory()
        ->forEvent($donationEvent)
        ->forExternalUser($externalUser ?? ExternalUser::factory()->create())
        ->verified()
        ->create();
}

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(BrowserTestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser');

pest()->tia()
    ->locally();    // restrict always() to local environments only
