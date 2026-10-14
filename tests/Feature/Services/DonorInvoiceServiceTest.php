<?php

use App\Enums\DonorInvoiceStatus;
use App\Enums\DonorPaymentVerification;
use App\Models\DonorEventInvoice;
use App\Services\DonorInvoiceService;

function receiptEligibilityInvoice(array $overrides = []): DonorEventInvoice
{
    return DonorEventInvoice::factory()->make($overrides + [
        'external_user_id' => 1,
        'donation_event_id' => 1,
        'webling_debitor_id' => 4321,
        'webling_state' => 'paid',
        'webling_total_cents' => 10000,
        'webling_remaining_cents' => 0,
        'webling_received_cents' => 10000,
        'webling_payment_verification' => DonorPaymentVerification::Verified,
        'webling_payment_date' => '2026-10-05',
        'webling_synced_at' => '2026-10-08 12:00:00',
        'webling_due_date' => '2026-10-07',
        'invoice_sent_at' => '2026-09-23 12:00:00',
        'source_snapshot' => ['webling' => ['date' => '2026-09-23']],
    ]);
}

it('allows receipts for verified actual bank receipts regardless of invoice-total coverage', function (int $totalCents, int $receivedCents): void {
    $invoice = receiptEligibilityInvoice([
        'webling_total_cents' => $totalCents,
        'webling_received_cents' => $receivedCents,
    ]);

    $eligible = app(DonorInvoiceService::class)->isReceiptEligible($invoice);

    expect($eligible)->toBeTrue();
})->with([
    'ordinary full payment' => [10000, 10000],
    'excess included in bank receipt' => [10000, 11010],
    'underpayment with separate writeoff' => [10000, 7000],
    'accountant reduced invoice total' => [7000, 7000],
    'accountant increased invoice total' => [11010, 11010],
    'small positive donation' => [10000, 1],
]);

it('blocks receipts when required settlement evidence is absent or inconsistent', function (array $overrides): void {
    $invoice = receiptEligibilityInvoice($overrides);

    $eligible = app(DonorInvoiceService::class)->isReceiptEligible($invoice);

    expect($eligible)->toBeFalse();
})->with([
    'remote invoice missing' => [['webling_debitor_id' => null]],
    'invalid remote identity' => [['webling_debitor_id' => 0]],
    'remote invoice deleted' => [['remote_deleted_at' => '2026-10-09 12:00:00']],
    'missing raw state' => [['webling_state' => null]],
    'open invoice' => [['webling_state' => 'open']],
    'partially paid invoice' => [['webling_state' => 'partially paid']],
    'written-off invoice' => [['webling_state' => 'writeoff']],
    'unknown raw state' => [['webling_state' => 'unknown']],
    'outstanding balance' => [['webling_remaining_cents' => 100]],
    'negative remaining balance' => [['webling_remaining_cents' => -100]],
    'missing remaining balance' => [['webling_remaining_cents' => null]],
    'unclear payment' => [['webling_payment_verification' => DonorPaymentVerification::Unclear]],
    'unverified payment' => [['webling_payment_verification' => DonorPaymentVerification::Unverified]],
    'missing verification' => [['webling_payment_verification' => null]],
    'missing received amount' => [['webling_received_cents' => null]],
    'zero received amount' => [['webling_received_cents' => 0]],
    'negative received amount' => [['webling_received_cents' => -100]],
    'missing actual payment date despite invoice and sync dates' => [['webling_payment_date' => null]],
    'no successful refresh' => [['webling_synced_at' => null]],
]);

it('keeps raw paid status distinct from receipt eligibility for unclear payments', function (): void {
    $invoice = receiptEligibilityInvoice(['webling_payment_verification' => DonorPaymentVerification::Unclear]);
    $service = app(DonorInvoiceService::class);

    expect($service->status($invoice))->toBe(DonorInvoiceStatus::Paid)
        ->and($service->isReceiptEligible($invoice))->toBeFalse();
});

it('does not require an invoice PDF, email, or eager-loaded event to establish payment eligibility', function (): void {
    $invoice = receiptEligibilityInvoice();
    $before = $invoice->getAttributes();

    $eligible = app(DonorInvoiceService::class)->isReceiptEligible($invoice);

    expect($eligible)->toBeTrue()
        ->and($invoice->getAttributes())->toBe($before)
        ->and($invoice->getRelations())->toBe([]);
});
