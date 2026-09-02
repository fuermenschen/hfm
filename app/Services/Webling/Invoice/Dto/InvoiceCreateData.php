<?php

declare(strict_types=1);

namespace App\Services\Webling\Invoice\Dto;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

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
     * @param  array<int,array{amount_cents:int, title:string}>  $invoiceLines
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
        public ?string $comment = null,
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $date = $data['date'] instanceof Carbon ? $data['date'] : Date::parse((string) ($data['date'] ?? now()->toDateString()));
        $due = (isset($data['duedate']) && $data['duedate'] instanceof Carbon)
            ? $data['duedate']
            : Date::parse((string) ($data['duedate'] ?? $data['due_date'] ?? $date->copy()->addDays(30)->toDateString()));

        // Default title if empty
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = 'Rechnung'; // default title
        }

        $invoiceLines = [];
        foreach ((array) ($data['invoice_lines'] ?? $data['lines'] ?? []) as $line) {
            if (! is_array($line)) {
                continue;
            }

            $invoiceLines[] = [
                'amount_cents' => isset($line['amount_cents'])
                    ? (int) $line['amount_cents']
                    : self::decimalToCents($line['amount'] ?? 0),
                'title' => (string) ($line['title'] ?? ''),
            ];
        }

        return new self(
            title: $title,
            date: $date,
            dueDate: $due,
            addressLines: array_values((array) ($data['address_lines'] ?? [])),
            periodId: (int) ($data['period_id'] ?? $data['periodId'] ?? 0),
            invoiceLines: $invoiceLines,
            accountingPeriodId: (int) ($data['accounting_period_id'] ?? $data['accountingPeriodId'] ?? $data['period_id'] ?? 0),
            debitAccountId: (int) ($data['debit_account_id'] ?? $data['debitAccountId'] ?? 0),
            creditAccountId: (int) ($data['credit_account_id'] ?? $data['creditAccountId'] ?? 0),
            comment: isset($data['comment']) ? (string) $data['comment'] : null,
        );
    }

    /**
     * Build the Webling payload for POST /debitor.
     *
     * @return array<string, mixed>
     */
    public function toWeblingPayload(): array
    {
        $dateStr = $this->date->toDateString();
        $dueStr = $this->dueDate->toDateString();

        $address = implode("\n", array_filter($this->addressLines, fn (string $l): bool => $l !== ''));

        $revenue = [];
        foreach ($this->invoiceLines as $line) {
            $amountCents = (int) $line['amount_cents'];
            $amount = (float) ($amountCents / 100);
            $lineTitle = (string) $line['title'];

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

        $properties = [
            'title' => $this->title,
            'date' => $dateStr,
            'duedate' => $dueStr,
            'address' => $address,
        ];

        if ($this->comment !== null) {
            $properties['comment'] = $this->comment;
        }

        return [
            'properties' => $properties,
            'parents' => [
                $this->periodId,
            ],
            'links' => [
                'revenue' => $revenue,
            ],
        ];
    }

    protected static function decimalToCents(mixed $amount): int
    {
        return is_numeric($amount) ? (int) round((float) $amount * 100) : 0;
    }
}
