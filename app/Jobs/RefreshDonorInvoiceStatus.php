<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\RefreshDonorInvoiceStatusAction;
use App\Exceptions\DonorInvoiceGuardException;
use App\Exceptions\Webling\WeblingApiException;
use App\Models\DonorEventInvoice;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Tries(3)]
#[Backoff([60, 300])]
#[UniqueFor(86400)]
class RefreshDonorInvoiceStatus implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public int $invoiceId)
    {
        $this->onQueue('maintenance');
    }

    public function uniqueId(): string
    {
        return (string) $this->invoiceId;
    }

    public function handle(RefreshDonorInvoiceStatusAction $refresh): void
    {
        $invoice = DonorEventInvoice::query()->whereHas('externalUser')->find($this->invoiceId);
        if ($invoice === null || $invoice->webling_debitor_id === null || $invoice->remote_deleted_at !== null) {
            return;
        }

        try {
            $refresh($invoice);
        } catch (DonorInvoiceGuardException $exception) {
            $current = $invoice->fresh();
            if ($current === null || $current->webling_debitor_id === null || $current->remote_deleted_at !== null) {
                return;
            }

            throw $exception;
        } catch (WeblingApiException $exception) {
            if (! $exception->response->serverError() && ! in_array($exception->category, [WeblingApiException::Transient, WeblingApiException::RateLimited], true)) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Donor invoice refresh failed.', ['invoice_id' => $this->invoiceId, 'exception' => $exception]);
    }
}
