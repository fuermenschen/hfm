<?php

use App\Actions\DownloadDonorInvoicePdfAction;
use App\Actions\RefreshDonorInvoiceStatusAction;
use App\Actions\RunDonorInvoiceBulkAction;
use App\Enums\DonorInvoiceStatus;
use App\Enums\DonorPaymentVerification;
use App\Exceptions\DonorInvoiceGuardException;
use App\Exceptions\Webling\WeblingApiException;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Services\DonorInvoiceService;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Settings\WeblingApiSettings;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Pest\Laravel\travel;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Storage::fake('local');
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch', 'api_key' => 'fake-key',
        'accounting_period_id' => 10, 'debit_account_id' => 2, 'credit_account_id' => 8,
        'bank_account_id' => 3, 'excess_income_account_id' => 9,
    ]);
});

function refreshInvoiceFixture(array $overrides = []): DonorEventInvoice
{
    $invoice = DonorEventInvoice::factory()->create($overrides + [
        'webling_debitor_id' => 4321,
        'source_total_cents' => 1500,
        'pdf_disk' => 'local',
        'pdf_path' => 'webling/donor-invoices/'.Str::uuid().'/test.pdf',
    ]);
    Storage::disk('local')->put($invoice->pdf_path, '%PDF-test');

    return $invoice;
}

function refreshWeblingMock(array $details, int $times = 1): WeblingInvoiceService
{
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('settlementDetails')->times($times)->andReturn($details + [
        'payment_verification' => $details['state'] === 'paid' ? 'unclear' : 'unverified',
        'received_cents' => null, 'payment_date' => null,
    ]);

    return $webling;
}

it('updates raw state, amounts, invoice number, and sync time from webling', function (): void {
    $invoice = refreshInvoiceFixture();
    $webling = refreshWeblingMock([
        'state' => 'partially paid',
        'due_date' => '2099-01-31',
        'invoice_number' => '1542',
        'total_cents' => 1500,
        'remaining_cents' => 500,
    ]);

    app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice);

    $invoice->refresh()->load('donationEvent');
    expect($invoice->webling_state)->toBe('partially paid')
        ->and($invoice->webling_due_date->toDateString())->toBe('2099-01-31')
        ->and($invoice->webling_invoice_number)->toBe('1542')
        ->and($invoice->webling_total_cents)->toBe(1500)
        ->and($invoice->webling_remaining_cents)->toBe(500)
        ->and($invoice->webling_synced_at)->not->toBeNull()
        ->and(app(DonorInvoiceService::class)->status($invoice))->toBe(DonorInvoiceStatus::PartiallyPaid);
});

it('marks the invoice remotely deleted on a confirmed 404', function (): void {
    $invoice = refreshInvoiceFixture([
        'webling_received_cents' => 1600, 'webling_payment_date' => '2026-10-05',
        'webling_payment_verification' => DonorPaymentVerification::Verified,
    ]);
    $path = $invoice->pdf_path;
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('settlementDetails')->once()->andThrow(
        new WeblingApiException(new Response(new GuzzleHttp\Psr7\Response(404)), WeblingApiException::NotFound),
    );

    app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice);

    $invoice->refresh();
    expect($invoice->remote_deleted_at)->not->toBeNull()
        ->and($invoice->webling_debitor_id)->toBeNull()
        ->and($invoice->pdf_path)->toBeNull()
        ->and($invoice->webling_received_cents)->toBeNull()
        ->and($invoice->webling_payment_date)->toBeNull()
        ->and($invoice->webling_payment_verification)->toBe(DonorPaymentVerification::Unverified)
        ->and(app(DonorInvoiceService::class)->status($invoice))->toBe(DonorInvoiceStatus::RemoteDeleted);
    Storage::disk('local')->assertMissing($path);
});

