<?php

use App\Actions\CollectDonorInvoiceDataAction;
use App\Jobs\CreateDonorInvoice;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Services\Webling\Invoice\Dto\InvoiceCreateData;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Services\Webling\Letter\LetterService;
use App\Settings\InvoiceSettings;
use App\Settings\WeblingApiSettings;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch',
        'api_key' => 'fake-key',
        'accounting_period_id' => 10,
        'debit_account_id' => 20,
        'credit_account_id' => 30,
    ]);
    InvoiceSettings::fake([
        'qr_iban' => 'CH5604835012345678009',
        'qr_show_amount' => true,
        'creditor_name' => 'HFM',
        'creditor_care_of' => '',
        'creditor_street' => 'Street',
        'creditor_building_number' => '1',
        'creditor_postal_code' => '8400',
        'creditor_city' => 'Winterthur',
        'due_days' => 14,
    ]);
    Storage::fake('local');
});

it('creates a marked debitor and caches its PDF from one persisted snapshot', function (): void {
    $invoice = donorInvoiceWithDonation();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $letter = Mockery::mock(LetterService::class);
    $debitorResponse = successfulResponse(4321);
    $pdfResponse = successfulResponse('%PDF-test');

    $webling->shouldReceive('commentMarker')->once()->with($invoice->id)->andReturn('HFM-DONOR-INVOICE:'.$invoice->id);
    $webling->shouldReceive('findInvoiceIdsByCommentMarker')->once()->andReturnUsing(function () use ($invoice): array {
        expect($invoice->fresh()->source_snapshot)->not->toBeNull();

        return [];
    });
    $webling->shouldReceive('createInvoiceWithMarker')->once()->withArgs(function (int $invoiceId, InvoiceCreateData $data) use ($invoice): bool {
        expect($invoiceId)->toBe($invoice->id)
            ->and($data->invoiceLines)->toHaveCount(1)
            ->and($data->invoiceLines[0]['amount_cents'])->toBe(1000)
            ->and($data->addressLines)->toContain('DE-12345 '.$invoice->externalUser->city);

        return true;
    })->andReturn($debitorResponse);
    $letter->shouldReceive('createFromSnapshot')->once()->withArgs(function (array $snapshot, string $title, int $debitorId): bool {
        expect($snapshot['total_cents'])->toBe(1000)
            ->and($snapshot['letter']['qr_invoice']['iban'])->toBe('CH5604835012345678009')
            ->and($title)->toBe('Spendenrechnung Höhenmeter für Menschen')
            ->and($debitorId)->toBe(4321);

        return true;
    })->andReturn($pdfResponse);

    runInvoiceJob($invoice, $webling, $letter);

    $invoice->refresh();
    expect($invoice->webling_debitor_id)->toBe(4321)
        ->and($invoice->source_total_cents)->toBe(1000)
        ->and($invoice->pdf_disk)->toBe('local')
        ->and($invoice->pdf_path)->toStartWith('webling/donor-invoices/'.$invoice->id.'/');
    Storage::disk('local')->assertExists($invoice->pdf_path);
});

it('recovers a matching debitor without another create request', function (): void {
    $invoice = donorInvoiceWithDonation();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $letter = Mockery::mock(LetterService::class);

    $webling->shouldReceive('commentMarker')->once()->andReturn('HFM-DONOR-INVOICE:'.$invoice->id);
    $webling->shouldReceive('findInvoiceIdsByCommentMarker')->once()->andReturn([999]);
    $webling->shouldNotReceive('createInvoiceWithMarker');
    $letter->shouldReceive('createFromSnapshot')->once()->andReturn(successfulResponse('%PDF-recovered'));

    runInvoiceJob($invoice, $webling, $letter);

    expect($invoice->refresh()->webling_debitor_id)->toBe(999);
});

