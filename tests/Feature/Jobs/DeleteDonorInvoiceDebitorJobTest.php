<?php

use App\Jobs\DeleteDonorInvoiceDebitor;

it('keeps cleanup-without-debitor behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed aggregate with optional local PDF metadata and no debitor id.

    // Act:
    // - Run DeleteDonorInvoiceDebitor.

    // Assert:
    // - Local PDF cleanup still occurs.
    // - Aggregate metadata no longer references removed file.

    expect(DeleteDonorInvoiceDebitor::class)->toBeString();
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');

it('keeps successful remote-delete cleanup behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed aggregate with debitor id + PDF metadata.
    // - Fake Webling delete response 204.
    // - Keep deletion semantics: 204 and 404 both treated as idempotent success.

    // Act:
    // - Run DeleteDonorInvoiceDebitor.

    // Assert:
    // - Debitor references cleared.
    // - PDF metadata cleared.
    // - Local file removed.

    expect(DeleteDonorInvoiceDebitor::class)->toBeString();
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');

it('keeps failed remote-delete behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed aggregate with debitor id.
    // - Fake Webling delete response non-204/non-404.

    // Act:
    // - Run DeleteDonorInvoiceDebitor.

    // Assert:
    // - Exception is raised.
    // - Aggregate retains debitor id for retry.
    // - Warning log keeps response status context for debugging.

    expect(DeleteDonorInvoiceDebitor::class)->toBeString();
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');

it('keeps 404-idempotent-delete behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed aggregate with debitor id.
    // - Fake Webling delete response 404.

    // Act:
    // - Run DeleteDonorInvoiceDebitor.

    // Assert:
    // - Operation treated as success.
    // - Debitor references cleared.

    expect(DeleteDonorInvoiceDebitor::class)->toBeString();
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');
