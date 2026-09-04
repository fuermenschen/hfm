<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\DonorInvoiceGuardException;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use Closure;
use Throwable;

/**
 * Runs one invoice operation over many invoices with per-item failure
 * isolation. Guard rejections count as skipped, unexpected errors as
 * failed; one broken item never stops the others.
 */
class RunDonorInvoiceBulkAction
{
    /**
     * @param  iterable<DonorEventInvoice>  $invoices
     * @return array{successful:int,skipped:int,failed:int,messages:list<string>}
     */
    public function __invoke(DonationEvent $event, iterable $invoices, Closure $operation): array
    {
        $result = ['successful' => 0, 'skipped' => 0, 'failed' => 0, 'messages' => []];

        foreach ($invoices as $invoice) {
            if ($invoice->donation_event_id !== $event->id) {
                $result['skipped']++;
                $result['messages'][] = 'Rechnung '.$invoice->id.': Die Rechnung gehört nicht zum ausgewählten Anlass.';

                continue;
            }

            try {
                $operation($invoice);
                $result['successful']++;
            } catch (DonorInvoiceGuardException $exception) {
                $result['skipped']++;
                $result['messages'][] = 'Rechnung '.$invoice->id.': '.$exception->getMessage();
            } catch (Throwable $exception) {
                $result['failed']++;
                $result['messages'][] = 'Rechnung '.$invoice->id.': '.$exception->getMessage();
            }
        }

        return $result;
    }
}