it('preserves cached values and reports failure for other webling errors', function (): void {
    $invoice = refreshInvoiceFixture();
    $invoice->forceFill([
        'webling_state' => 'paid', 'webling_total_cents' => 1500, 'webling_remaining_cents' => 0,
        'webling_payment_verification' => DonorPaymentVerification::Verified,
        'webling_received_cents' => 1600, 'webling_payment_date' => '2026-10-05',
        'webling_synced_at' => '2026-10-06 12:00:00',
    ])->save();
    $before = $invoice->refresh()->getAttributes();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('settlementDetails')->once()->andThrow(
        new WeblingApiException(new Response(new GuzzleHttp\Psr7\Response(500)), WeblingApiException::Transient),
    );

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(WeblingApiException::class);

    $invoice->refresh();
    expect($invoice->getAttributes())->toBe($before);
});

it('skips invoices that are not created in webling', function (): void {
    $invoice = refreshInvoiceFixture(['webling_debitor_id' => null]);
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldNotReceive('settlementDetails');

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'nicht erstellt');
});

it('skips remotely deleted invoices', function (): void {
    $invoice = refreshInvoiceFixture();
    $invoice->forceFill(['remote_deleted_at' => now(), 'webling_debitor_id' => null, 'pdf_disk' => null, 'pdf_path' => null])->save();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldNotReceive('settlementDetails');

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'gelöscht');
});

it('refreshes only the given invoice', function (): void {
    $event = DonationEvent::factory()->create();
    $other = DonorEventInvoice::factory()->forEvent($event)->create(['webling_debitor_id' => 9999]);
    $invoice = refreshInvoiceFixture();
    $webling = refreshWeblingMock(['state' => 'paid', 'due_date' => null, 'invoice_number' => '77', 'total_cents' => 1500, 'remaining_cents' => 0]);

    app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($other->refresh()->webling_state)->toBeNull()
        ->and($other->webling_synced_at)->toBeNull();
});

function verifiedRefreshDetails(array $overrides = []): array
{
    return $overrides + [
        'state' => 'paid', 'due_date' => '2026-10-07', 'invoice_number' => '322',
        'total_cents' => 1500, 'remaining_cents' => 0, 'received_cents' => 1600,
        'payment_date' => '2026-10-05', 'payment_verification' => 'verified',
    ];
}

/** @return array<string, mixed> */
function refreshEvidencePayloads(DonorEventInvoice $invoice): array
{
    $debitorId = $invoice->webling_debitor_id;
    $payment = [
        'type' => 'entry', 'id' => 20, 'parents' => [30], 'properties' => ['amount' => 15],
        'links' => ['debit' => [3], 'credit' => [2], 'debitor' => [$debitorId]],
    ];

    return [
        'invoice' => [
            'type' => 'debitor', 'parents' => [10],
            'properties' => ['state' => 'paid', 'debitorid' => 322, 'totalamount' => 15, 'remainingamount' => 0, 'paidamount' => 15, 'writeoffamount' => 0, 'duedate' => '2026-10-07'],
            'links' => [
                'payment' => [$payment], 'writeoff' => [],
                'revenue' => [['id' => 11, 'properties' => ['amount' => 15], 'links' => ['debit' => [2], 'credit' => [8], 'debitor' => [$debitorId]]]],
            ],
        ],
        'booking' => [
            'type' => 'entrygroup', 'parents' => [10], 'properties' => ['date' => '2026-10-05'], 'children' => ['entry' => [20]],
        ],
        'entries' => [$payment],
    ];
}

/**
 * @param  array<string, mixed>  $responses
 * @param  array<string, mixed>|null  $payloads
 */
function fakeRefreshEvidence(DonorEventInvoice $invoice, array $responses = [], ?array $payloads = null): void
{
    $payloads ??= refreshEvidencePayloads($invoice);
    $entryIds = $payloads['booking']['children']['entry'];
    $entries = count($payloads['entries']) === 1 ? $payloads['entries'][0] : $payloads['entries'];
    Http::preventStrayRequests();
    Http::fake($responses + [
        'https://demo.webling.ch/api/1/debitor/'.$invoice->webling_debitor_id => Http::response($payloads['invoice']),
        'https://demo.webling.ch/api/1/entrygroup/30' => Http::response($payloads['booking']),
        'https://demo.webling.ch/api/1/entry/'.implode(',', $entryIds) => Http::response($entries),
    ]);
}

