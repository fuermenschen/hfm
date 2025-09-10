<?php

use App\Services\Webling\WeblingApiService;
use App\Settings\WeblingApiSettings;
use Webling\API\Client;

it('constructs a Webling Client from settings and config options', function (): void {
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

    expect($service->client())->toBeInstanceOf(Client::class);
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
