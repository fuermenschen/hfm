<?php

namespace App\Services\Webling\Invoice;

use App\Services\Webling\Invoice\Dto\InvoiceCreateData;
use App\Services\Webling\WeblingApiService;
use App\Settings\WeblingApiSettings;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;

/**
 * Service for working with invoices ("debitor") via Webling API.
 *
 * This service encapsulates invoice-related operations and uses
 * the WeblingApiService for HTTP communication.
 */
class WeblingInvoiceService
{
    public function __construct(public WeblingApiService $api, public WeblingApiSettings $settings) {}

    /**
     * Convenience helper to create an invoice from discrete arguments.
     *
     * @param  array<int,array{amount: float, title: string}>  $invoiceLines
     */
    public function createInvoiceFromParams(
        string $title,
        Carbon $date,
        Carbon $dueDate,
        array $addressLines,
        int $periodId,
        array $invoiceLines,
        int $accountingPeriodId,
        int $debitAccountId,
        int $creditAccountId,
    ): Response {
        $dto = new InvoiceCreateData(
            title: $title,
            date: $date,
            dueDate: $dueDate,
            addressLines: $addressLines,
            periodId: $periodId,
            invoiceLines: $invoiceLines,
            accountingPeriodId: $accountingPeriodId,
            debitAccountId: $debitAccountId,
            creditAccountId: $creditAccountId,
        );

        return $this->createInvoice($dto);
    }

    /**
     * Create an invoice (debitor) in Webling.
     *
     * Accepts a DTO or a plain array. If array is provided, it will be
     * converted into a DTO first.
     *
     * Expected fields when passing an array:
     * - title: string
     * - date: Carbon|string (Y-m-d)
     * - duedate: Carbon|string (Y-m-d)
     * - address_lines: string[]
     * - period_id: int
     * - invoice_lines: array<int, array{amount: float, title: string}>
     * - accounting_period_id: int (defaults to settings)
     * - debit_account_id: int (defaults to settings)
     * - credit_account_id: int (defaults to settings)
     *
     * @param  InvoiceCreateData|array<string,mixed>  $data
     */
    public function createInvoice(InvoiceCreateData|array $data): Response
    {
        if (is_array($data)) {
            $data['accounting_period_id'] = $data['accounting_period_id'] ?? $this->settings->accounting_period_id;
            $data['debit_account_id'] = $data['debit_account_id'] ?? $this->settings->debit_account_id;
            $data['credit_account_id'] = $data['credit_account_id'] ?? $this->settings->credit_account_id;
            $dto = InvoiceCreateData::fromArray($data);
        } else {
            $dto = $data;
            if (($dto->accountingPeriodId ?? 0) === 0) {
                $dto->accountingPeriodId = $this->settings->accounting_period_id;
            }
            if (($dto->debitAccountId ?? 0) === 0) {
                $dto->debitAccountId = $this->settings->debit_account_id;
            }
            if (($dto->creditAccountId ?? 0) === 0) {
                $dto->creditAccountId = $this->settings->credit_account_id;
            }
        }

        return $this->api->post('debitor', $dto->toWeblingPayload());
    }

    /**
     * Retrieve an invoice by ID.
     */
    public function getInvoice(int $id): Response
    {
        return $this->api->get("debitor/{$id}");
    }

    /**
     * Update an invoice by ID.
     *
     * @param  array<string,mixed>  $data  Invoice payload updates
     */
    public function updateInvoice(int $id, array $data): Response
    {
        return $this->api->put("debitor/{$id}", $data);
    }

    /**
     * Delete an invoice by ID.
     */
    public function deleteInvoice(int $id): Response
    {
        return $this->api->delete("debitor/{$id}");
    }
}
