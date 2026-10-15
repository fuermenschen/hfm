<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DonorInvoiceStatus;
use App\Enums\DonorPaymentVerification;
use App\Models\DonorEventInvoice;
use Illuminate\Support\Facades\Date;
use LogicException;

/**
 * Derives donor event invoice display status and receipt eligibility.
 *
 * Lives in a service because overdue detection spans the invoice and its
 * donation event timezone; model helpers must stay database-free.
 */
class DonorInvoiceService
{
    /**
     * Shared contract for receipt generation and portal receipt downloads.
     * Writeoffs may settle an invoice without covering its total in cash.
     *
     * @api
     */
    public function isReceiptEligible(DonorEventInvoice $invoice): bool
    {
        return $invoice->remote_deleted_at === null
            && ($invoice->webling_debitor_id ?? 0) > 0
            && $invoice->webling_state === 'paid'
            && $invoice->webling_remaining_cents === 0
            && $invoice->webling_payment_verification === DonorPaymentVerification::Verified
            && ($invoice->webling_received_cents ?? 0) > 0
            && $invoice->webling_payment_date !== null
            && $invoice->webling_synced_at !== null;
    }

    public function status(DonorEventInvoice $invoice): DonorInvoiceStatus
    {
        if ($invoice->remote_deleted_at !== null) {
            return DonorInvoiceStatus::RemoteDeleted;
        }

        if ($invoice->webling_state === 'paid') {
            return DonorInvoiceStatus::Paid;
        }

        if ($invoice->webling_state === 'writeoff') {
            return DonorInvoiceStatus::Writeoff;
        }

        if ($invoice->webling_state !== null && ! in_array($invoice->webling_state, DonorEventInvoice::UnpaidStates, true)) {
            return DonorInvoiceStatus::Unknown;
        }

        if ($this->isOverdue($invoice)) {
            return DonorInvoiceStatus::Overdue;
        }

        if ($invoice->webling_state === 'partially paid') {
            return DonorInvoiceStatus::PartiallyPaid;
        }

        if ($invoice->invoice_sent_at !== null) {
            return DonorInvoiceStatus::Sent;
        }

        if ($invoice->webling_debitor_id !== null) {
            return DonorInvoiceStatus::Created;
        }

        return DonorInvoiceStatus::NotCreated;
    }

    public function isOverdue(DonorEventInvoice $invoice): bool
    {
        if (! in_array($invoice->webling_state, DonorEventInvoice::UnpaidStates, true) || $invoice->webling_due_date === null) {
            return false;
        }

        throw_unless(
            $invoice->relationLoaded('donationEvent'),
            LogicException::class,
            'The donationEvent relation must be eager-loaded before overdue detection.',
        );

        return $invoice->webling_due_date->toDateString() < Date::now($invoice->donationEvent->timezone)->toDateString();
    }
}
