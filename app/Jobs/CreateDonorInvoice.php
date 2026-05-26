<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ExternalUser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateDonorInvoice implements ShouldQueue
{
    use Queueable;

    // @phpstan-ignore-next-line shipmonk.deadProperty.neverRead
    public function __construct(public ExternalUser $externalUser) {}

    /**
     * TODO(refactor-external-user):
     * Replace donor-bound orchestration with donor_event_invoices workflow (GH-134).
     *
     * Keep orchestration pattern from legacy flow:
     * `Bus::chain([new CreateDonorInvoiceDebitor(...), new CreateDonorInvoiceLetter(...)])->onConnection('sync')->dispatch();`
     *
     * Pseudo code:
     * - Resolve event-scoped invoice aggregate.
     * - Keep sequential chain order: debitor creation before letter generation.
     * - Keep explicit sync connection for deterministic inline behavior where needed.
     * - Dispatch chain through Bus facade.
     */
    public function handle(): void {}
}
