<?php

use App\Actions\CreateDonorInvoiceAction;
use App\Jobs\CreateDonorInvoice;
use App\Models\DonationEvent;
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
