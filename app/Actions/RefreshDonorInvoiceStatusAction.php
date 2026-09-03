<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\DonorInvoiceGuardException;
use App\Exceptions\Webling\WeblingApiException;
use App\Models\DonorEventInvoice;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Support\Facades\Date;

class RefreshDonorInvoiceStatusAction
{
    public function __construct(private WeblingInvoiceService $weblingInvoices) {}

    public function __invoke(DonorEventInvoice $invoice): void
    {
        throw_if($invoice->remote_deleted_at !== null, DonorInvoiceGuardException::class, 'Die Rechnung wurde in Webling gelöscht.');
        throw_if($invoice->webling_debitor_id === null, DonorInvoiceGuardException::class, 'Die Rechnung ist in Webling nicht erstellt.');

        try {
            $details = $this->weblingInvoices->invoiceDetails($invoice->webling_debitor_id);
        } catch (WeblingApiException $weblingApiException) {
            // A confirmed 404 means the remote invoice was deleted elsewhere.
            throw_if($weblingApiException->category !== WeblingApiException::NotFound, $weblingApiException);

            $invoice->markRemotelyDeleted();

            return;
        }

        $invoice->forceFill([
            'webling_state' => $details['state'] !== '' ? $details['state'] : null,
            'webling_due_date' => $details['due_date'],
            'webling_invoice_number' => $details['invoice_number'],
            'webling_total_cents' => $details['total_cents'],
            'webling_remaining_cents' => $details['remaining_cents'],
            'webling_synced_at' => Date::now(),
        ])->save();
    }
}
