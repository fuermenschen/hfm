<?php

namespace App\Services\Webling;

use App\Services\Webling\Dto\InvoiceCreateData;
use Carbon\Carbon;
use Webling\API\IResponse;

/**
 * Service for working with invoices ("debitor") via Webling API.
 *
 * This service encapsulates invoice-related operations and uses
 * the WeblingApiService for HTTP communication.
 */
class WeblingInvoiceService
{
    public function __construct(public WeblingApiService $api) {}

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
     * - accounting_period_id: int
     * - debit_account_id: int
     * - credit_account_id: int
     *
     * @param  InvoiceCreateData|array<string,mixed>  $data
     */
    public function createInvoice(InvoiceCreateData|array $data): IResponse
    {
        $dto = is_array($data) ? InvoiceCreateData::fromArray($data) : $data;

        return $this->api->post('debitor', $dto->toWeblingPayload());
    }

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
    ): IResponse {
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
     * Retrieve an invoice by ID.
     */
    public function getInvoice(int $id): IResponse
    {
        return $this->api->get("debitor/{$id}");
    }

    /**
     * Update an invoice by ID.
     *
     * @param  array<string,mixed>  $data  Invoice payload updates
     */
    public function updateInvoice(int $id, array $data): IResponse
    {
        return $this->api->put("debitor/{$id}", $data);
    }

    /**
     * Delete an invoice by ID.
     */
    public function deleteInvoice(int $id): IResponse
    {
        return $this->api->delete("debitor/{$id}");
    }
}
