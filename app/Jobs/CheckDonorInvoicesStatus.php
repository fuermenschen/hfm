<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckDonorInvoicesStatus implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /**
     * TODO(refactor-external-user):
     * Replace donor payment-status sync with donor_event_invoices projection (GH-134).
     *
     * Keep generic Webling index queries, but write status into event-scoped invoice model.
     * Keep filter semantics from legacy flow:
     * - paid query: ['state' => 'paid']
     * - overdue query: [['state', '!=', 'paid'], ['duedate', '<', 'TODAY()']]
     * Keep update precedence semantics:
     * - apply paid first
     * - apply overdue second
     * - never overwrite already paid with overdue
     *
     * Pseudo code:
     * - Fetch paid and overdue debitor ids from WeblingInvoiceService.
     * - Map debitor ids to donor_event_invoices rows.
     * - Persist payment_status without overriding paid with overdue.
     */
    public function handle(WeblingInvoiceService $invoices): void {}
}
