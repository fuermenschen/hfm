<?php

use App\Services\Infomaniak\InfomaniakNewsletterService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    config([
        'services.infomaniak_newsletter.token' => 'test-token',
        'services.infomaniak_newsletter.domain_id' => 12345,
        'services.infomaniak_newsletter.group_id' => 275443,
        'services.infomaniak_newsletter.base_url' => 'https://api.infomaniak.com',
    ]);
});

it('creates a new subscriber with the target group when email is unknown', function (): void {
    Http::fake([
        'https://api.infomaniak.com/1/newsletters/12345/subscribers/filter' => Http::response([
            'result' => 'success',
            'data' => [],
        ], 200),
        'https://api.infomaniak.com/1/newsletters/12345/subscribers' => Http::response([
            'result' => 'success',
            'data' => ['id' => 99],
        ], 200),
    ]);

    $service = app(InfomaniakNewsletterService::class);
    $alreadyRegistered = $service->registerSubscriber('Anna', 'anna@example.com');

    expect($alreadyRegistered)->toBeFalse();

    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://api.infomaniak.com/1/newsletters/12345/subscribers') {
            return false;
        }

        return $request['email'] === 'anna@example.com'
            && $request['fields']['firstname'] === 'Anna'
            && $request['groups'] === [275443];
    });
});

it('assigns an existing subscriber to the target group', function (): void {
    Http::fake([
        'https://api.infomaniak.com/1/newsletters/12345/subscribers/filter' => Http::response([
            'result' => 'success',
            'data' => [
                ['id' => 42, 'email' => 'anna@example.com'],
            ],
        ], 200),
        'https://api.infomaniak.com/1/newsletters/12345/groups/275443/subscribers/assign' => Http::response([
            'result' => 'success',
            'data' => true,
        ], 200),
    ]);

    $service = app(InfomaniakNewsletterService::class);
    $alreadyRegistered = $service->registerSubscriber('Anna', 'anna@example.com');

    expect($alreadyRegistered)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.infomaniak.com/1/newsletters/12345/groups/275443/subscribers/assign'
            && $request['subscriber_ids'] === [42];
    });

    Http::assertNotSent(function ($request) {
        return $request->url() === 'https://api.infomaniak.com/1/newsletters/12345/subscribers';
    });
});

it('validates email and first name before sending API calls', function (): void {
    Http::fake();

    $service = app(InfomaniakNewsletterService::class);
    $service->registerSubscriber('', 'invalid-email');
})->throws(ValidationException::class);

it('retries subscriber creation without fields on validation failure', function (): void {
    Http::fake([
        'https://api.infomaniak.com/1/newsletters/12345/subscribers/filter' => Http::response([
            'result' => 'success',
            'data' => [],
        ], 200),
        'https://api.infomaniak.com/1/newsletters/12345/subscribers' => Http::sequence()
            ->push([
                'result' => 'error',
                'error' => [
                    'code' => 'validation_failed',
                ],
            ], 422)
            ->push([
                'result' => 'success',
                'data' => ['id' => 123],
            ], 200),
    ]);

    $service = app(InfomaniakNewsletterService::class);
    $alreadyRegistered = $service->registerSubscriber('Anna', 'anna@example.com');

    expect($alreadyRegistered)->toBeFalse();

    Http::assertSentCount(3);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.infomaniak.com/1/newsletters/12345/subscribers'
            && ! isset($request['fields'])
            && $request['groups'] === [275443];
    });
});

it('unsubscribes a subscriber by email when unsubscribing', function (): void {
    Http::fake([
        'https://api.infomaniak.com/1/newsletters/12345/subscribers/filter' => Http::response([
            'result' => 'success',
            'data' => [
                ['id' => 42, 'email' => 'anna@example.com'],
            ],
        ], 200),
        'https://api.infomaniak.com/1/newsletters/12345/subscribers/unsubscribe' => Http::response([
            'result' => 'success',
            'data' => true,
        ], 200),
    ]);

    $service = app(InfomaniakNewsletterService::class);
    $service->unsubscribeSubscriber('anna@example.com');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.infomaniak.com/1/newsletters/12345/subscribers/unsubscribe'
            && $request->method() === 'PUT'
            && $request['select']['all'] === false
            && $request['select']['include'] === [42];
    });
});
