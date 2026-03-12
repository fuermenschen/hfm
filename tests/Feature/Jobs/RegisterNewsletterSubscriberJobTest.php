<?php

use App\Jobs\RegisterNewsletterSubscriber;
use App\Services\Infomaniak\InfomaniakNewsletterService;

it('forwards payload to infomaniak newsletter service', function (): void {
    $service = Mockery::mock(InfomaniakNewsletterService::class);
    $service->shouldReceive('registerSubscriber')
        ->once()
        ->with('Anna', 'anna@example.com');

    (new RegisterNewsletterSubscriber('Anna', 'anna@example.com'))->handle($service);
});
