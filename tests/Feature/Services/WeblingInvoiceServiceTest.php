<?php

use App\Services\Webling\Dto\InvoiceCreateData;
use App\Services\Webling\WeblingApiService;
use App\Services\Webling\WeblingInvoiceService;
use App\Settings\WeblingApiSettings;
use Carbon\Carbon;
use Webling\API\IResponse;

it('builds payload and posts to debitor using DTO', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
        'accounting_period_id' => 321,
        'debit_account_id' => 777,
        'credit_account_id' => 555,
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $fakeResponse = Mockery::mock(IResponse::class);

    $api->shouldReceive('post')->once()->withArgs(function (string $path, array $payload): bool {
        expect($path)->toBe('debitor')
            ->and($payload['properties']['title'])->toBe('Invoice Title')
            ->and($payload['properties']['date'])->toBe('2025-09-05')
            ->and($payload['properties']['duedate'])->toBe('2025-09-10')
            ->and($payload['properties']['address'])->toBe("John Doe\nStreet 1\n8000 Zurich")
            ->and($payload['parents'])->toBe([123])
            ->and($payload['links']['revenue'])->toHaveCount(2);

        $rev0 = $payload['links']['revenue'][0];
        expect($rev0['properties']['amount'])->toBe(150.0)
            ->and($rev0['properties']['title'])->toBe('Line A')
            ->and($rev0['parents'][0]['properties']['date'])->toBe('2025-09-05')
            ->and($rev0['parents'][0]['parents'])->toBe([321])
            ->and($rev0['links']['credit'])->toBe([555])
            ->and($rev0['links']['debit'])->toBe([777]);

        $rev1 = $payload['links']['revenue'][1];
        expect($rev1['properties']['amount'])->toBe(120.0)
            ->and($rev1['properties']['title'])->toBe('Line B');

        return true;
    })->andReturn($fakeResponse);

    $service = new WeblingInvoiceService($api, app(WeblingApiSettings::class));

    $dto = new InvoiceCreateData(
        title: 'Invoice Title',
        date: Carbon::parse('2025-09-05'),
        dueDate: Carbon::parse('2025-09-10'),
        addressLines: ['John Doe', 'Street 1', '8000 Zurich'],
        periodId: 123,
        invoiceLines: [
            ['amount' => 150.0, 'title' => 'Line A'],
            ['amount' => 120.0, 'title' => 'Line B'],
        ],
        accountingPeriodId: 321,
        debitAccountId: 777,
        creditAccountId: 555,
    );

    $service->createInvoice($dto);
});

it('accepts array input as well', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
        'accounting_period_id' => 2,
        'debit_account_id' => 3,
        'credit_account_id' => 4,
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $fakeResponse = Mockery::mock(IResponse::class);

    $api->shouldReceive('post')->once()->withArgs(function (string $path, array $payload): bool {
        expect($path)->toBe('debitor')
            ->and($payload['properties']['title'])->toBe('T');

        return true;
    })->andReturn($fakeResponse);

    $service = new WeblingInvoiceService($api, app(WeblingApiSettings::class));

    $service->createInvoice([
        'title' => 'T',
        'date' => '2025-09-01',
        'duedate' => '2025-09-08',
        'address_lines' => ['A'],
        'period_id' => 1,
        'invoice_lines' => [['amount' => 10.0, 'title' => 'x']],
        // leave out accounting ids to test defaulting as well as explicit ones
    ]);
});

it('uses centrally stored settings by default but allows overrides', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
        'accounting_period_id' => 999,
        'debit_account_id' => 111,
        'credit_account_id' => 222,
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $fakeResponse = Mockery::mock(IResponse::class);

    $api->shouldReceive('post')->twice()->withArgs(function (string $path, array $payload): bool {
        expect($path)->toBe('debitor');

        return true;
    })->andReturn($fakeResponse);

    $service = new WeblingInvoiceService($api, app(WeblingApiSettings::class));

    // 1) Defaults when missing
    $service->createInvoice([
        'title' => 'Defaults',
        'date' => '2025-09-01',
        'duedate' => '2025-09-08',
        'address_lines' => ['A'],
        'period_id' => 10,
        'invoice_lines' => [['amount' => 10.0, 'title' => 'x']],
    ]);

    // 2) Overrides when provided
    $service->createInvoice([
        'title' => 'Overrides',
        'date' => '2025-09-01',
        'duedate' => '2025-09-08',
        'address_lines' => ['A'],
        'period_id' => 10,
        'invoice_lines' => [['amount' => 10.0, 'title' => 'x']],
        'accounting_period_id' => 888,
        'debit_account_id' => 333,
        'credit_account_id' => 444,
    ]);
});