it('fetches all HTTP evidence outside the persistence transaction before changing any cached facts', function (): void {
    $invoice = refreshInvoiceFixture(['webling_state' => 'open', 'webling_synced_at' => '2026-10-04 12:00:00']);
    $before = $invoice->refresh()->getAttributes();
    $transactionLevel = DB::transactionLevel();
    fakeRefreshEvidence($invoice, [
        'https://demo.webling.ch/api/1/entry/20' => function () use ($invoice, $before, $transactionLevel): PromiseInterface {
            expect(DB::transactionLevel())->toBe($transactionLevel);
            expect($invoice->fresh()->getAttributes())->toBe($before);
            expect(Cache::lock('donor-invoice-creation:'.$invoice->id, 120)->get())->toBeFalse();

            return Http::response([
                'type' => 'entry', 'parents' => [30], 'properties' => ['amount' => 15],
                'links' => ['debit' => [3], 'credit' => [2], 'debitor' => [4321]],
            ]);
        },
    ]);

    app(RefreshDonorInvoiceStatusAction::class)($invoice);

    expect($invoice->webling_payment_verification)->toBe(DonorPaymentVerification::Verified)
        ->and($invoice->webling_received_cents)->toBe(1500)
        ->and($invoice->webling_payment_date->toDateString())->toBe('2026-10-05');
    Http::assertSentCount(3);
});

it('preserves previous successful facts when real evidence retrieval fails', function (string $path, mixed $response, string $exceptionClass, int $requests): void {
    $invoice = refreshInvoiceFixture([
        'webling_state' => 'paid', 'webling_received_cents' => 1600, 'webling_payment_date' => '2026-10-05',
        'webling_payment_verification' => DonorPaymentVerification::Verified, 'webling_synced_at' => '2026-10-06 12:00:00',
    ]);
    $before = $invoice->refresh()->getAttributes();
    fakeRefreshEvidence($invoice, ['https://demo.webling.ch/api/1/'.$path => $response]);

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class)($invoice))->toThrow($exceptionClass);

    expect($invoice->refresh()->getAttributes())->toBe($before);
    Storage::disk('local')->assertExists($invoice->pdf_path);
    Http::assertSentCount($requests);
})->with([
    'missing booking' => fn () => ['entrygroup/30', Http::response([], 404), RuntimeException::class, 2],
    'missing entry' => fn () => ['entry/20', Http::response([], 404), RuntimeException::class, 3],
    'permission error' => fn () => ['debitor/4321', Http::response([], 403), WeblingApiException::class, 1],
    'rate limiting' => fn () => ['entrygroup/30', Http::response([], 429), WeblingApiException::class, 2],
    'server error' => fn () => ['entry/20', Http::response([], 503), WeblingApiException::class, 3],
    'timeout' => fn () => ['entrygroup/30', Http::failedConnection(), ConnectionException::class, 2],
    'unreadable invoice' => fn () => ['debitor/4321', Http::response('not JSON'), RuntimeException::class, 1],
]);

it('preserves previous facts and releases the lock when local persistence fails', function (): void {
    $invoice = refreshInvoiceFixture(['webling_synced_at' => '2026-10-06 12:00:00']);
    $before = $invoice->refresh()->getAttributes();
    $webling = refreshWeblingMock(verifiedRefreshDetails());
    DonorEventInvoice::updating(function (DonorEventInvoice $updating) use ($invoice): void {
        if ($updating->id === $invoice->id) {
            throw new RuntimeException('Local persistence failed.');
        }
    });

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(RuntimeException::class, 'Local persistence failed.');

    expect($invoice->refresh()->getAttributes())->toBe($before);
    $lock = Cache::lock('donor-invoice-creation:'.$invoice->id, 120);
    expect($lock->get())->toBeTrue();
    $lock->release();
});

