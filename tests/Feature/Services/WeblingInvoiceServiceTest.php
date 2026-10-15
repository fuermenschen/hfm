<?php

use App\Exceptions\Webling\WeblingApiException;
use App\Services\Webling\Invoice\Dto\InvoiceCreateData;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Services\Webling\WeblingApiService;
use App\Settings\WeblingApiSettings;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

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
        'type' => 'debitor',
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

/** @return array<string, mixed> */
function weblingSettlementFixture(): array
{
    $payment = [
        'type' => 'entry',
        'id' => 20,
        'properties' => ['amount' => 100],
        'parents' => [30],
        'links' => ['debit' => [3], 'credit' => [2], 'debitor' => [42]],
    ];

    return [
        'invoice' => [
            'type' => 'debitor',
            'parents' => [10],
            'properties' => ['state' => 'paid', 'debitorid' => 322, 'duedate' => '2026-10-07', 'totalamount' => 100, 'remainingamount' => 0, 'paidamount' => 100, 'writeoffamount' => 0],
            'links' => [
                'payment' => [$payment],
                'revenue' => [['id' => 11, 'properties' => ['amount' => 100], 'links' => ['debit' => [2], 'credit' => [8], 'debitor' => [42]]]],
                'writeoff' => [],
            ],
        ],
        'booking' => [
            'type' => 'entrygroup',
            'parents' => [10],
            'properties' => ['date' => '2026-10-05', 'amountCache' => '{"total":999}'],
            'children' => ['entry' => [20]],
        ],
        'entries' => [$payment],
    ];
}

/**
 * @param  array<string, mixed>  $fixture
 * @param  array<string, mixed>  $responses
 */
function fakeWeblingSettlement(array $fixture, array $responses = []): void
{
    Http::preventStrayRequests();
    $entryIds = $fixture['booking']['children']['entry'];
    $entries = count($fixture['entries']) === 1
        ? Arr::except($fixture['entries'][0], ['id'])
        : $fixture['entries'];
    Http::fake($responses + [
        'https://demo.webling.ch/api/1/debitor/42' => Http::response($fixture['invoice']),
        'https://demo.webling.ch/api/1/entrygroup/30' => Http::response($fixture['booking']),
        'https://demo.webling.ch/api/1/entry/'.implode(',', $entryIds) => Http::response($entries),
    ]);
}

