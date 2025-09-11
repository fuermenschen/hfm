<?php

namespace App\Services\Webling\Invoice\Dto;

use Carbon\Carbon;

/**
 * Data Transfer Object for creating a Webling invoice (debitor).
 *
 * Shapes the payload according to Webling's expected structure as shown in
 * temp/01_post_debitor.json.
 */
class InvoiceCreateData
{
    /**
     * @param  array<int,string>  $addressLines
     * @param  array<int,array{amount: float, title: string}>  $invoiceLines
     */
    public function __construct(
        public string $title,
        public Carbon $date,
        public Carbon $dueDate,
        public array $addressLines,
        public int $periodId,
        public array $invoiceLines,
        public int $accountingPeriodId,
        public int $debitAccountId,
        public int $creditAccountId,
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $date = $data['date'] instanceof Carbon ? $data['date'] : Carbon::parse((string) ($data['date'] ?? now()->toDateString()));
        $due = (isset($data['duedate']) && $data['duedate'] instanceof Carbon)
            ? $data['duedate']
            : Carbon::parse((string) ($data['duedate'] ?? $data['due_date'] ?? $date->copy()->addDays(30)->toDateString()));

        // Default title if empty
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = 'Rechnung'; // default title
        }

        return new self(
            title: $title,
            date: $date,
            dueDate: $due,
            addressLines: array_values((array) ($data['address_lines'] ?? [])),
            periodId: (int) ($data['period_id'] ?? $data['periodId'] ?? 0),
            invoiceLines: array_values((array) ($data['invoice_lines'] ?? $data['lines'] ?? [])),
            accountingPeriodId: (int) ($data['accounting_period_id'] ?? $data['accountingPeriodId'] ?? $data['period_id'] ?? 0),
            debitAccountId: (int) ($data['debit_account_id'] ?? $data['debitAccountId'] ?? 0),
            creditAccountId: (int) ($data['credit_account_id'] ?? $data['creditAccountId'] ?? 0),
        );
    }

    /**
     * Build the Webling payload for POST /debitor.
     *
     * @return false|string
     */
    public function toWeblingPayload(): array
    {
        $dateStr = $this->date->toDateString();
        $dueStr = $this->dueDate->toDateString();

        $address = implode("\n", array_filter($this->addressLines, fn ($l) => $l !== null && $l !== ''));

        $revenue = [];
        foreach ($this->invoiceLines as $line) {
            $amount = (float) ($line['amount'] ?? 0);
            $lineTitle = (string) ($line['title'] ?? '');

            $revenue[] = [
                'properties' => [
                    'amount' => $amount,
                    'title' => $lineTitle,
                ],
                'parents' => [
                    [
                        'properties' => [
                            'date' => $dateStr,
                            'title' => $lineTitle,
                        ],
                        'parents' => [
                            $this->accountingPeriodId,
                        ],
                    ],
                ],
                'links' => [
                    'credit' => [
                        $this->creditAccountId,
                    ],
                    'debit' => [
                        $this->debitAccountId,
                    ],
                ],
            ];
        }

        return [
            'properties' => [
                'title' => $this->title,
                'date' => $dateStr,
                'duedate' => $dueStr,
                'address' => $address,
            ],
            'parents' => [
                $this->periodId,
            ],
            'links' => [
                'revenue' => $revenue,
            ],
        ];
    }
}