it('persists complete payment facts using explicitly configured accounts', function (): void {
    travelTo('2026-10-09 12:00:00');
    $invoice = refreshInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('settlementDetails')->once()->with(4321, 3, [9])->andReturn(verifiedRefreshDetails());

    app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($invoice->webling_state)->toBe('paid')
        ->and($invoice->webling_total_cents)->toBe(1500)
        ->and($invoice->webling_remaining_cents)->toBe(0)
        ->and($invoice->webling_received_cents)->toBe(1600)
        ->and($invoice->webling_payment_date->toDateString())->toBe('2026-10-05')
        ->and($invoice->webling_payment_verification)->toBe(DonorPaymentVerification::Verified)
        ->and($invoice->webling_synced_at->format('Y-m-d H:i:s'))->toBe('2026-10-09 12:00:00')
        ->and($invoice->source_total_cents)->toBe(1500);
});

it('recomputes corrections on repeated refresh without accumulating received amounts', function (): void {
    $invoice = refreshInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('settlementDetails')->times(3)->andReturn(
        verifiedRefreshDetails(),
        verifiedRefreshDetails(),
        verifiedRefreshDetails(['received_cents' => 1000, 'payment_date' => '2026-10-04']),
    );
    $refresh = app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling]);

    $refresh($invoice);
    $refresh($invoice);
    expect($invoice->webling_received_cents)->toBe(1600);
    $refresh($invoice);

    expect($invoice->webling_received_cents)->toBe(1000)
        ->and($invoice->webling_payment_date->toDateString())->toBe('2026-10-04')
        ->and($invoice->webling_payment_verification)->toBe(DonorPaymentVerification::Verified);
});

it('revokes old verified facts when settlement becomes unclear or unpaid', function (array $details, DonorPaymentVerification $verification): void {
    $invoice = refreshInvoiceFixture([
        'webling_state' => 'paid', 'webling_received_cents' => 1600, 'webling_payment_date' => '2026-10-05',
        'webling_payment_verification' => DonorPaymentVerification::Verified, 'webling_synced_at' => '2026-10-06 12:00:00',
    ]);
    $webling = refreshWeblingMock($details);

    app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($invoice->webling_payment_verification)->toBe($verification)
        ->and($invoice->webling_received_cents)->toBeNull()
        ->and($invoice->webling_payment_date)->toBeNull()
        ->and($invoice->webling_synced_at->format('Y-m-d H:i:s'))->not->toBe('2026-10-06 12:00:00');
})->with([
    'unclear paid settlement' => [verifiedRefreshDetails(['payment_verification' => 'unclear', 'received_cents' => null, 'payment_date' => null]), DonorPaymentVerification::Unclear],
    'reopened invoice' => [verifiedRefreshDetails(['state' => 'open', 'remaining_cents' => 1500, 'payment_verification' => 'unverified', 'received_cents' => null, 'payment_date' => null]), DonorPaymentVerification::Unverified],
]);

it('passes unconfigured bank and excess accounts without inventing account IDs', function (): void {
    app(WeblingApiSettings::class)->bank_account_id = null;
    app(WeblingApiSettings::class)->excess_income_account_id = null;
    $invoice = refreshInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('settlementDetails')->once()->with(4321, 0, [])->andReturn(
        verifiedRefreshDetails(['payment_verification' => 'unclear', 'received_cents' => null, 'payment_date' => null]),
    );

    app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($invoice->webling_payment_verification)->toBe(DonorPaymentVerification::Unclear);
});

it('reloads invoice identity after acquiring the shared lifecycle lock', function (): void {
    $invoice = refreshInvoiceFixture();
    DB::table('donor_event_invoices')->where('id', $invoice->id)->update(['webling_debitor_id' => 9999]);
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('settlementDetails')->once()->with(9999, 3, [9])->andReturn(verifiedRefreshDetails());

    app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($invoice->webling_debitor_id)->toBe(9999)
        ->and($invoice->webling_payment_verification)->toBe(DonorPaymentVerification::Verified);
});

