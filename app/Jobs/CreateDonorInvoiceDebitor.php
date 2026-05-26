<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ExternalUser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateDonorInvoiceDebitor implements ShouldQueue
{
    use Queueable;

    // @phpstan-ignore-next-line shipmonk.deadProperty.neverRead
    public function __construct(public ExternalUser $externalUser) {}

    /**
     * TODO(refactor-external-user):
     * Implement debitor creation against donor_event_invoices aggregate (GH-134).
     *
     * Keep generic Webling invoice service. Replace donor table dependencies.
     * Keep important mapping behavior from legacy flow:
     * - Line title pattern: "{athlete} for {partner} | {rounds} laps at CHF {amount}".
     * - Add suffix "| Max. Fr. X" or "| Min. Fr. X" when subtotal clipped.
     * - Drop zero-amount lines before API call.
     * - Prefix non-CH postal code with country code when missing (e.g. DE-12345).
     * - Due date: invoice settings due_days, fallback +14 days.
     * - Persist both debitor_id and computed debitor_url.
     *
     * Pseudo code:
     * - Collect event-scoped invoice lines.
     * - Build InvoiceCreateData payload with title/date/duedate/address_lines/period_id/invoice_lines.
     * - Persist debitor_id + debitor_url on donor_event_invoices.
     */
    public function handle(): void {}
}
