<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ExternalUser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteDonorInvoiceDebitor implements ShouldQueue
{
    use Queueable;

    // @phpstan-ignore-next-line shipmonk.deadProperty.neverRead
    public function __construct(public ExternalUser $externalUser) {}

    /**
     * TODO(refactor-external-user):
     * Implement cleanup against donor_event_invoices aggregate (GH-134).
     *
     * Keep important cleanup semantics from legacy flow:
     * - Attempt local PDF deletion first, ignore storage exceptions.
     * - If no debitor id exists, persist local cleanup and exit.
     * - Delete remote debitor via WeblingInvoiceService::deleteInvoice($id).
     * - Treat HTTP 204 and 404 as successful idempotent cleanup.
     * - Any other response should raise RuntimeException and keep debitor reference for retry.
     *
     * Pseudo code:
     * - Delete local PDF artifact from aggregate metadata.
     * - Delete debitor through generic WeblingInvoiceService.
     * - Treat 204/404 as successful idempotent cleanup.
     * - Clear debitor references on donor_event_invoices row.
     */
    public function handle(): void {}
}
