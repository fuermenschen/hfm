<?php

use App\Services\Webling\Dto\InvoiceCreateData;
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
            ['amount' => 10.0, 'title' => 'Test'],
        ],
        'accounting_period_id' => 0,
        'debit_account_id' => 0,
        'credit_account_id' => 0,
    ]);

    expect($dto->title)->toBe('Spendenquittung 2025');
    expect($dto->dueDate->toDateString())->toBe('2025-10-10');

    $payload = $dto->toWeblingPayload();
    expect($payload['properties']['title'])->toBe('Spendenquittung 2025');
    expect($payload['properties']['date'])->toBe('2025-09-10');
    expect($payload['properties']['duedate'])->toBe('2025-10-10');
});
