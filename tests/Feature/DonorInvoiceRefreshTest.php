<?php

use App\Exceptions\Webling\WeblingApiException;
use App\Jobs\DispatchDonorInvoiceRefresh;
use App\Jobs\RefreshDonorInvoiceStatus;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Settings\WeblingApiSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\freezeTime;
use function Pest\Laravel\travel;
use function Pest\Laravel\travelTo;

it('selects invoices by successful refresh age and raw state', function (?string $state, ?int $hours, bool $expected): void {
    travelTo(now('UTC')->startOfDay()->setTime(2, 0));
    $invoice = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 42, 'webling_state' => $state,
        'webling_synced_at' => $hours === null ? null : now()->subHours($hours),
    ]);
    Queue::fake([RefreshDonorInvoiceStatus::class]);

    (new DispatchDonorInvoiceRefresh)->handle();

    if ($expected) {
        Queue::assertPushed(RefreshDonorInvoiceStatus::class, fn ($job): bool => $job->invoiceId === $invoice->id);
    } else {
        Queue::assertNotPushed(RefreshDonorInvoiceStatus::class);
    }
})->with([
    [null, null, true], ['paid', null, true], ['open', 1, false], ['open', 23, true], ['open', 24, true],
    ['partially paid', 24, true], ['unrecognized', 24, true], [null, 24, true],
    ['paid', 144, false], ['paid', 167, true], ['paid', 168, true], ['writeoff', 144, false], ['writeoff', 167, true], ['writeoff', 168, true],
]);

it('selects invoices whose last refresh completed seconds after the scheduled time', function (string $state, int $days): void {
    travelTo(now('UTC')->startOfDay()->setTime(2, 0));
    $invoice = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 42,
        'webling_state' => $state,
        'webling_synced_at' => now('UTC')->subDays($days)->addSeconds(5),
    ]);
    Queue::fake([RefreshDonorInvoiceStatus::class]);

    (new DispatchDonorInvoiceRefresh)->handle();

    Queue::assertPushed(RefreshDonorInvoiceStatus::class, fn ($job): bool => $job->invoiceId === $invoice->id);
})->with([['open', 1], ['paid', 7], ['writeoff', 7]]);

it('uses UTC calendar cutoffs for daily and weekly refreshes across timezone boundaries', function (string $state, string $scheduledAt, string $lastSyncedAt): void {
    config(['app.timezone' => 'Europe/Zurich']);
    travelTo(Carbon::parse($scheduledAt));
    $invoice = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 42,
        'webling_state' => $state,
        'webling_synced_at' => $lastSyncedAt,
    ]);
    Queue::fake([RefreshDonorInvoiceStatus::class]);

    (new DispatchDonorInvoiceRefresh)->handle();

    Queue::assertPushed(RefreshDonorInvoiceStatus::class, fn (RefreshDonorInvoiceStatus $job): bool => $job->invoiceId === $invoice->id);
})->with([
    'daily across UTC midnight' => ['open', '2026-10-15 04:00:00 Europe/Zurich', '2026-10-15 01:00:00'],
    'weekly across UTC midnight' => ['paid', '2026-10-15 04:00:00 Europe/Zurich', '2026-10-09 01:00:00'],
    'daily after DST shift' => ['open', '2026-10-26 03:00:00 Europe/Zurich', '2026-10-26 00:00:00'],
    'weekly after DST shift' => ['writeoff', '2026-10-26 03:00:00 Europe/Zurich', '2026-10-20 00:00:00'],
]);

it('forces event refresh but excludes untracked deleted and other event invoices', function (): void {
    $event = DonationEvent::factory()->create();
    $invoice = DonorEventInvoice::factory()->forEvent($event)->create(['webling_debitor_id' => 42, 'webling_state' => 'paid', 'webling_synced_at' => now()]);
    DonorEventInvoice::factory()->forEvent($event)->create();
    DonorEventInvoice::factory()->forEvent($event)->create(['webling_debitor_id' => 43, 'remote_deleted_at' => now()]);
    DonorEventInvoice::factory()->create(['webling_debitor_id' => 44]);
    $deleted = DonorEventInvoice::factory()->forEvent($event)->create(['webling_debitor_id' => 45]);
    $deleted->externalUser->delete();
    Queue::fake([RefreshDonorInvoiceStatus::class]);

    (new DispatchDonorInvoiceRefresh($event->id))->handle();

    Queue::assertPushed(RefreshDonorInvoiceStatus::class, 1);
    Queue::assertPushed(RefreshDonorInvoiceStatus::class, fn ($job): bool => $job->invoiceId === $invoice->id);
});

