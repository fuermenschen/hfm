<?php

declare(strict_types=1);

namespace App\Services\Webling\Invoice;

use App\Enums\DonorPaymentVerification;
use App\Exceptions\Webling\WeblingApiException;
use App\Services\Webling\Invoice\Dto\InvoiceCreateData;
use App\Services\Webling\WeblingApiService;
use App\Settings\WeblingApiSettings;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Service for working with invoices ("debitor") via Webling API.
 *
 * This service encapsulates invoice-related operations and uses
 * the WeblingApiService for HTTP communication.
 */
class WeblingInvoiceService
{
    public const string DonorInvoiceMarkerPrefix = 'HFM-DONOR-INVOICE:';

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
     * @param  array<int,array{amount_cents:int, title:string}>  $invoiceLines
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
     * - invoice_lines: array<int, array{amount_cents: int, title: string}>
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
     * Create an invoice with the stable local invoice marker.
     *
     * @param  InvoiceCreateData|array<string,mixed>  $data
     */
    public function createInvoiceWithMarker(int $localInvoiceId, InvoiceCreateData|array $data): Response
    {
        $marker = $this->commentMarker($localInvoiceId);

        if (is_array($data)) {
            $data['comment'] = $marker;
        } else {
            $data->comment = $marker;
        }

        return $this->createInvoice($data);
    }

    public function commentMarker(int $localInvoiceId): string
    {
        return self::DonorInvoiceMarkerPrefix.$localInvoiceId;
    }

    /**
     * Derive the Webling web UI URL for a Debitor (verified against demo1.webling.ch).
     */
    public function debitorUrl(int $debitorId): string
    {
        return sprintf(
            '%s/admin#/accounting/%d/debitor/:debitor/view/%d',
            rtrim($this->settings->api_url, '/'),
            $this->settings->accounting_period_id,
            $debitorId,
        );
    }

