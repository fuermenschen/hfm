<?php

use App\Services\Webling\Invoice\Dto\InvoiceCreateData;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Services\Webling\WeblingApiService;
use App\Settings\WeblingApiSettings;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;

it('builds payload and posts to debitor using DTO', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
        'accounting_period_id' => 321,
        'debit_account_id' => 777,
        'credit_account_id' => 555,
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $fakeResponse = Mockery::mock(Response::class);

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
            ['amount_cents' => 15000, 'title' => 'Line A'],
            ['amount_cents' => 12000, 'title' => 'Line B'],
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
    $fakeResponse = Mockery::mock(Response::class);

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
        'invoice_lines' => [['amount_cents' => 1000, 'title' => 'x']],
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
    $fakeResponse = Mockery::mock(Response::class);

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
        'invoice_lines' => [['amount_cents' => 1000, 'title' => 'x']],
    ]);

    // 2) Overrides when provided
    $service->createInvoice([
        'title' => 'Overrides',
        'date' => '2025-09-01',
        'duedate' => '2025-09-08',
        'address_lines' => ['A'],
        'period_id' => 10,
        'invoice_lines' => [['amount_cents' => 1000, 'title' => 'x']],
        'accounting_period_id' => 888,
        'debit_account_id' => 333,
        'credit_account_id' => 444,
    ]);
});

it('indexes all debitors when no filter is provided', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $fakeResponse = Mockery::mock(Response::class);

    $api->shouldReceive('get')->once()->with('debitor')->andReturn($fakeResponse);

    $service = new WeblingInvoiceService($api, app(WeblingApiSettings::class));

    $service->index();
});

it('builds correct query string when passing raw filter string', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $fakeResponse = Mockery::mock(Response::class);

    $filter = '`state`!="paid"AND`duedate`<TODAY()';
    $expectedPath = 'debitor?filter='.rawurlencode($filter);

    $api->shouldReceive('get')->once()->with($expectedPath)->andReturn($fakeResponse);

    $service = new WeblingInvoiceService($api, app(WeblingApiSettings::class));

    $service->index($filter);
});

it('builds filter from associative array (equals conditions)', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $fakeResponse = Mockery::mock(Response::class);

    $conditions = [
        'state' => 'paid',
        'period_id' => 10,
    ];

    $filter = '`state`="paid"AND`period_id`=10';
    $expectedPath = 'debitor?filter='.rawurlencode($filter);

    $api->shouldReceive('get')->once()->with($expectedPath)->andReturn($fakeResponse);

    $service = new WeblingInvoiceService($api, app(WeblingApiSettings::class));

    $service->index($conditions);
});

it('builds filter from triplet conditions', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $fakeResponse = Mockery::mock(Response::class);

    $conditions = [
        ['state', '!=', 'paid'],
        ['duedate', '<', 'TODAY()'],
    ];

    $filter = '`state`!="paid"AND`duedate`<TODAY()';
    $expectedPath = 'debitor?filter='.rawurlencode($filter);

    $api->shouldReceive('get')->once()->with($expectedPath)->andReturn($fakeResponse);

    $service = new WeblingInvoiceService($api, app(WeblingApiSettings::class));

    $service->index($conditions);
});

it('writes a stable marker when creating an invoice for a local invoice', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
        'accounting_period_id' => 321,
        'debit_account_id' => 777,
        'credit_account_id' => 555,
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $response = Mockery::mock(Response::class);
    $marker = 'HFM-DONOR-INVOICE:123';

    $api->shouldReceive('post')->once()->withArgs(function (string $path, array $payload) use ($marker): bool {
        expect($path)->toBe('debitor')
            ->and($payload['properties']['comment'])->toBe($marker);

        return true;
    })->andReturn($response);

    $service = new WeblingInvoiceService($api, app(WeblingApiSettings::class));

    $service->createInvoiceWithMarker(123, [
        'title' => 'Invoice',
        'date' => '2025-09-01',
        'duedate' => '2025-09-08',
        'address_lines' => ['A'],
        'period_id' => 1,
        'invoice_lines' => [['amount_cents' => 1000, 'title' => 'Line']],
    ]);

    expect($service->commentMarker(123))->toBe($marker);
});

it('finds only full Debitors with an exact comment marker', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $response = Mockery::mock(Response::class);
    $marker = 'HFM-DONOR-INVOICE:123';
    $filter = rawurlencode('`comment`="'.$marker.'"');

    $api->shouldReceive('get')
        ->once()
        ->with('debitor?format=full&filter='.$filter)
        ->andReturn($response);
    $response->shouldReceive('json')->once()->andReturn([
        'objects' => [
            ['id' => 45, 'properties' => ['comment' => $marker]],
            ['id' => 46, 'properties' => ['comment' => 'HFM-DONOR-INVOICE:1234']],
        ],
    ]);

    $service = new WeblingInvoiceService($api, app(WeblingApiSettings::class));

    expect($service->findInvoiceIdsByCommentMarker($marker))->toBe([45]);
});

it('finds exact markers in Webling direct-list responses', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $response = Mockery::mock(Response::class);
    $marker = 'HFM-DONOR-INVOICE:123';

    $api->shouldReceive('get')->once()->andReturn($response);
    $response->shouldReceive('json')->once()->andReturn([
        ['id' => 45, 'properties' => ['comment' => $marker]],
        ['id' => 46, 'properties' => []],
        ['id' => 47, 'properties' => ['comment' => 'HFM-DONOR-INVOICE:1234']],
    ]);

    $service = new WeblingInvoiceService($api, app(WeblingApiSettings::class));

    expect($service->findInvoiceIdsByCommentMarker($marker))->toBe([45]);
});

it('rejects an invoice response without a Webling state', function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
    ]);

    $api = Mockery::mock(WeblingApiService::class);
    $response = Mockery::mock(Response::class);
    $api->shouldReceive('get')->once()->with('debitor/42')->andReturn($response);
    $response->shouldReceive('json')->once()->andReturn([
        'properties' => [
            'duedate' => '2026-01-31',
            'totalamount' => 15,
            'remainingamount' => 15,
        ],
    ]);

    $service = new WeblingInvoiceService($api, app(WeblingApiSettings::class));

    expect(fn () => $service->invoiceDetails(42))
        ->toThrow(RuntimeException::class, 'no Debitor state');
});
