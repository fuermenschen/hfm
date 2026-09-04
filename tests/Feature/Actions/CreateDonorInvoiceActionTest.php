<?php

use App\Actions\CreateDonorInvoiceAction;
use App\Exceptions\DonorInvoiceGuardException;
use App\Jobs\CreateDonorInvoice;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use Illuminate\Support\Facades\Queue;

it('creates one event-scoped invoice row and dispatches its creation job', function (): void {
    Queue::fake();
    $externalUser = ExternalUser::factory()->create();
    $event = DonationEvent::factory()->create();

    $invoice = app(CreateDonorInvoiceAction::class)($externalUser, $event);
    $sameInvoice = app(CreateDonorInvoiceAction::class)($externalUser, $event);

    expect($invoice->is($sameInvoice))->toBeTrue();
    Queue::assertPushed(CreateDonorInvoice::class, 2);
    Queue::assertPushed(CreateDonorInvoice::class, fn (CreateDonorInvoice $job): bool => $job->invoice->is($invoice));
});

it('blocks recreation of an invoice with unknown webling state', function (): void {
    Queue::fake();
    $externalUser = ExternalUser::factory()->create();
    $event = DonationEvent::factory()->create();
    DonorEventInvoice::factory()->forExternalUser($externalUser)->forEvent($event)->create([
        'webling_debitor_id' => 42,
        'webling_state' => 'shredded',
    ]);

    expect(fn () => app(CreateDonorInvoiceAction::class)($externalUser, $event))
        ->toThrow(DonorInvoiceGuardException::class, 'unbekannt');
    Queue::assertNothingPushed();
});

it('allows recreation of a remotely deleted invoice row', function (): void {
    Queue::fake();
    $externalUser = ExternalUser::factory()->create();
    $event = DonationEvent::factory()->create();
    $deleted = DonorEventInvoice::factory()->forExternalUser($externalUser)->forEvent($event)->create([
        'remote_deleted_at' => now(),
    ]);

    $invoice = app(CreateDonorInvoiceAction::class)($externalUser, $event);

    expect($invoice->is($deleted))->toBeTrue();
    Queue::assertPushed(CreateDonorInvoice::class);
});
