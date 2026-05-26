<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ExternalUser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateDonorInvoiceLetter implements ShouldQueue
{
    use Queueable;

    // @phpstan-ignore-next-line shipmonk.deadProperty.neverRead
    public function __construct(public ExternalUser $externalUser) {}

    /**
     * TODO(refactor-external-user):
     * Implement letter generation against donor_event_invoices aggregate (GH-134).
     *
     * Keep reusable LetterService and QR settings. Remove donor table coupling.
     * Keep important generation details from legacy flow:
     * - Require debitor_id before letter generation.
     * - Skip generation when PDF metadata already exists (idempotent behavior).
     * - Due date from invoice settings due_days, fallback +14 days.
     * - Compute minimum payment from sum(invoice line totals).
     * - QR fields mapped explicitly: debtorName, debtorStreet, debtorBuildingNumber([]), debtorPostalCode, debtorCity.
     * - Persist stored PDF metadata: disk/path/size.
     *
     * Pseudo code:
     * - Read event-scoped invoice lines and due date.
     * - Generate letter + QR payload from aggregate debtor data.
     * - Store PDF metadata on donor_event_invoices.
     */
    public function handle(): void {}
}