it('pages dispatch and deduplicates overlapping requests', function (): void {
    DonorEventInvoice::factory()->forEvent(DonationEvent::factory()->create())->count(101)->create(['webling_debitor_id' => 42]);
    $queue = Queue::fake([RefreshDonorInvoiceStatus::class, DispatchDonorInvoiceRefresh::class]);
    (new DispatchDonorInvoiceRefresh)->handle();
    (new DispatchDonorInvoiceRefresh)->handle();
    Queue::assertPushed(RefreshDonorInvoiceStatus::class, 100);
    Queue::assertPushed(DispatchDonorInvoiceRefresh::class, 1);
    $continuation = $queue->pushed(DispatchDonorInvoiceRefresh::class)->first();
    $continuation->handle();
    Queue::assertPushed(RefreshDonorInvoiceStatus::class, 101);
});

it('keeps prior facts on failure and refreshes another invoice independently', function (): void {
    freezeTime();
    WeblingApiSettings::fake([
        'api_url' => 'https://demo.webling.ch', 'api_key' => 'fake-key',
        'accounting_period_id' => 10, 'bank_account_id' => 3, 'excess_income_account_id' => null,
    ]);
    $failed = DonorEventInvoice::factory()->create(['webling_debitor_id' => 42, 'webling_state' => 'open', 'webling_synced_at' => now()->subDays(2)]);
    $good = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 43, 'webling_state' => 'unknown', 'webling_synced_at' => now()->subDays(2),
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://demo.webling.ch/api/1/debitor/42' => Http::response([], 503),
        'https://demo.webling.ch/api/1/debitor/43' => Http::response([
            'type' => 'debitor', 'parents' => [10],
            'properties' => ['state' => 'open', 'totalamount' => 10, 'remainingamount' => 10],
        ]),
    ]);
    $before = $failed->refresh()->getAttributes();

    expect(fn () => app()->call([new RefreshDonorInvoiceStatus($failed->id), 'handle']))->toThrow(WeblingApiException::class);
    app()->call([new RefreshDonorInvoiceStatus($good->id), 'handle']);

    expect($failed->fresh()->getAttributes())->toBe($before);
    $good->refresh();
    expect($good->webling_state)->toBe('open')
        ->and($good->webling_total_cents)->toBe(1000)
        ->and($good->webling_remaining_cents)->toBe(1000)
        ->and($good->webling_synced_at->toDateTimeString())->toBe(now()->toDateTimeString());
    Http::assertSentCount(2);
});

it('skips invoices deleted while waiting in the queue', function (): void {
    WeblingApiSettings::fake(['api_url' => 'https://demo.webling.ch', 'api_key' => 'fake-key']);
    $invoice = DonorEventInvoice::factory()->create(['remote_deleted_at' => now()]);
    Http::preventStrayRequests();
    app()->call([new RefreshDonorInvoiceStatus($invoice->id), 'handle']);
    Http::assertNothingSent();
});

it('queues daily refresh from the scheduler only once per minute', function (): void {
    travelTo(now('UTC')->setTime(2, 0));
    Queue::fake([DispatchDonorInvoiceRefresh::class]);
    artisan('schedule:run')->assertSuccessful();
    artisan('schedule:run')->assertSuccessful();
    Queue::assertPushedOn('maintenance', DispatchDonorInvoiceRefresh::class);
    Queue::assertPushed(DispatchDonorInvoiceRefresh::class, 1);
});

it('backs off transient failures and records terminal failures without changing freshness', function (int $status, bool $retryable): void {
    freezeTime();
    config(['queue.default' => 'database']);
    WeblingApiSettings::fake(['api_url' => 'https://demo.webling.ch', 'api_key' => 'fake-key']);
    $invoice = DonorEventInvoice::factory()->create(['webling_debitor_id' => 42, 'webling_synced_at' => now()->subDays(2)]);
    $before = $invoice->refresh()->getAttributes();
    Http::preventStrayRequests();
    Http::fake(['https://demo.webling.ch/api/1/debitor/42' => Http::response([], $status)]);
    dispatch(new RefreshDonorInvoiceStatus($invoice->id));

    artisan('queue:work', ['--once' => true, '--queue' => 'maintenance'])->assertSuccessful();

    if ($retryable) {
        $pending = DB::table('jobs')->sole();
        expect($pending->attempts)->toBe(1);
        expect($pending->available_at)->toBe(now()->addMinute()->timestamp);
        travel(60)->seconds();
        artisan('queue:work', ['--once' => true, '--queue' => 'maintenance'])->assertSuccessful();
        expect(DB::table('jobs')->sole()->available_at)->toBe(now()->addMinutes(5)->timestamp);
        travel(300)->seconds();
        artisan('queue:work', ['--once' => true, '--queue' => 'maintenance'])->assertSuccessful();
    }

    assertDatabaseCount('jobs', 0);
    assertDatabaseCount('failed_jobs', 1);
    expect($invoice->fresh()->getAttributes())->toBe($before);
})->with([[503, true], [502, true], [429, true], [401, false]]);
