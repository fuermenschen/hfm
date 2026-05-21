<?php

use App\Jobs\RegisterNewsletterSubscriber;
use App\Notifications\NewsletterRegistrationStatusNotification;
use App\Services\Infomaniak\InfomaniakNewsletterService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

it('forwards payload to infomaniak newsletter service', function (): void {
    Notification::fake();

    $service = Mockery::mock(InfomaniakNewsletterService::class);
    $service->shouldReceive('registerSubscriber')
        ->once()
        ->with('Anna', 'anna@example.com')
        ->andReturn(false);

    (new RegisterNewsletterSubscriber('Anna', 'anna@example.com'))->handle($service);

    Notification::assertSentOnDemand(NewsletterRegistrationStatusNotification::class, function ($notification, $channels, $notifiable) {
        return ($notifiable->routes['mail'] ?? null) === 'anna@example.com'
            && $notification->alreadyRegistered === false;
    });
});

it('sends info-only notification when address already exists', function (): void {
    Notification::fake();

    $service = Mockery::mock(InfomaniakNewsletterService::class);
    $service->shouldReceive('registerSubscriber')
        ->once()
        ->with('Anna', 'anna@example.com')
        ->andReturn(true);

    (new RegisterNewsletterSubscriber('Anna', 'anna@example.com'))->handle($service);

    Notification::assertSentOnDemand(NewsletterRegistrationStatusNotification::class, function ($notification, $channels, $notifiable) {
        return ($notifiable->routes['mail'] ?? null) === 'anna@example.com'
            && $notification->alreadyRegistered === true;
    });
});

it('logs an error and skips notifications when api call fails', function (): void {
    Notification::fake();
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'Newsletter registration API call failed.'
                && $context['email'] === 'anna@example.com'
                && $context['first_name'] === 'Anna'
                && $context['error'] === 'API not reachable';
        });

    $service = Mockery::mock(InfomaniakNewsletterService::class);
    $service->shouldReceive('registerSubscriber')
        ->once()
        ->andThrow(new RuntimeException('API not reachable'));

    (new RegisterNewsletterSubscriber('Anna', 'anna@example.com'))->handle($service);

    Notification::assertNothingSent();
});