it('does not apply old payment facts or an old 404 to a replacement invoice', function (bool $remoteDeleted): void {
    $invoice = refreshInvoiceFixture();
    $replacementPath = 'webling/donor-invoices/replacement.pdf';
    Storage::disk('local')->put($replacementPath, '%PDF-replacement');
    $replacementFacts = [];
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('settlementDetails')->once()->andReturnUsing(function () use ($invoice, $replacementPath, &$replacementFacts, $remoteDeleted): array {
        $replacement = $invoice->fresh();
        $replacement->forceFill(['webling_debitor_id' => 9999, 'pdf_path' => $replacementPath, 'source_total_cents' => 2500])->save();
        $replacementFacts = $replacement->refresh()->getAttributes();
        if ($remoteDeleted) {
            throw new WeblingApiException(new Response(new GuzzleHttp\Psr7\Response(404)), WeblingApiException::NotFound);
        }

        return verifiedRefreshDetails();
    });

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'während der Aktualisierung geändert');

    expect($invoice->refresh()->getAttributes())->toBe($replacementFacts);
    Storage::disk('local')->assertExists($replacementPath);
})->with(['late facts' => false, 'late 404' => true]);

it('does not restore facts after concurrent deletion', function (): void {
    $invoice = refreshInvoiceFixture();
    $deletedFacts = [];
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('settlementDetails')->once()->andReturnUsing(function () use ($invoice, &$deletedFacts): array {
        $deleted = $invoice->fresh();
        $deleted->markRemotelyDeleted();
        $deletedFacts = $deleted->refresh()->getAttributes();

        return verifiedRefreshDetails();
    });

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'während der Aktualisierung geändert');

    expect($invoice->refresh()->getAttributes())->toBe($deletedFacts);
});

it('rejects late responses after losing ownership of the refresh lock', function (bool $remoteDeleted): void {
    $invoice = refreshInvoiceFixture();
    $before = $invoice->refresh()->getAttributes();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('settlementDetails')->once()->andReturnUsing(function () use ($invoice, $remoteDeleted): array {
        Cache::lock('donor-invoice-creation:'.$invoice->id)->forceRelease();
        if ($remoteDeleted) {
            throw new WeblingApiException(new Response(new GuzzleHttp\Psr7\Response(404)), WeblingApiException::NotFound);
        }

        return verifiedRefreshDetails();
    });

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(LockTimeoutException::class);

    expect($invoice->refresh()->getAttributes())->toBe($before);
    Storage::disk('local')->assertExists($invoice->pdf_path);
})->with(['late facts' => false, 'late 404' => true]);

it('sizes the shared lock for the bounded three-request evidence read', function (int $timeout, int $lease): void {
    config(['services.webling.options.timeout' => $timeout]);
    $invoice = refreshInvoiceFixture();
    $webling = refreshWeblingMock(verifiedRefreshDetails());
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->with(10, Mockery::type('Closure'))->andReturnUsing(fn (int $wait, Closure $operation) => $operation());
    $lock->shouldReceive('refresh')->once()->with($lease)->andReturnTrue();
    Cache::partialMock()->shouldReceive('lock')->once()->with('donor-invoice-creation:'.$invoice->id, $lease)->andReturn($lock);

    app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($invoice->webling_payment_verification)->toBe(DonorPaymentVerification::Verified);
})->with(['default HTTP budget' => [10, 120], 'longer HTTP budget' => [60, 210]]);

it('preserves all facts when the shared creation or deletion lock is unavailable', function (): void {
    $invoice = refreshInvoiceFixture();
    $before = $invoice->refresh()->getAttributes();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldNotReceive('settlementDetails');
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->andThrow(LockTimeoutException::class);
    Cache::partialMock()->shouldReceive('lock')->once()->with('donor-invoice-creation:'.$invoice->id, 120)->andReturn($lock);

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(LockTimeoutException::class);

    expect($invoice->refresh()->getAttributes())->toBe($before);
});

it('rejects a response after lease expiry even when no other process has taken the lock', function (): void {
    travelTo('2026-10-09 12:00:00');
    $invoice = refreshInvoiceFixture();
    $before = $invoice->refresh()->getAttributes();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('settlementDetails')->once()->andReturnUsing(function (): array {
        travel(121)->seconds();

        return verifiedRefreshDetails();
    });

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(LockTimeoutException::class);

    expect($invoice->refresh()->getAttributes())->toBe($before);
});

