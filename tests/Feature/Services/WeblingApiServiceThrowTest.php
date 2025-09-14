<?php

use App\Services\Webling\WeblingApiService;
use App\Settings\WeblingApiSettings;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

it('throws RequestException on 4xx/5xx responses', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => '12345678901234567890123456789012',
        'accounting_period_id' => 1,
        'debit_account_id' => 2,
        'credit_account_id' => 3,
    ]);

    // Fake any request to the demo base URL to return 404
    Http::fake([
        'demo.webling.ch/*' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $service = app(WeblingApiService::class);

    // Expect a RequestException when a 404 is encountered
    $this->expectException(RequestException::class);

    $service->get('member/1');
});
