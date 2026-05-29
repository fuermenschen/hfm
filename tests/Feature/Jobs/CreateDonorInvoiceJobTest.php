<?php

use App\Jobs\CreateDonorInvoice;

it('keeps donor invoice orchestration behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed donor_event_invoices aggregate for one external user + event.
    // - Fake queue/bus orchestration.
    // - Keep chain syntax expectation: Bus::chain([CreateDonorInvoiceDebitor, CreateDonorInvoiceLetter])->onConnection('sync')->dispatch().

    // Act:
    // - Dispatch CreateDonorInvoice.

    // Assert:
    // - Debitor creation job scheduled first.
    // - Letter generation job scheduled second.
    // - Chain runs on sync connection for deterministic sequential behavior.
    // - Aggregate transitions from pending to generated state.

    expect(CreateDonorInvoice::class)->toBeString();
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');

it('keeps failed-letter fallback behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed aggregate with successful debitor creation and failed letter response.

    // Act:
    // - Dispatch CreateDonorInvoice.

    // Assert:
    // - Debitor reference persists.
    // - PDF metadata stays empty.
    // - Failure is visible through aggregate state/logging.

    expect(CreateDonorInvoice::class)->toBeString();
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');
