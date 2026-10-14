<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\DonorInvoiceGuardException;
use App\Exceptions\Webling\WeblingApiException;
use App\Models\DonorEventInvoice;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Settings\WeblingApiSettings;
use Illuminate\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RefreshDonorInvoiceStatusAction
{
    public function __construct(private WeblingInvoiceService $weblingInvoices, private WeblingApiSettings $settings) {}

    public function __invoke(DonorEventInvoice $invoice): void
    {
        $seconds = max(120, 3 * max(1, (int) config('services.webling.options.timeout', 10)) + 30);
        $lock = Cache::lock('donor-invoice-creation:'.$invoice->id, $seconds);
        throw_unless($lock instanceof Lock, RuntimeException::class, 'The configured cache store does not support renewable invoice locks.');
        $lock->block(10, function () use ($invoice, $lock, $seconds): void {
            $invoice->refresh();
            throw_if($invoice->remote_deleted_at !== null, DonorInvoiceGuardException::class, 'Die Rechnung wurde in Webling gelöscht.');
            throw_if($invoice->webling_debitor_id === null, DonorInvoiceGuardException::class, 'Die Rechnung ist in Webling nicht erstellt.');
            $debitorId = $invoice->webling_debitor_id;
            $details = null;

            try {
                $details = $this->weblingInvoices->settlementDetails(
                    $debitorId,
                    $this->settings->bank_account_id ?? 0,
                    $this->settings->excess_income_account_id === null ? [] : [$this->settings->excess_income_account_id],
                );
            } catch (WeblingApiException $weblingApiException) {
                throw_if($weblingApiException->category !== WeblingApiException::NotFound, $weblingApiException);
            }

            DB::transaction(function () use ($invoice, $lock, $seconds, $debitorId, $details): void {
                $current = DonorEventInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
                throw_unless($lock->refresh($seconds), LockTimeoutException::class, 'Invoice refresh lock expired before persistence.');
                throw_if($current->webling_debitor_id !== $debitorId || $current->remote_deleted_at !== null, DonorInvoiceGuardException::class, 'Die Rechnung wurde während der Aktualisierung geändert. Bitte erneut aktualisieren.');

                if ($details === null) {
                    $current->markRemotelyDeleted();

                    return;
                }

                $current->forceFill([
                    'webling_state' => $details['state'],
                    'webling_due_date' => $details['due_date'],
                    'webling_invoice_number' => $details['invoice_number'],
                    'webling_total_cents' => $details['total_cents'],
                    'webling_remaining_cents' => $details['remaining_cents'],
                    'webling_received_cents' => $details['received_cents'],
                    'webling_payment_date' => $details['payment_date'],
                    'webling_payment_verification' => $details['payment_verification'],
                    'webling_synced_at' => Date::now(),
                ])->save();
            });
            $invoice->refresh();
        });
    }
}