it('makes supported HTTP settlements receipt-eligible using persisted bank receipts and immutable invoice snapshots', function (float $total, float $payment, float $writeoff, float $excess, int $totalCents, int $receivedCents): void {
    travelTo('2026-10-09 12:00:00');
    $snapshot = ['total_cents' => 1500, 'webling' => ['date' => '2026-09-23']];
    $invoice = refreshInvoiceFixture(['source_snapshot' => $snapshot]);
    $path = $invoice->pdf_path;
    $payloads = refreshEvidencePayloads($invoice);
    $payloads['invoice']['properties']['totalamount'] = $total;
    $payloads['invoice']['properties']['paidamount'] = $payment;
    $payloads['invoice']['properties']['writeoffamount'] = $writeoff;
    $payloads['invoice']['links']['revenue'][0]['properties']['amount'] = $total;
    $payloads['invoice']['links']['payment'][0]['properties']['amount'] = $payment;
    $payloads['entries'][0]['properties']['amount'] = $payment;
    if ($writeoff > 0) {
        $payloads['invoice']['links']['writeoff'][] = [
            'id' => 50, 'parents' => [60], 'properties' => ['amount' => $writeoff],
            'links' => ['debit' => [8], 'credit' => [2], 'debitor' => [4321]],
        ];
    }
    if ($excess > 0) {
        $payloads['booking']['children']['entry'][] = 21;
        $payloads['entries'][] = [
            'type' => 'entry', 'id' => 21, 'parents' => [30], 'properties' => ['amount' => $excess],
            'links' => ['debit' => [3], 'credit' => [9]],
        ];
    }
    fakeRefreshEvidence($invoice, payloads: $payloads);

    app(RefreshDonorInvoiceStatusAction::class)($invoice);

    $stored = $invoice->fresh();
    expect($stored->webling_received_cents)->toBe($receivedCents)
        ->and($stored->webling_total_cents)->toBe($totalCents)
        ->and($stored->webling_payment_date->toDateString())->toBe('2026-10-05')
        ->and($stored->webling_payment_verification)->toBe(DonorPaymentVerification::Verified)
        ->and($stored->webling_synced_at->format('Y-m-d H:i:s'))->toBe('2026-10-09 12:00:00')
        ->and($stored->source_snapshot)->toBe($snapshot)
        ->and($stored->source_total_cents)->toBe(1500)
        ->and($stored->pdf_path)->toBe($path);
    expect(app(DonorInvoiceService::class)->isReceiptEligible($stored))->toBeTrue();
    Storage::disk('local')->assertExists($path);
    Http::assertSentCount(3);
})->with([
    'ordinary receipt' => [15.0, 15.0, 0.0, 0.0, 1500, 1500],
    'accountant increased invoice total' => [20.0, 20.0, 0.0, 0.0, 2000, 2000],
    'accountant decreased invoice total' => [10.0, 10.0, 0.0, 0.0, 1000, 1000],
    'underpayment with separate writeoff' => [15.0, 10.0, 5.0, 0.0, 1500, 1000],
    'excess in same bank booking' => [15.0, 15.0, 0.0, 1.10, 1500, 1610],
]);

