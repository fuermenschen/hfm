<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExternalUser;

class DonorInvoiceService
{
    /**
     * TODO(refactor-external-user):
     * Rebuild donor invoice workflow on event-scoped model `donor_event_invoices` (GH-134).
     *
     * Important: donor invoices are separate from association donation invoices.
     * Reintroduction must keep these domains isolated.
     *
     * Pseudo code for future implementation:
     * - Resolve target external user + donation event scope.
     * - Upsert donor_event_invoice aggregate row.
     * - Trigger Webling debitor + letter generation via reusable services.
     * - Persist generated artifact references on donor_event_invoices.
     * - Return UI feedback mapped to event-scoped invoice state.
     *
     * Keep legacy user-interface guard semantics for parity:
     * - already created: debitor_id + letter_pdf present => warning, no refresh
     * - missing email => danger
     * - missing PDF or missing file in storage => danger
     * - success branches => success with refresh=true
     *
     * @return array{heading:string,text:string,variant:string,duration:int|null,refresh:bool}
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function createInvoice(ExternalUser $externalUser): array
    {
        return $this->featureParkedResponse();
    }

    /**
     * TODO(refactor-external-user): Replace donor-bound delete flow with donor_event_invoices workflow (GH-134).
     * Keep legacy idempotent semantics: no remote call when both debitor and PDF metadata are absent.
     *
     * @return array{heading:string,text:string,variant:string,duration:int|null,refresh:bool}
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function deleteInvoice(ExternalUser $externalUser): array
    {
        return $this->featureParkedResponse();
    }

    /**
     * TODO(refactor-external-user): Download source should come from donor_event_invoices storage metadata (GH-134).
     * Keep file-name convention parity: invoice_DON-{event-aware-id}.pdf.
     *
     * @return array{absolute_path:string,file_name:string}|null
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function getDownloadData(ExternalUser $externalUser): ?array
    {
        return null;
    }

    /**
     * TODO(refactor-external-user): Implement send flow against donor_event_invoices model (GH-134).
     *
     * Pseudo code:
     * - Validate event-scoped invoice exists and has generated PDF.
     * - Queue mail with attachment from reusable GenericMailMessage.
     * - Persist sent timestamp on donor_event_invoices row.
     * - Subject/body keep donor-invoice wording (not association donation invoice wording).
     *
     * @return array{heading:string,text:string,variant:string,duration:int|null,refresh:bool}
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function sendInvoice(ExternalUser $externalUser): array
    {
        return $this->featureParkedResponse();
    }

    /**
     * TODO(refactor-external-user): Implement reminder flow against donor_event_invoices model (GH-134).
     * Keep legacy guard order: invoice must be sent -> status must be overdue -> email must exist -> PDF must exist.
     *
     * @return array{heading:string,text:string,variant:string,duration:int|null,refresh:bool}
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function sendReminder(ExternalUser $externalUser): array
    {
        return $this->featureParkedResponse();
    }

    /**
     * TODO(refactor-external-user): Derive status from donor_event_invoices state machine (GH-134).
     * Keep precedence parity: paid > overdue > sent > created > not_created.
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function formatInvoiceStatus(ExternalUser $externalUser): string
    {
        return '-';
    }

    /**
     * TODO(refactor-external-user): Bulk-eligibility must evaluate event-scoped invoice state (GH-134).
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function canCreateInvoiceInBulk(ExternalUser $externalUser): bool
    {
        return false;
    }

    /**
     * TODO(refactor-external-user): Bulk-eligibility must evaluate event-scoped invoice state (GH-134).
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function canSendInvoiceInBulk(ExternalUser $externalUser): bool
    {
        return false;
    }

    /**
     * TODO(refactor-external-user): Bulk-eligibility must evaluate event-scoped invoice state (GH-134).
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function canSendReminderInBulk(ExternalUser $externalUser): bool
    {
        return false;
    }

    /**
     * TODO(refactor-external-user): Replace summary query with donor_event_invoices projection (GH-134).
     * Keep mutually exclusive buckets: paid, overdue, sent, created, not_created.
     *
     * @return array{paid:int,overdue:int,sent:int,created:int,not_created:int}
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function invoiceStatusSummary(): array
    {
        return [
            'paid' => 0,
            'overdue' => 0,
            'sent' => 0,
            'created' => 0,
            'not_created' => 0,
        ];
    }

    /**
     * @return array{heading:string,text:string,variant:string,duration:int|null,refresh:bool}
     */
    protected function featureParkedResponse(): array
    {
        return [
            'heading' => 'Funktion deaktiviert',
            'text' => 'Donor-Invoice-Workflow ist bis zur Neuimplementierung mit donor_event_invoices (GH-134) deaktiviert.',
            'variant' => 'warning',
            'duration' => null,
            'refresh' => false,
        ];
    }
}
