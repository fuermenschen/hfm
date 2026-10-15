<?php

use App\Enums\DonorPaymentVerification;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\assertDatabaseHas;

it('defines event-scoped invoice schema and indexes', function (): void {
    $columns = collect(Schema::getColumns('donor_event_invoices'))->keyBy('name');
    $indexes = collect(Schema::getIndexes('donor_event_invoices'));

    expect(Schema::hasColumns('donor_event_invoices', [
        'id',
        'external_user_id',
        'donation_event_id',
        'webling_debitor_id',
        'webling_invoice_number',
        'webling_state',
        'webling_due_date',
        'webling_total_cents',
        'webling_remaining_cents',
        'webling_received_cents',
        'webling_payment_date',
        'webling_payment_verification',
        'webling_synced_at',
        'pdf_disk',
        'pdf_path',
        'invoice_sent_at',
        'invoice_reminder_sent_at',
        'source_snapshot',
        'source_total_cents',
        'remote_deleted_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue()
        ->and($columns['external_user_id']['nullable'])->toBeFalse()
        ->and($columns['donation_event_id']['nullable'])->toBeFalse()
        ->and($columns['source_snapshot']['nullable'])->toBeTrue()
        ->and($indexes->contains(fn (array $index): bool => $index['unique']
            && $index['columns'] === ['external_user_id', 'donation_event_id']))->toBeTrue()
        ->and($indexes->contains(fn (array $index): bool => $index['columns'] === ['donation_event_id']))->toBeTrue()
        ->and($indexes->contains(fn (array $index): bool => $index['columns'] === ['webling_debitor_id']))->toBeTrue();
});

it('persists invoice data with relationships and casts', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $donationEvent = DonationEvent::factory()->create();
    $snapshot = [
        'lines' => [
            ['amount_cents' => 1250, 'title' => 'Donation'],
        ],
        'total_cents' => 1250,
    ];

    $invoice = DonorEventInvoice::factory()
        ->forExternalUser($externalUser)
        ->forEvent($donationEvent)
        ->create([
            'webling_debitor_id' => 123,
            'webling_invoice_number' => 'INV-123',
            'webling_state' => 'open',
            'webling_due_date' => '2026-10-01',
            'webling_total_cents' => 1250,
            'webling_remaining_cents' => 1250,
            'webling_synced_at' => '2026-09-01 12:00:00',
            'pdf_disk' => 'local',
            'pdf_path' => 'invoices/123.pdf',
            'source_snapshot' => $snapshot,
            'source_total_cents' => 1250,
            'invoice_sent_at' => '2026-09-01 12:01:00',
            'invoice_reminder_sent_at' => null,
            'remote_deleted_at' => null,
        ]);

    $invoice->refresh();

    expect($invoice->externalUser->is($externalUser))->toBeTrue()
        ->and($invoice->donationEvent->is($donationEvent))->toBeTrue()
        ->and($externalUser->donorEventInvoices->sole()->is($invoice))->toBeTrue()
        ->and($donationEvent->donorEventInvoices->sole()->is($invoice))->toBeTrue()
        ->and($invoice->webling_debitor_id)->toBe(123)
        ->and($invoice->webling_due_date->toDateString())->toBe('2026-10-01')
        ->and($invoice->webling_synced_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($invoice->source_snapshot)->toBe($snapshot)
        ->and($invoice->source_total_cents)->toBe(1250)
        ->and($invoice->invoice_sent_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('allows an empty not-created invoice row', function (): void {
    $invoice = DonorEventInvoice::factory()->create();

    expect($invoice->webling_debitor_id)->toBeNull()
        ->and($invoice->webling_received_cents)->toBeNull()
        ->and($invoice->webling_payment_date)->toBeNull()
        ->and($invoice->webling_payment_verification)->toBe(DonorPaymentVerification::Unverified)
        ->and($invoice->source_snapshot)->toBeNull()
        ->and($invoice->pdf_path)->toBeNull()
        ->and($invoice->invoice_sent_at)->toBeNull();
});

it('persists received cents, payment date, and typed payment verification independently of invoice totals', function (): void {
    $invoice = DonorEventInvoice::factory()->create([
        'webling_total_cents' => 10000,
        'webling_received_cents' => '11010',
        'webling_payment_date' => '2026-10-05',
        'webling_payment_verification' => DonorPaymentVerification::Verified,
    ]);

    $invoice->refresh();

    expect($invoice->webling_received_cents)->toBe(11010)
        ->and($invoice->webling_payment_date->toDateString())->toBe('2026-10-05')
        ->and($invoice->webling_payment_verification)->toBe(DonorPaymentVerification::Verified);
    assertDatabaseHas('donor_event_invoices', [
        'id' => $invoice->id,
        'webling_total_cents' => 10000,
        'webling_received_cents' => 11010,
        'webling_payment_verification' => 'verified',
    ]);
});

it('loads unclear payment verification without claiming a received amount or date', function (): void {
    $invoice = DonorEventInvoice::factory()->create([
        'webling_state' => 'paid',
        'webling_payment_verification' => 'unclear',
    ]);

    $invoice->refresh();

    expect($invoice->webling_payment_verification)->toBe(DonorPaymentVerification::Unclear)
        ->and($invoice->webling_received_cents)->toBeNull()
        ->and($invoice->webling_payment_date)->toBeNull();
});

it('defaults legacy-style paid invoice inserts to unverified without changing existing facts', function (): void {
    $id = DB::table('donor_event_invoices')->insertGetId([
        'external_user_id' => ExternalUser::factory()->create()->id,
        'donation_event_id' => DonationEvent::factory()->create()->id,
        'webling_debitor_id' => 322,
        'webling_state' => 'paid',
        'webling_total_cents' => 7000,
        'webling_remaining_cents' => 0,
        'webling_synced_at' => '2026-10-08 10:04:40',
    ]);

    $invoice = DonorEventInvoice::query()->findOrFail($id);

    expect($invoice->webling_payment_verification)->toBe(DonorPaymentVerification::Unverified)
        ->and($invoice->webling_received_cents)->toBeNull()
        ->and($invoice->webling_payment_date)->toBeNull()
        ->and($invoice->webling_state)->toBe('paid')
        ->and($invoice->webling_total_cents)->toBe(7000)
        ->and($invoice->webling_synced_at->format('Y-m-d H:i:s'))->toBe('2026-10-08 10:04:40');
});

it('adds payment summaries to populated invoice tables and rolls back without losing invoice facts', function (): void {
    $originalConnection = DB::getDefaultConnection();
    config(['database.connections.payment_summary_upgrade' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
    DB::setDefaultConnection('payment_summary_upgrade');

    try {
        Schema::create('donor_event_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('webling_state');
            $table->unsignedBigInteger('webling_total_cents');
        });
        $id = DB::table('donor_event_invoices')->insertGetId(['webling_state' => 'paid', 'webling_total_cents' => 7000]);
        $migration = require base_path('database/migrations/2026_10_13_153607_add_payment_summary_to_donor_event_invoices_table.php');

        $migration->up();

        $row = DB::table('donor_event_invoices')->where('id', $id)->first();
        expect($row->webling_state)->toBe('paid')
            ->and($row->webling_total_cents)->toBe(7000)
            ->and($row->webling_received_cents)->toBeNull()
            ->and($row->webling_payment_date)->toBeNull()
            ->and($row->webling_payment_verification)->toBe('unverified');

        $migration->down();

        expect(Schema::hasColumn('donor_event_invoices', 'webling_payment_verification'))->toBeFalse()
            ->and(DB::table('donor_event_invoices')->where('id', $id)->value('webling_total_cents'))->toBe(7000);
    } finally {
        DB::setDefaultConnection($originalConnection);
        DB::purge('payment_summary_upgrade');
    }
});

it('enforces one invoice per external user and event', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $event = DonationEvent::factory()->create();

    DonorEventInvoice::factory()->forExternalUser($externalUser)->forEvent($event)->create();

    expect(fn () => DonorEventInvoice::factory()
        ->forExternalUser($externalUser)
        ->forEvent($event)
        ->create())->toThrow(QueryException::class);
});

it('allows the same external user in multiple events and multiple users in one event', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $otherExternalUser = ExternalUser::factory()->create();
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();

    $firstInvoice = DonorEventInvoice::factory()->forExternalUser($externalUser)->forEvent($event)->create();
    $secondInvoice = DonorEventInvoice::factory()->forExternalUser($externalUser)->forEvent($otherEvent)->create();
    $thirdInvoice = DonorEventInvoice::factory()->forExternalUser($otherExternalUser)->forEvent($event)->create();

    expect($firstInvoice->exists)->toBeTrue()
        ->and($secondInvoice->exists)->toBeTrue()
        ->and($thirdInvoice->exists)->toBeTrue();
});

it('restricts deleting referenced events and external users', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $event = DonationEvent::factory()->create();
    DonorEventInvoice::factory()->forExternalUser($externalUser)->forEvent($event)->create();

    expect(fn () => $event->delete())->toThrow(QueryException::class);

    $externalUser->delete();

    expect($externalUser->trashed())->toBeTrue()
        ->and(DonorEventInvoice::query()->whereKey($externalUser->donorEventInvoices->sole()->id)->exists())->toBeTrue()
        ->and(fn () => $externalUser->forceDelete())->toThrow(QueryException::class);
});