it('withdraws receipt eligibility after unsupported HTTP evidence while retaining invoice PDF access', function (array $changes, DonorPaymentVerification $verification, int $requests): void {
    travelTo('2026-10-09 12:00:00');
    $invoice = refreshInvoiceFixture([
        'webling_state' => 'paid', 'webling_remaining_cents' => 0, 'webling_received_cents' => 1500,
        'webling_payment_date' => '2026-10-05', 'webling_payment_verification' => DonorPaymentVerification::Verified,
        'webling_synced_at' => '2026-10-08 12:00:00',
    ]);
    $path = $invoice->pdf_path;
    $payloads = refreshEvidencePayloads($invoice);
    foreach ($changes as $key => $value) {
        Arr::set($payloads, $key, $value);
    }
    fakeRefreshEvidence($invoice, payloads: $payloads);

    app(RefreshDonorInvoiceStatusAction::class)($invoice);

    $stored = $invoice->fresh();
    expect($stored->webling_payment_verification)->toBe($verification)
        ->and($stored->webling_received_cents)->toBeNull()
        ->and($stored->webling_payment_date)->toBeNull()
        ->and($stored->webling_synced_at->format('Y-m-d H:i:s'))->toBe('2026-10-09 12:00:00');
    expect(app(DonorInvoiceService::class)->isReceiptEligible($stored))->toBeFalse();
    expect(app(DownloadDonorInvoicePdfAction::class)($stored)['path'])->toBe($path);
    Storage::disk('local')->assertExists($path);
    Http::assertSentCount($requests);
})->with([
    'multiple payments' => [['invoice.links.payment.1' => ['id' => 21]], DonorPaymentVerification::Unclear, 1],
    'missing payment date' => [['booking.properties.date' => null], DonorPaymentVerification::Unclear, 2],
    'wrong bank account' => [['invoice.links.payment.0.links.debit' => [4]], DonorPaymentVerification::Unclear, 1],
    'another invoice in same bank booking' => [[
        'booking.children.entry' => [20, 21],
        'entries.1' => ['type' => 'entry', 'id' => 21, 'parents' => [30], 'properties' => ['amount' => 1.10], 'links' => ['debit' => [3], 'credit' => [9], 'debitor' => [9999]]],
    ], DonorPaymentVerification::Unclear, 3],
    'payment removed and invoice reopened' => [[
        'invoice.properties.state' => 'open', 'invoice.properties.paidamount' => 0,
        'invoice.properties.remainingamount' => 15, 'invoice.links.payment' => [],
    ], DonorPaymentVerification::Unverified, 1],
]);

it('blocks split-excess receipts when the permitted income account is unconfigured', function (): void {
    app(WeblingApiSettings::class)->excess_income_account_id = null;
    $invoice = refreshInvoiceFixture();
    $payloads = refreshEvidencePayloads($invoice);
    $payloads['booking']['children']['entry'][] = 21;
    $payloads['entries'][] = [
        'type' => 'entry', 'id' => 21, 'parents' => [30], 'properties' => ['amount' => 1.10],
        'links' => ['debit' => [3], 'credit' => [9]],
    ];
    fakeRefreshEvidence($invoice, payloads: $payloads);

    app(RefreshDonorInvoiceStatusAction::class)($invoice);

    expect($invoice->fresh()->webling_payment_verification)->toBe(DonorPaymentVerification::Unclear);
    expect(app(DonorInvoiceService::class)->isReceiptEligible($invoice->fresh()))->toBeFalse();
    Http::assertSentCount(3);
});

it('recomputes HTTP payment corrections and dates without accumulating prior receipts', function (): void {
    $invoice = refreshInvoiceFixture();
    $first = refreshEvidencePayloads($invoice);
    $corrected = $first;
    $corrected['invoice']['properties']['totalamount'] = 20;
    $corrected['invoice']['properties']['paidamount'] = 20;
    $corrected['invoice']['links']['revenue'][0]['properties']['amount'] = 20;
    $corrected['invoice']['links']['payment'][0]['properties']['amount'] = 20;
    $corrected['entries'][0]['properties']['amount'] = 20;
    $corrected['booking']['properties']['date'] = '2026-10-06';
    Http::preventStrayRequests();
    Http::fake([
        'https://demo.webling.ch/api/1/debitor/4321' => Http::sequence()->push($first['invoice'])->push($first['invoice'])->push($corrected['invoice']),
        'https://demo.webling.ch/api/1/entrygroup/30' => Http::sequence()->push($first['booking'])->push($first['booking'])->push($corrected['booking']),
        'https://demo.webling.ch/api/1/entry/20' => Http::sequence()->push($first['entries'][0])->push($first['entries'][0])->push($corrected['entries'][0]),
    ]);
    $refresh = app(RefreshDonorInvoiceStatusAction::class);

    $refresh($invoice);
    $refresh($invoice);
    expect($invoice->fresh()->webling_received_cents)->toBe(1500);
    $refresh($invoice);

    $stored = $invoice->fresh();
    expect($stored->webling_received_cents)->toBe(2000)
        ->and($stored->webling_payment_date->toDateString())->toBe('2026-10-06');
    expect(app(DonorInvoiceService::class)->isReceiptEligible($stored))->toBeTrue();
    Http::assertSentCount(9);
});