describe('bank settlement evidence', function (): void {
    beforeEach(function (): void {
        WeblingApiSettings::fake([
            'api_url' => 'https://demo.webling.ch',
            'api_key' => 'fake-key',
            'accounting_period_id' => 10,
            'debit_account_id' => 2,
            'credit_account_id' => 8,
        ]);
    });

    it('normalizes a single bank receipt using its booking date rather than invoice dates', function (): void {
        fakeWeblingSettlement(weblingSettlementFixture());

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 3);

        expect($details)->toBe([
            'state' => 'paid', 'due_date' => '2026-10-07', 'invoice_number' => '322',
            'total_cents' => 10000, 'remaining_cents' => 0,
            'payment_verification' => 'verified', 'received_cents' => 10000, 'payment_date' => '2026-10-05',
            'excess_cents' => 0, 'writeoff_cents' => 0,
        ]);
        Http::assertSentCount(3);
    });

    it('includes allowed excess bank receipts and normalizes unordered multiget objects', function (): void {
        $fixture = weblingSettlementFixture();
        $fixture['booking']['children']['entry'][] = 21;
        array_unshift($fixture['entries'], [
            'type' => 'entry', 'id' => 21, 'properties' => ['amount' => '10.10'], 'parents' => [30],
            'links' => ['debit' => [3], 'credit' => [9]],
        ]);
        fakeWeblingSettlement($fixture);

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 3, [9]);

        expect($details['received_cents'])->toBe(11010)
            ->and($details['excess_cents'])->toBe(1010)
            ->and($details['writeoff_cents'])->toBe(0)
            ->and($details['payment_verification'])->toBe('verified');
        Http::assertSentCount(3);
    });

    it('accepts accountant-adjusted totals and separately recorded writeoffs', function (array $properties, array $writeoffs): void {
        $fixture = weblingSettlementFixture();
        $fixture['invoice']['properties'] = $properties + $fixture['invoice']['properties'];
        $fixture['invoice']['links']['revenue'][0]['properties']['amount'] = $properties['totalamount'];
        $fixture['invoice']['links']['payment'][0]['properties']['amount'] = '70.00';
        $fixture['invoice']['links']['writeoff'] = $writeoffs;
        $fixture['entries'][0]['properties']['amount'] = 70;
        fakeWeblingSettlement($fixture);

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 3);

        expect($details['received_cents'])->toBe(7000)
            ->and($details['excess_cents'])->toBe(0)
            ->and($details['writeoff_cents'])->toBe((int) (($properties['writeoffamount'] ?? 0) * 100))
            ->and($details['payment_verification'])->toBe('verified');
        Http::assertSentCount(3);
    })->with([
        'adjusted invoice total' => [['totalamount' => 70, 'paidamount' => 70], []],
        'underpayment plus writeoff' => [
            ['totalamount' => 100, 'paidamount' => 70, 'writeoffamount' => 30],
            [['id' => 50, 'properties' => ['amount' => 30], 'links' => ['debit' => [8], 'credit' => [2], 'debitor' => [42]]]],
        ],
    ]);

    it('does not fetch payment evidence for invoices that are not paid', function (string $state): void {
        $fixture = weblingSettlementFixture();
        $fixture['invoice']['properties']['state'] = $state;
        fakeWeblingSettlement($fixture);

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 3);

        expect($details['payment_verification'])->toBe('unverified')
            ->and($details['received_cents'])->toBeNull()
            ->and($details['payment_date'])->toBeNull();
        Http::assertSentCount(1);
    })->with(['open', 'partially paid', 'writeoff', 'unknown']);

    it('reports a fully written-off invoice without treating the writeoff as cash', function (): void {
        $fixture = weblingSettlementFixture();
        $fixture['invoice']['properties']['state'] = 'writeoff';
        $fixture['invoice']['properties']['paidamount'] = 0;
        $fixture['invoice']['properties']['writeoffamount'] = 100;
        fakeWeblingSettlement($fixture);

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 3);

        expect($details['writeoff_cents'])->toBe(10000);
        expect($details['received_cents'])->toBeNull();
        expect($details['excess_cents'])->toBeNull();
        Http::assertSentCount(1);
    });

    it('marks unsupported invoice evidence unclear without further requests', function (string $path, mixed $value): void {
        $fixture = weblingSettlementFixture();
        Arr::set($fixture, $path, $value);
        fakeWeblingSettlement($fixture);

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 3);

        expect($details['payment_verification'])->toBe('unclear')
            ->and($details['received_cents'])->toBeNull()
            ->and($details['payment_date'])->toBeNull();
        Http::assertSentCount(1);
    })->with([
        'no payment' => ['invoice.links.payment', []],
        'wrong linked payment type' => ['invoice.links.payment.0.type', 'entrygroup'],
        'missing linked payment type' => ['invoice.links.payment.0.type', null],
        'missing invoice period' => ['invoice.parents', null],
        'wrong configured period' => ['invoice.parents', [99]],
        'multiple payments' => ['invoice.links.payment', [['id' => 20], ['id' => 21]]],
        'missing payment amount' => ['invoice.links.payment.0.properties.amount', null],
        'settlement mismatch' => ['invoice.links.payment.0.properties.amount', 99],
        'fractional cent' => ['invoice.links.payment.0.properties.amount', '100.001'],
        'boolean amount' => ['invoice.properties.paidamount', true],
        'missing writeoff amount' => ['invoice.properties.writeoffamount', null],
        'negative writeoff' => ['invoice.properties.writeoffamount', -1],
        'unreconciled invoice' => ['invoice.properties.totalamount', 110],
        'outstanding balance' => ['invoice.properties.remainingamount', 10],
        'wrong bank' => ['invoice.links.payment.0.links.debit', [4]],
        'wrong Debitor account' => ['invoice.links.payment.0.links.credit', [4]],
        'wrong invoice' => ['invoice.links.payment.0.links.debitor', [43]],
        'several invoices' => ['invoice.links.payment.0.links.debitor', [42, 43]],
        'multiple parent bookings' => ['invoice.links.payment.0.parents', [30, 31]],
        'unexplained writeoff links' => ['invoice.links.writeoff', [['id' => 50, 'properties' => ['amount' => 30]]]],
    ]);

    it('marks unsupported parent bookings unclear', function (string $path, mixed $value): void {
        $fixture = weblingSettlementFixture();
        Arr::set($fixture, $path, $value);
        fakeWeblingSettlement($fixture);

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 3);

        expect($details['payment_verification'])->toBe('unclear')
            ->and($details['received_cents'])->toBeNull();
        Http::assertSentCount(2);
    })->with([
        'missing date' => ['booking.properties.date', null],
        'impossible date' => ['booking.properties.date', '2026-02-30'],
        'timestamp instead of date' => ['booking.properties.date', '2026-10-05 12:00:00'],
        'different accounting period' => ['booking.parents', [99]],
        'unrelated booking' => ['booking.children.entry', [21]],
        'too many entries' => ['booking.children.entry', [20, 21, 22]],
        'duplicate entry' => ['booking.children.entry', [20, 20]],
    ]);

    it('marks incompatible bank entries unclear', function (string $path, mixed $value): void {
        $fixture = weblingSettlementFixture();
        Arr::set($fixture, $path, $value);
        fakeWeblingSettlement($fixture);

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 3);

        expect($details['payment_verification'])->toBe('unclear')
            ->and($details['received_cents'])->toBeNull();
        Http::assertSentCount(3);
    })->with([
        'changed settlement amount' => ['entries.0.properties.amount', 99],
        'negative receipt' => ['entries.0.properties.amount', -100],
        'wrong bank' => ['entries.0.links.debit', [4]],
        'reverse-direction booking' => ['entries.0.links.debit', [2]],
        'wrong settlement account' => ['entries.0.links.credit', [4]],
        'another invoice' => ['entries.0.links.debitor', [43]],
        'wrong parent' => ['entries.0.parents', [31]],
    ]);

    it('rejects excess with unexplained accounts or another invoice link', function (array $links): void {
        $fixture = weblingSettlementFixture();
        $fixture['booking']['children']['entry'][] = 21;
        $fixture['entries'][] = ['type' => 'entry', 'id' => 21, 'properties' => ['amount' => 10.10], 'parents' => [30], 'links' => $links];
        fakeWeblingSettlement($fixture);

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 3, [9]);

        expect($details['payment_verification'])->toBe('unclear')
            ->and($details['received_cents'])->toBeNull();
        Http::assertSentCount(3);
    })->with([
        'unapproved income' => [['debit' => [3], 'credit' => [8]]],
        'bank transfer' => [['debit' => [3], 'credit' => [3]]],
        'another Debitor settlement' => [['debit' => [3], 'credit' => [2]]],
        'another bank account' => [['debit' => [4], 'credit' => [9]]],
        'another invoice' => [['debit' => [3], 'credit' => [9], 'debitor' => [43]]],
    ]);

    it('distinguishes a missing linked object from a missing invoice', function (string $path, string $exceptionClass): void {
        fakeWeblingSettlement(weblingSettlementFixture(), [
            'https://demo.webling.ch/api/1/'.$path => Http::response([], 404),
        ]);

        expect(fn () => app(WeblingInvoiceService::class)->settlementDetails(42, 3))->toThrow($exceptionClass);
        Http::assertSentCount($path === 'debitor/42' ? 1 : ($path === 'entrygroup/30' ? 2 : 3));
    })->with([
        'invoice 404' => ['debitor/42', WeblingApiException::class],
        'booking 404' => ['entrygroup/30', RuntimeException::class],
        'entry 404' => ['entry/20', RuntimeException::class],
    ]);

    it('propagates failed evidence requests rather than returning unclear', function (mixed $response, string $exceptionClass): void {
        fakeWeblingSettlement(weblingSettlementFixture(), ['https://demo.webling.ch/api/1/entrygroup/30' => $response]);

        expect(fn () => app(WeblingInvoiceService::class)->settlementDetails(42, 3))->toThrow($exceptionClass);
        Http::assertSentCount(2);
    })->with([
        'permission 403' => fn () => [Http::response([], 403), WeblingApiException::class],
        'rate limit 429' => fn () => [Http::response([], 429), WeblingApiException::class],
        'server 503' => fn () => [Http::response([], 503), WeblingApiException::class],
        'connection failure' => fn () => [Http::failedConnection(), ConnectionException::class],
        'unreadable payload' => fn () => [Http::response('not JSON'), RuntimeException::class],
        'incomplete multiget' => fn () => [Http::response([]), RuntimeException::class],
    ]);

    it('rejects invalid invoice amounts instead of silently interpreting them as zero', function (mixed $amount): void {
        $fixture = weblingSettlementFixture();
        $fixture['invoice']['properties']['totalamount'] = $amount;
        fakeWeblingSettlement($fixture);

        expect(fn () => app(WeblingInvoiceService::class)->invoiceDetails(42))->toThrow(RuntimeException::class, 'invalid Debitor amounts');
        Http::assertSentCount(1);
    })->with([null, '', 'invalid', true, '10.001', '1e2', '9223372036854775807']);

    it('keeps invoice-only reads independent of payment evidence', function (): void {
        $fixture = weblingSettlementFixture();
        unset($fixture['invoice']['links']['payment']);
        fakeWeblingSettlement($fixture);

        expect(app(WeblingInvoiceService::class)->invoiceDetails(42)['state'])->toBe('paid');
        Http::assertSentCount(1);
    });

    it('rejects wrong or missing Debitor types even when invoice properties look valid', function (?string $type): void {
        $fixture = weblingSettlementFixture();
        $fixture['invoice']['type'] = $type;
        fakeWeblingSettlement($fixture);

        expect(fn () => app(WeblingInvoiceService::class)->invoiceDetails(42))
            ->toThrow(RuntimeException::class, 'invalid Debitor type');
        Http::assertSentCount(1);
    })->with(['wrong type' => 'entry', 'missing type' => null]);

    it('marks matching invoice and booking periods unclear when outside the configured period', function (): void {
        $fixture = weblingSettlementFixture();
        $fixture['invoice']['parents'] = [99];
        $fixture['booking']['parents'] = [99];
        fakeWeblingSettlement($fixture);

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 3);

        expect($details['payment_verification'])->toBe('unclear')
            ->and($details['received_cents'])->toBeNull()
            ->and($details['payment_date'])->toBeNull();
        Http::assertSentCount(1);
    });

    it('keeps historical invoice status readable outside the configured accounting period', function (): void {
        $fixture = weblingSettlementFixture();
        $fixture['invoice']['parents'] = [99];
        fakeWeblingSettlement($fixture);

        $details = app(WeblingInvoiceService::class)->invoiceDetails(42);

        expect($details['state'])->toBe('paid')
            ->and($details['total_cents'])->toBe(10000);
        Http::assertSentCount(1);
    });

    it('normalizes numeric string IDs and wrapped multiget responses', function (): void {
        $fixture = weblingSettlementFixture();
        $fixture['invoice']['parents'] = ['10'];
        $fixture['invoice']['links']['payment'][0]['id'] = '20';
        $fixture['invoice']['links']['payment'][0]['parents'] = ['30'];
        $fixture['invoice']['links']['payment'][0]['links'] = ['debit' => ['3'], 'credit' => ['2'], 'debitor' => ['42']];
        fakeWeblingSettlement($fixture, [
            'https://demo.webling.ch/api/1/entry/20' => Http::response(['objects' => $fixture['entries']]),
        ]);

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 3);

        expect($details['payment_verification'])->toBe('verified')
            ->and($details['received_cents'])->toBe(10000);
        Http::assertSentCount(3);
    });

    it('does not verify without an explicitly identified bank account', function (): void {
        fakeWeblingSettlement(weblingSettlementFixture());

        $details = app(WeblingInvoiceService::class)->settlementDetails(42, 0);

        expect($details['payment_verification'])->toBe('unclear');
        Http::assertSentCount(1);
    });

    it('fails refresh on inconsistent or incomplete entry responses', function (array $entries): void {
        fakeWeblingSettlement(weblingSettlementFixture(), [
            'https://demo.webling.ch/api/1/entry/20' => Http::response($entries),
        ]);

        expect(fn () => app(WeblingInvoiceService::class)->settlementDetails(42, 3))->toThrow(RuntimeException::class);
        Http::assertSentCount(3);
    })->with([
        'missing entry' => [[]],
        'wrong entry ID' => [[['type' => 'entry', 'id' => 21, 'properties' => ['amount' => 100]]]],
        'wrong object type' => [[['type' => 'entrygroup', 'id' => 20, 'properties' => ['amount' => 100]]]],
        'duplicate entries' => [[
            ['type' => 'entry', 'id' => 20, 'properties' => ['amount' => 100]],
            ['type' => 'entry', 'id' => 20, 'properties' => ['amount' => 100]],
        ]],
    ]);

    it('rejects malformed invoice due dates instead of passing them to persistence', function (): void {
        $fixture = weblingSettlementFixture();
        $fixture['invoice']['properties']['duedate'] = '2026-02-30';
        fakeWeblingSettlement($fixture);

        expect(fn () => app(WeblingInvoiceService::class)->invoiceDetails(42))->toThrow(RuntimeException::class, 'invalid Debitor due date');
        Http::assertSentCount(1);
    });
});