it('keeps the frozen snapshot and debitor when PDF creation fails', function (): void {
    $invoice = donorInvoiceWithDonation();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $failedLetter = Mockery::mock(LetterService::class);

    $webling->shouldReceive('commentMarker')->once()->andReturn('HFM-DONOR-INVOICE:'.$invoice->id);
    $webling->shouldReceive('findInvoiceIdsByCommentMarker')->once()->andReturn([]);
    $webling->shouldReceive('createInvoiceWithMarker')->once()->andReturn(successfulResponse(123));
    $failedLetter->shouldReceive('createFromSnapshot')->once()->andReturn(failedResponse());

    expect(fn () => runInvoiceJob($invoice, $webling, $failedLetter))->toThrow(RuntimeException::class);
    $snapshot = $invoice->refresh()->source_snapshot;

    Donation::query()->where('donor_external_user_id', $invoice->external_user_id)->update(['amount_per_round' => 99]);
    $letter = Mockery::mock(LetterService::class);
    $letter->shouldReceive('createFromSnapshot')->once()->withArgs(function (array $nextSnapshot) use ($snapshot): bool {
        expect($nextSnapshot)->toBe($snapshot);

        return true;
    })->andReturn(successfulResponse('%PDF-retry'));

    runInvoiceJob($invoice, Mockery::mock(WeblingInvoiceService::class), $letter);

    expect($invoice->refresh()->source_snapshot)->toBe($snapshot)
        ->and($invoice->pdf_path)->not->toBeNull();
});

it('fails closed when more than one debitor matches its marker', function (): void {
    $invoice = donorInvoiceWithDonation();
    $webling = Mockery::mock(WeblingInvoiceService::class);

    $webling->shouldReceive('commentMarker')->once()->andReturn('HFM-DONOR-INVOICE:'.$invoice->id);
    $webling->shouldReceive('findInvoiceIdsByCommentMarker')->once()->andReturn([1, 2]);

    expect(fn () => runInvoiceJob($invoice, $webling, Mockery::mock(LetterService::class)))
        ->toThrow(RuntimeException::class, 'Multiple Webling Debitors');
    expect($invoice->refresh()->webling_debitor_id)->toBeNull()
        ->and($invoice->source_snapshot)->not->toBeNull();
});

it('does not create a debitor without billable lines', function (): void {
    $invoice = donorInvoiceWithDonation();
    Donation::query()->where('donor_external_user_id', $invoice->external_user_id)->update([
        'amount_per_round' => 0,
    ]);
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldNotReceive('commentMarker');

    expect(fn () => runInvoiceJob($invoice, $webling, Mockery::mock(LetterService::class)))
        ->toThrow(RuntimeException::class, 'No billable invoice lines');
    expect($invoice->refresh()->source_snapshot)->toBeNull();
});

it('does no remote work and keeps the cached pdf when it runs again', function (): void {
    $invoice = donorInvoiceWithDonation();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $letter = Mockery::mock(LetterService::class);
    $webling->shouldReceive('commentMarker')->once()->andReturn('HFM-DONOR-INVOICE:'.$invoice->id);
    $webling->shouldReceive('findInvoiceIdsByCommentMarker')->once()->andReturn([]);
    $webling->shouldReceive('createInvoiceWithMarker')->once()->andReturn(successfulResponse(4321));
    $letter->shouldReceive('createFromSnapshot')->once()->andReturn(successfulResponse('%PDF-first'));

    runInvoiceJob($invoice, $webling, $letter);

    $invoice->refresh();
    $path = $invoice->pdf_path;
    $pdf = Storage::disk('local')->get($path);

    $idleWebling = Mockery::mock(WeblingInvoiceService::class);
    $idleWebling->shouldNotReceive('commentMarker');
    $idleWebling->shouldNotReceive('findInvoiceIdsByCommentMarker');
    $idleWebling->shouldNotReceive('createInvoiceWithMarker');
    $idleLetter = Mockery::mock(LetterService::class);
    $idleLetter->shouldNotReceive('createFromSnapshot');

    runInvoiceJob($invoice, $idleWebling, $idleLetter);

    expect($invoice->refresh()->pdf_path)->toBe($path)
        ->and(Storage::disk('local')->get($path))->toBe($pdf);
});

it('recovers through the marker when debitor creation ends ambiguously', function (): void {
    $invoice = donorInvoiceWithDonation();
    $webling = Mockery::mock(WeblingInvoiceService::class);

    $webling->shouldReceive('commentMarker')->twice()->andReturn('HFM-DONOR-INVOICE:'.$invoice->id);
    $webling->shouldReceive('findInvoiceIdsByCommentMarker')->twice()->andReturn([], [777]);
    $webling->shouldReceive('createInvoiceWithMarker')->once()->andReturn(failedResponse());

    $failedLetter = Mockery::mock(LetterService::class);
    $failedLetter->shouldNotReceive('createFromSnapshot');

    expect(fn () => runInvoiceJob($invoice, $webling, $failedLetter))
        ->toThrow(RuntimeException::class, 'Webling Debitor creation failed');
    expect($invoice->refresh()->webling_debitor_id)->toBeNull();

    $letter = Mockery::mock(LetterService::class);
    $letter->shouldReceive('createFromSnapshot')->once()->andReturn(successfulResponse('%PDF-recovered'));

    runInvoiceJob($invoice, $webling, $letter);

    expect($invoice->refresh()->webling_debitor_id)->toBe(777);
});

