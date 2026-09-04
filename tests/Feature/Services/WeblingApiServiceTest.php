<?php

use App\Exceptions\Webling\WeblingApiException;
use App\Services\Webling\WeblingApiService;
use App\Settings\WeblingApiSettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

it('constructs an HTTP client (PendingRequest) from settings and config options', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => '12345678901234567890123456789012',
        'accounting_period_id' => 1,
        'debit_account_id' => 2,
        'credit_account_id' => 3,
    ]);

    config([
        'services.webling.options' => [
            'connecttimeout' => 2,
            'timeout' => 5,
            'useragent' => 'Pest Test Client',
        ],
    ]);

    $service = app(WeblingApiService::class);

    expect($service->client())->toBeInstanceOf(PendingRequest::class);
});

it('throws an exception when Webling settings are missing', function (): void {
    WeblingApiSettings::fake([
        'api_url' => '',
        'api_key' => '',
        'accounting_period_id' => 1,
        'debit_account_id' => 2,
        'credit_account_id' => 3,
    ]);

    app(WeblingApiService::class);
})->throws(InvalidArgumentException::class);

it('throws a RequestException on 5xx responses to protect against missed errors', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => '12345678901234567890123456789012',
        'accounting_period_id' => 1,
        'debit_account_id' => 2,
        'credit_account_id' => 3,
    ]);

    config([
        'services.webling.options' => [
            'connecttimeout' => 2,
            'timeout' => 5,
            'useragent' => 'Pest Test Client',
        ],
    ]);

    Http::fake([
        'https://demo.webling.ch/api/1/*' => Http::response(['error' => 'Server error'], 500),
    ]);

    $service = app(WeblingApiService::class);

    // Any request should throw because ->throw() is enabled on the client
    $service->post('member', ['foo' => 'bar']);
})->throws(RequestException::class);

it('classifies Webling HTTP failures', function (int $status, string $category): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => '12345678901234567890123456789012',
        'accounting_period_id' => 1,
        'debit_account_id' => 2,
        'credit_account_id' => 3,
    ]);

    Http::fake([
        'https://demo.webling.ch/api/1/*' => Http::response(['error' => 'Failure'], $status),
    ]);

    $exception = null;

    try {
        app(WeblingApiService::class)->get('member/1');
    } catch (WeblingApiException $caught) {
        $exception = $caught;
    }

    expect($exception)->toBeInstanceOf(WeblingApiException::class)
        ->and($exception->category)->toBe($category);
})->with([
    'not found' => [404, WeblingApiException::NotFound],
    'authentication' => [401, WeblingApiException::Authentication],
    'permission' => [403, WeblingApiException::Authentication],
    'rate limit' => [429, WeblingApiException::RateLimited],
    'not implemented' => [501, WeblingApiException::Unexpected],
    'transient' => [503, WeblingApiException::Transient],
]);