it('isolates failed and unclear HTTP settlements during event-scoped bulk refresh', function (): void {
    $event = DonationEvent::factory()->create();
    $failed = refreshInvoiceFixture([
        'donation_event_id' => $event->id, 'webling_debitor_id' => 9999,
        'webling_state' => 'paid', 'webling_total_cents' => 1500, 'webling_remaining_cents' => 0,
        'webling_received_cents' => 1500, 'webling_payment_date' => '2026-10-05',
        'webling_payment_verification' => DonorPaymentVerification::Verified, 'webling_synced_at' => '2026-10-08 12:00:00',
    ]);
    $valid = refreshInvoiceFixture(['donation_event_id' => $event->id]);
    $unclear = refreshInvoiceFixture(['donation_event_id' => $event->id, 'webling_debitor_id' => 5555]);
    $other = refreshInvoiceFixture(['webling_debitor_id' => 7777]);
    $beforeFailure = $failed->refresh()->getAttributes();
    $beforeOther = $other->refresh()->getAttributes();
    $unclearPayloads = refreshEvidencePayloads($unclear);
    $unclearPayloads['invoice']['links']['payment'] = [];
    fakeRefreshEvidence($valid, [
        'https://demo.webling.ch/api/1/debitor/9999' => Http::response([], 503),
        'https://demo.webling.ch/api/1/debitor/5555' => Http::response($unclearPayloads['invoice']),
    ]);
    $refresh = app(RefreshDonorInvoiceStatusAction::class);

    $result = app(RunDonorInvoiceBulkAction::class)($event, [$failed, $valid, $unclear, $other], fn (DonorEventInvoice $invoice) => $refresh($invoice));

    expect($result['successful'])->toBe(2)
        ->and($result['failed'])->toBe(1)
        ->and($result['skipped'])->toBe(1);
    expect($failed->refresh()->getAttributes())->toBe($beforeFailure);
    expect($other->refresh()->getAttributes())->toBe($beforeOther);
    expect(app(DonorInvoiceService::class)->isReceiptEligible($valid->fresh()))->toBeTrue();
    expect($unclear->fresh()->webling_payment_verification)->toBe(DonorPaymentVerification::Unclear);
    expect(app(DonorInvoiceService::class)->isReceiptEligible($unclear->fresh()))->toBeFalse();
    Http::assertSentCount(5);
});

it('revokes receipts and cached invoice downloads after a confirmed invoice HTTP 404', function (): void {
    $invoice = refreshInvoiceFixture([
        'webling_state' => 'paid', 'webling_remaining_cents' => 0,
        'webling_received_cents' => 1500, 'webling_payment_date' => '2026-10-05',
        'webling_payment_verification' => DonorPaymentVerification::Verified,
        'webling_synced_at' => '2026-10-08 12:00:00',
    ]);
    $path = $invoice->pdf_path;
    fakeRefreshEvidence($invoice, ['https://demo.webling.ch/api/1/debitor/4321' => Http::response([], 404)]);

    app(RefreshDonorInvoiceStatusAction::class)($invoice);

    $stored = $invoice->fresh();
    expect($stored->remote_deleted_at)->not->toBeNull()
        ->and($stored->webling_received_cents)->toBeNull()
        ->and($stored->webling_payment_date)->toBeNull()
        ->and($stored->webling_synced_at)->toBeNull();
    expect(app(DonorInvoiceService::class)->isReceiptEligible($stored))->toBeFalse();
    expect(app(DownloadDonorInvoicePdfAction::class)($stored))->toBeNull();
    Storage::disk('local')->assertMissing($path);
    Http::assertSentCount(1);
});
