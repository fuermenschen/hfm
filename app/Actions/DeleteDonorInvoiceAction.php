<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\DonorInvoiceGuardException;
use App\Exceptions\Webling\WeblingApiException;
use App\Models\DonorEventInvoice;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Support\Facades\Cache;

class DeleteDonorInvoiceAction
{
    public function __construct(private WeblingInvoiceService $weblingInvoices) {}

    public function __invoke(DonorEventInvoice $invoice): void
    {
        Cache::lock('donor-invoice-creation:'.$invoice->id, 120)->block(10, function () use ($invoice): void {
            $invoice = DonorEventInvoice::query()->with('donationEvent')->findOrFail($invoice->id);

            throw_if($invoice->remote_deleted_at !== null, DonorInvoiceGuardException::class, 'Die Rechnung wurde bereits gelöscht.');

            // Local creation never reached Webling: clean up locally without a remote request.
            if ($invoice->webling_debitor_id === null) {
                $invoice->markRemotelyDeleted();

                return;
            }

            // Live Webling read: fail closed when Webling is unavailable.
            try {
                $details = $this->weblingInvoices->invoiceDetails($invoice->webling_debitor_id);
            } catch (WeblingApiException $weblingApiException) {
                throw_if($weblingApiException->category !== WeblingApiException::NotFound, $weblingApiException);

                $invoice->markRemotelyDeleted();

                return;
            }

            throw_if($details['state'] === 'paid' || $details['state'] === 'writeoff', DonorInvoiceGuardException::class, 'Bezahlte oder abgeschriebene Rechnungen können nicht gelöscht werden.');

            throw_if($details['state'] !== 'open', DonorInvoiceGuardException::class, 'Nur offene Rechnungen können gelöscht werden.');

            try {
                $this->weblingInvoices->deleteInvoice($invoice->webling_debitor_id);
            } catch (WeblingApiException $weblingApiException) {
                // A confirmed 404 means the remote invoice is already gone: cleanup is still successful.
                throw_if($weblingApiException->category !== WeblingApiException::NotFound, $weblingApiException);
            }

            $invoice->markRemotelyDeleted();
        });
    }
}
