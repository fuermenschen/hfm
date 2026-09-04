<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DonorInvoiceStatus;
use App\Models\DonorEventInvoice;
use Illuminate\Support\Facades\Date;
use LogicException;

/**
 * Derives the display status of donor event invoices.
 *
 * Lives in a service because overdue detection spans the invoice and its
 * donation event timezone; model helpers must stay database-free.
 */
class DonorInvoiceService
{
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
