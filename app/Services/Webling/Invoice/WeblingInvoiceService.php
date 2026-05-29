<?php

declare(strict_types=1);

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
     * List invoice (debitor) IDs with optional filter.
     *
     * Examples:
     * - index() → GET debitor (all IDs)
     * - index('`state`="paid"') → GET debitor?filter=...
     * - index([['state', '!=', 'paid']]) → GET debitor?filter=`state`!="paid"
     * - index([
     *     ['state', '!=', 'paid'],
     *     ['duedate', '<', 'TODAY()'],
     *   ]) → GET debitor?filter=`state`!="paid"AND`duedate`<TODAY()
     * - index(['state' => 'paid']) → GET debitor?filter=`state`="paid"
     *
     * @param  null|string|array<int,array{0:string,1:string,2:mixed}>|array<string,mixed>  $filter
     */
    // TODO(dead-code): Remove ignore when donor_event_invoices status sync is reintroduced.
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function index(null|string|array $filter = null): Response
    {
        if ($filter === null) {
            return $this->api->get('debitor');
        }

        $filterString = is_string($filter)
            ? $filter
            : $this->buildFilter($filter);

        $encoded = rawurlencode($filterString);

        return $this->api->get('debitor?filter='.$encoded);
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
    // Temporarily unused in active flows; kept for upcoming Webling sync operations.
    // TODO(dead-code): Remove ignore when get-by-id flow is reintroduced.
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function getInvoice(int $id): Response
    {
        return $this->api->get('debitor/'.$id);
    }

    /**
     * Update an invoice by ID.
     *
     * @param  array<string,mixed>  $data  Invoice payload updates
     */
    // Temporarily unused in active flows; kept for upcoming Webling sync operations.
    // TODO(dead-code): Remove ignore when invoice update flow is reintroduced.
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function updateInvoice(int $id, array $data): Response
    {
        return $this->api->put('debitor/'.$id, $data);
    }

    /**
     * Delete an invoice by ID.
     */
    public function deleteInvoice(int $id): Response
    {
        return $this->api->delete('debitor/'.$id);
    }

    /**
     * Build a Webling filter string from an array definition.
     *
     * Supported forms:
     * - ['field' => value, 'field2' => value2]
     * - [[field, operator, value], [field2, operator, value2]]
     * Values:
     * - strings are quoted: "value"
     * - numbers are kept as-is
     * - Carbon|string dates are quoted as YYYY-MM-DD
     * - UPPERCASE_FUNCTION() values are kept as-is (no quotes)
     *
     * @param  array<int,array{0:string,1:string,2:mixed}>|array<string,mixed>  $conditions
     */
    protected function buildFilter(array $conditions): string
    {
        $parts = [];

        // Associative array of equals
        if ($this->isAssoc($conditions)) {
            foreach ($conditions as $field => $value) {
                $parts[] = $this->quoteName((string) $field).'='.$this->formatValue($value);
            }
        } else {
            // List of triplets
            foreach ($conditions as $cond) {
                if (! is_array($cond) || count($cond) !== 3) {
                    continue; // ignore invalid entries silently
                }

                [$field, $op, $value] = $cond;
                $parts[] = $this->quoteName((string) $field).trim((string) $op).$this->formatValue($value);
            }
        }

        return implode('AND', $parts);
    }

    protected function isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Quote a field name for Webling filter: `field`.
     */
    protected function quoteName(string $name): string
    {
        return '`'.str_replace('`', '', $name).'`';
    }

    /**
     * Format a value for the filter string.
     */
    protected function formatValue(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return '"'.$value->format('Y-m-d').'"';
        }

        if (is_string($value)) {
            // If looks like an UPPERCASE_FUNCTION(), keep it as-is.
            if (preg_match('/^[A-Z_]+\(\)$/', $value) === 1) {
                return $value;
            }

            return '"'.str_replace('"', '\"', $value).'"';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return 'NULL';
        }

        // Fallback to JSON string
        return '"'.addslashes((string) $value).'"';
    }
}