it('creates the invoice behind a lock keyed by the local invoice id', function (): void {
    $invoice = donorInvoiceWithDonation();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $letter = Mockery::mock(LetterService::class);
    $webling->shouldReceive('commentMarker')->andReturn('HFM-DONOR-INVOICE:'.$invoice->id);
    $webling->shouldReceive('findInvoiceIdsByCommentMarker')->andReturn([]);
    $webling->shouldReceive('createInvoiceWithMarker')->andReturn(successfulResponse(4321));
    $letter->shouldReceive('createFromSnapshot')->andReturn(successfulResponse('%PDF-locked'));

    $lock = Mockery::mock();
    $lock->shouldReceive('block')->once()->with(10, Mockery::type('Closure'))->andReturnUsing(fn (int $seconds, Closure $callback) => $callback());
    $lockCalls = [];
    Cache::swap(lockManager($lock, function (array $parameters) use (&$lockCalls): void {
        $lockCalls[] = $parameters;
    }));

    runInvoiceJob($invoice, $webling, $letter);

    expect($lockCalls)->toBe([['donor-invoice-creation:'.$invoice->id, 120]])
        ->and($invoice->refresh()->webling_debitor_id)->toBe(4321);
});

it('fails without side effects when the creation lock is unavailable', function (): void {
    $invoice = donorInvoiceWithDonation();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldNotReceive('commentMarker');
    $webling->shouldNotReceive('findInvoiceIdsByCommentMarker');
    $webling->shouldNotReceive('createInvoiceWithMarker');

    $lock = Mockery::mock();
    $lock->shouldReceive('block')->once()->andThrow(LockTimeoutException::class);
    Cache::swap(lockManager($lock));

    expect(fn () => runInvoiceJob($invoice, $webling, Mockery::mock(LetterService::class)))
        ->toThrow(LockTimeoutException::class);
    expect($invoice->refresh()->source_snapshot)->toBeNull()
        ->and($invoice->webling_debitor_id)->toBeNull()
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

function donorInvoiceWithDonation(): DonorEventInvoice
{
    $donor = ExternalUser::factory()->create([
        'first_name' => 'Anna',
        'last_name' => 'Muster',
        'country_of_residence' => 'DE',
        'zip_code' => '12345',
    ]);
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create(['name' => 'Partner']);
    $registration = AthleteRegistration::factory()->forEvent($event)->withPartner($partner)->create(['rounds_done' => 2]);
    Donation::factory()->forPair($donor, $registration)->create([
        'amount_per_round' => 5,
        'amount_min' => null,
        'amount_max' => null,
        'verified' => false,
    ]);

    return DonorEventInvoice::factory()->forExternalUser($donor)->forEvent($event)->create();
}

function successfulResponse(string|int $body): Response
{
    $response = Mockery::mock(Response::class);
    $response->shouldReceive('successful')->andReturn(true);
    $response->shouldReceive('json')->andReturn($body);
    $response->shouldReceive('body')->andReturn((string) $body);

    return $response;
}

function failedResponse(): Response
{
    $response = Mockery::mock(Response::class);
    $response->shouldReceive('successful')->andReturn(false);

    return $response;
}

function runInvoiceJob(DonorEventInvoice $invoice, WeblingInvoiceService $webling, LetterService $letter): void
{
    (new CreateDonorInvoice($invoice))->handle(
        app(CollectDonorInvoiceDataAction::class),
        $webling,
        $letter,
        app(InvoiceSettings::class),
        app(WeblingApiSettings::class),
    );
}

function lockManager(mixed $lock, ?Closure $onLock = null): CacheManager
{
    return new class(app(), $lock, $onLock) extends CacheManager
    {
        public function __construct($app, private readonly mixed $lock, private readonly ?Closure $onLock)
        {
            parent::__construct($app);
        }

        public function __call($method, $parameters)
        {
            if ($method === 'lock') {
                if ($this->onLock !== null) {
                    ($this->onLock)($parameters);
                }

                return $this->lock;
            }

            return parent::__call($method, $parameters);
        }
    };
}
