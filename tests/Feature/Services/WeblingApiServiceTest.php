<?php

use App\Services\Webling\WeblingApiService;
use Webling\API\Client;

it('constructs a Webling Client from configuration', function (): void {
    config([
        'services.webling.base_url' => 'https://demo.webling.ch',
        'services.webling.api_key' => '12345678901234567890123456789012',
        'services.webling.options' => [
            'connecttimeout' => 2,
            'timeout' => 5,
            'useragent' => 'Pest Test Client',
        ],
    ]);

    $service = app(WeblingApiService::class);

    expect($service->client())->toBeInstanceOf(Client::class);
});

it('throws an exception when Webling config is missing', function (): void {
    config([
        'services.webling.base_url' => '',
        'services.webling.api_key' => '',
        'services.webling.options' => [],
    ]);

    new WeblingApiService(app('config'));
})->throws(InvalidArgumentException::class);
