<?php

use App\Services\Webling\Invoice\Dto\InvoiceCreateData;
use Carbon\Carbon;

it('applies default title and due date when missing', function () {
    $today = Carbon::parse('2025-09-10');
    Carbon::setTestNow($today);

    $dto = InvoiceCreateData::fromArray([
        // title omitted to trigger default
        'date' => $today,
        'address_lines' => ['Max Mustermann', 'Musterweg 1', '8000 Zürich'],
        'period_id' => 0,
        'invoice_lines' => [
            ['amount_cents' => 1000, 'title' => 'Test'],
        ],
        'accounting_period_id' => 0,
        'debit_account_id' => 0,
        'credit_account_id' => 0,
    ]);

    expect($dto->title)->toBe('Rechnung')
        ->and($dto->dueDate->toDateString())->toBe('2025-10-10');

    $payload = $dto->toWeblingPayload();
    expect($payload['properties']['title'])->toBe('Rechnung')
        ->and($payload['properties']['date'])->toBe('2025-09-10')
        ->and($payload['properties']['duedate'])->toBe('2025-10-10');
});

it('converts integer cents to Webling currency amounts and writes comments', function (): void {
    $dto = new InvoiceCreateData(
        title: 'Invoice',
        date: Carbon::parse('2025-09-10'),
        dueDate: Carbon::parse('2025-09-24'),
        addressLines: ['Donor'],
        periodId: 10,
        invoiceLines: [['amount_cents' => 999, 'title' => 'Test']],
        accountingPeriodId: 20,
        debitAccountId: 30,
        creditAccountId: 40,
        comment: 'HFM-DONOR-INVOICE:99',
    );

    $payload = $dto->toWeblingPayload();

    expect($payload['properties']['comment'])->toBe('HFM-DONOR-INVOICE:99')
        ->and($payload['links']['revenue'][0]['properties']['amount'])->toBe(9.99);
});