    /**
     * Find full Debitor records whose comment exactly matches the marker.
     *
     * @return list<int>
     */
    public function findInvoiceIdsByCommentMarker(string $marker): array
    {
        $filter = rawurlencode('`comment`='.$this->formatValue($marker));
        $response = $this->api->get('debitor?format=full&filter='.$filter);
        $payload = $response->json();
        $objects = match (true) {
            ! is_array($payload) => [],
            isset($payload['objects']) && is_array($payload['objects']) => $payload['objects'],
            array_is_list($payload) => $payload,
            isset($payload['id']) => [$payload],
            default => [],
        };

        $ids = [];
        foreach ($objects as $key => $object) {
            if (is_int($object) || (is_string($object) && ctype_digit($object))) {
                $ids[] = (int) $object;

                continue;
            }

            if (! is_array($object)) {
                continue;
            }

            $comment = $object['properties']['comment'] ?? null;
            if ($comment !== $marker) {
                continue;
            }

            $id = $object['id'] ?? (is_int($key) ? null : $key);
            if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Retrieve an invoice by ID.
     *
     * @throws WeblingApiException
     * @throws ConnectionException
     */
    public function getInvoice(int $id): Response
    {
        return $this->api->get('debitor/'.$id);
    }

    /**
     * Read and normalize the current live details of a Debitor from Webling.
     *
     * @return array{state:string,due_date:?string,invoice_number:?string,total_cents:int,remaining_cents:int}
     *
     * @throws WeblingApiException
     * @throws ConnectionException
     * @throws RuntimeException
     */
    public function invoiceDetails(int $debitorId): array
    {
        $payload = $this->getInvoice($debitorId)->json();

        return $this->normalizeInvoiceDetails($payload, $debitorId);
    }

    /**
     * Read one supported bank receipt, including explicitly allowed excess income.
     * Account IDs are supplied by the caller for the invoice's accounting period.
     *
     * @param  list<int>  $excessIncomeAccountIds
     * @return array{state:string,due_date:?string,invoice_number:?string,total_cents:int,remaining_cents:int,payment_verification:string,received_cents:?int,payment_date:?string}
     */
    // TODO(dead-code): Connect this reader to invoice refresh in task 4 of issue #285.
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function settlementDetails(int $debitorId, int $bankAccountId, array $excessIncomeAccountIds = []): array
    {
        $payload = $this->getInvoice($debitorId)->json();
        $details = $this->normalizeInvoiceDetails($payload, $debitorId);
        $result = $details + ['payment_verification' => DonorPaymentVerification::Unverified->value, 'received_cents' => null, 'payment_date' => null];

        if ($details['state'] !== 'paid') {
            return $result;
        }

        $result['payment_verification'] = DonorPaymentVerification::Unclear->value;
        $paidCents = $this->amountInCents($payload['properties']['paidamount'] ?? null);
        $writeoffCents = $this->amountInCents($payload['properties']['writeoffamount'] ?? null);
        $payments = $payload['links']['payment'] ?? null;
        if ($bankAccountId < 1 || $details['remaining_cents'] !== 0 || $paidCents === null || $paidCents <= 0
            || $writeoffCents === null || $writeoffCents < 0 || $details['total_cents'] !== $paidCents + $writeoffCents
            || ! is_array($payments) || ! array_is_list($payments) || count($payments) !== 1 || ! is_array($payments[0])) {
            return $result;
        }

        $payment = $payments[0];
        $paymentId = $this->objectId($payment['id'] ?? null);
        $parents = $this->objectIds($payment['parents'] ?? null);
        $debitAccounts = $this->objectIds($payment['links']['debit'] ?? null);
        $creditAccounts = $this->objectIds($payment['links']['credit'] ?? null);
        if ($paymentId === null || $parents === null || count($parents) !== 1 || $debitAccounts !== [$bankAccountId]
            || $creditAccounts === null || count($creditAccounts) !== 1 || $creditAccounts[0] === $bankAccountId
            || $this->objectIds($payment['links']['debitor'] ?? null) !== [$debitorId]
            || $this->amountInCents($payment['properties']['amount'] ?? null) !== $paidCents) {
            return $result;
        }

        $debitorAccountId = $creditAccounts[0];
        foreach (['revenue' => $details['total_cents'], 'writeoff' => $writeoffCents] as $category => $expectedCents) {
            $invoiceEntries = $payload['links'][$category] ?? [];
            if (! is_array($invoiceEntries) || ! array_is_list($invoiceEntries)) {
                return $result;
            }

            $sum = 0;
            $seenIds = [];
            foreach ($invoiceEntries as $invoiceEntry) {
                $id = is_array($invoiceEntry) ? $this->objectId($invoiceEntry['id'] ?? null) : null;
                $amount = $this->amountInCents($invoiceEntry['properties']['amount'] ?? null);
                $accountLink = $category === 'revenue' ? 'debit' : 'credit';
                if ($id === null || isset($seenIds[$id]) || $amount === null || $amount <= 0
                    || $this->objectIds($invoiceEntry['links'][$accountLink] ?? null) !== [$debitorAccountId]
                    || $this->objectIds($invoiceEntry['links']['debitor'] ?? null) !== [$debitorId]) {
                    return $result;
                }

                $seenIds[$id] = true;
                $sum += $amount;
            }

            if ($sum !== $expectedCents) {
                return $result;
            }
        }

        $groupId = $parents[0];
        $group = $this->readEvidenceObjects('entrygroup', [$groupId])[$groupId];
        $date = $this->accountingDate($group['properties']['date'] ?? null);
        $entryIds = $this->objectIds($group['children']['entry'] ?? null);
        $invoiceParents = $this->objectIds($payload['parents'] ?? null);
        if ($date === null || $entryIds === null || count($entryIds) < 1 || count($entryIds) > 2
            || ! in_array($paymentId, $entryIds, true)
            || $invoiceParents === null || count($invoiceParents) !== 1
            || $this->objectIds($group['parents'] ?? null) !== $invoiceParents) {
            return $result;
        }

        $entries = $this->readEvidenceObjects('entry', $entryIds);
        $receivedCents = 0;
        foreach ($entries as $entryId => $entry) {
            $amountCents = $this->amountInCents($entry['properties']['amount'] ?? null);
            $credits = $this->objectIds($entry['links']['credit'] ?? null);
            if ($amountCents === null || $amountCents <= 0 || $this->objectIds($entry['parents'] ?? null) !== [$groupId]
                || $this->objectIds($entry['links']['debit'] ?? null) !== [$bankAccountId]
                || $credits === null || count($credits) !== 1) {
                return $result;
            }

            if ($entryId === $paymentId) {
                if ($credits !== [$debitorAccountId] || $amountCents !== $paidCents
                    || $this->objectIds($entry['links']['debitor'] ?? null) !== [$debitorId]) {
                    return $result;
                }
            } elseif ($this->objectIds($entry['links']['debitor'] ?? []) !== []
                || in_array($credits[0], [$bankAccountId, $debitorAccountId], true)
                || ! in_array($credits[0], $excessIncomeAccountIds, true)) {
                return $result;
            }

            $receivedCents += $amountCents;
        }

        return array_replace($result, ['payment_verification' => DonorPaymentVerification::Verified->value, 'received_cents' => $receivedCents, 'payment_date' => $date]);
    }

    /**
     * @return array{state:string,due_date:?string,invoice_number:?string,total_cents:int,remaining_cents:int}
     */
    protected function normalizeInvoiceDetails(mixed $payload, int $debitorId): array
    {
        $properties = is_array($payload) ? ($payload['properties'] ?? null) : null;
        throw_unless(is_array($properties), RuntimeException::class, 'Webling returned an unreadable Debitor payload for ID '.$debitorId.'.');

        $state = $properties['state'] ?? null;
        throw_unless(is_string($state) && $state !== '', RuntimeException::class, 'Webling returned no Debitor state for ID '.$debitorId.'.');

        $dueDate = $properties['duedate'] ?? null;
        throw_if($dueDate !== null && $dueDate !== '' && $this->accountingDate($dueDate) === null, RuntimeException::class, 'Webling returned an invalid Debitor due date for ID '.$debitorId.'.');
        $totalCents = $this->amountInCents($properties['totalamount'] ?? null);
        $remainingCents = $this->amountInCents($properties['remainingamount'] ?? null);
        throw_if($totalCents === null || $remainingCents === null, RuntimeException::class, 'Webling returned invalid Debitor amounts for ID '.$debitorId.'.');

        return [
            'state' => $state,
            'due_date' => is_string($dueDate) && $dueDate !== '' ? $dueDate : null,
            'invoice_number' => is_numeric($properties['debitorid'] ?? null) ? (string) (int) $properties['debitorid'] : null,
            'total_cents' => $totalCents,
            'remaining_cents' => $remainingCents,
        ];
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, array<string, mixed>>
     */
    protected function readEvidenceObjects(string $type, array $ids): array
    {
        $path = $type.'/'.implode(',', $ids);
        try {
            $payload = $this->api->get($path)->json();
        } catch (WeblingApiException $weblingApiException) {
            throw_if($weblingApiException->category === WeblingApiException::NotFound, RuntimeException::class, 'Webling payment evidence was not found at '.$path.'.', 0, $weblingApiException);

            throw $weblingApiException;
        }

        throw_unless(is_array($payload), RuntimeException::class, 'Webling returned unreadable payment evidence at '.$path.'.');
        $objects = isset($payload['type']) ? [$payload + ['id' => $ids[0]]] : ($payload['objects'] ?? $payload);
        throw_unless(is_array($objects) && array_is_list($objects), RuntimeException::class, 'Webling returned unreadable payment evidence at '.$path.'.');
        $indexed = [];
        foreach ($objects as $object) {
            $id = is_array($object) ? $this->objectId($object['id'] ?? null) : null;
            throw_if($id === null || ($object['type'] ?? null) !== $type || ! is_array($object['properties'] ?? null)
                || ! in_array($id, $ids, true) || isset($indexed[$id]), RuntimeException::class, 'Webling returned inconsistent payment evidence at '.$path.'.');
            $indexed[$id] = $object;
        }

        throw_if(count($indexed) !== count($ids), RuntimeException::class, 'Webling returned incomplete payment evidence at '.$path.'.');

        return $indexed;
    }

    protected function amountInCents(mixed $amount): ?int
    {
        if ((! is_int($amount) && ! is_float($amount) && ! is_string($amount))
            || ! preg_match('/\A(-?)([0-9]{1,15})(?:\.([0-9]{1,2}))?\z/', (string) $amount, $matches)) {
            return null;
        }

        $cents = (int) $matches[2] * 100 + (int) str_pad($matches[3] ?? '', 2, '0');

        return $matches[1] === '-' ? -$cents : $cents;
    }

    protected function objectId(mixed $id): ?int
    {
        if (! is_int($id) && ! is_string($id)) {
            return null;
        }

        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    /** @return list<int>|null */
    protected function objectIds(mixed $ids): ?array
    {
        if (! is_array($ids) || ! array_is_list($ids)) {
            return null;
        }

        $normalized = [];
        foreach ($ids as $id) {
            $id = $this->objectId($id);
            if ($id === null || in_array($id, $normalized, true)) {
                return null;
            }

            $normalized[] = $id;
        }

        return $normalized;
    }

    protected function accountingDate(mixed $date): ?string
    {
        if (! is_string($date) || ! preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $date, $matches)
            || ! checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return null;
        }

        return $date;
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
     *
     * @throws WeblingApiException
     * @throws ConnectionException
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
