<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Donor;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CheckDonorInvoicesStatus implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /**
     * Fetch paid and overdue debitors from Webling and update matching donors' webling_data.payment_status.
     */
    public function handle(WeblingInvoiceService $invoices): void
    {
        try {
            // Fetch IDs of paid debitors
            $paidResponse = $invoices->index(['state' => 'paid']);
            $paidIds = $this->extractIds($paidResponse->json());

            // Fetch IDs of overdue debitors: state != paid AND duedate < TODAY()
            $overdueResponse = $invoices->index([
                ['state', '!=', 'paid'],
                ['duedate', '<', 'TODAY()'],
            ]);
            $overdueIds = $this->extractIds($overdueResponse->json());

            // Update donors with matching debitor_id to 'paid'
            if ($paidIds !== []) {
                Donor::query()
                    ->whereIn('webling_data->debitor_id', $paidIds)
                    ->chunkById(200, function ($donors): void {
                        /** @var Donor $donor */
                        foreach ($donors as $donor) {
                            $data = $donor->webling_data ?? [];
                            $data['payment_status'] = 'paid';
                            $donor->webling_data = $data;
                            $donor->save();
                        }
                    });
            }

            // Update donors with matching debitor_id to 'overdue' (but don't override 'paid')
            if ($overdueIds !== []) {
                Donor::query()
                    ->whereIn('webling_data->debitor_id', $overdueIds)
                    ->chunkById(200, function ($donors): void {
                        /** @var Donor $donor */
                        foreach ($donors as $donor) {
                            $data = $donor->webling_data ?? [];
                            // If already marked as paid, keep it
                            if (($data['payment_status'] ?? null) === 'paid') {
                                continue;
                            }

                            $data['payment_status'] = 'overdue';
                            $donor->webling_data = $data;
                            $donor->save();
                        }
                    });
            }
        } catch (\Throwable $throwable) {
            Log::error('Failed to check donor invoice status', [
                'message' => $throwable->getMessage(),
            ]);
            throw $throwable;
        }
    }

    /**
     * Attempt to extract an array of integer IDs from a Webling index response.
     * Accepts a few common shapes: [1,2,3], ['items' => [..]], ['response' => ['items' => [..]]].
     * Falls back to collecting all top-level integer values.
     *
     * @param  array<string,mixed>|array<int,mixed>|null  $payload
     * @return array<int,int>
     */
    protected function extractIds(?array $payload): array
    {
        if ($payload === null) {
            return [];
        }

        // Most likely shapes
        $items = $payload['items'] ?? null;
        if (is_array($items)) {
            return array_values(array_filter(array_map('intval', $items), fn (int $v): bool => $v > 0));
        }

        if (isset($payload['response']) && is_array($payload['response'])) {
            $resp = $payload['response'];
            if (isset($resp['items']) && is_array($resp['items'])) {
                return array_values(array_filter(array_map('intval', $resp['items']), fn (int $v): bool => $v > 0));
            }
        }

        // If the payload itself is a list of numbers
        if (Arr::isList($payload)) {
            return array_values(array_filter(array_map('intval', $payload), fn (int $v): bool => $v > 0));
        }

        // Fallback: collect any integer-ish values across payload
        $collected = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($payload));
        foreach ($iterator as $value) {
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $collected[] = (int) $value;
            }
        }

        return array_values(array_unique(array_filter($collected, fn (int $v): bool => $v > 0)));
    }
}
