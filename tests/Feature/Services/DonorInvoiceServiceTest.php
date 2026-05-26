<?php

use App\Services\DonorInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps create-invoice behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Create external user with donor role semantics.
    // - Create donation_event scope.
    // - Create donor_event_invoice aggregate row (new model from GH-134).
    // - Fake Webling invoice + letter generation services.

    // Act:
    // - Call DonorInvoiceService::createInvoice() on event-scoped identity.

    // Assert:
    // - Debitor creation requested exactly once.
    // - PDF generation requested with event-scoped invoice line data.
    // - donor_event_invoices row stores debitor id + pdf metadata.
    // - UI response signals success.

    expect(app(DonorInvoiceService::class))->toBeInstanceOf(DonorInvoiceService::class);
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');

it('keeps send-invoice behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed event-scoped donor_event_invoice with generated PDF metadata.
    // - Fake mail transport.

    // Act:
    // - Call DonorInvoiceService::sendInvoice().

    // Assert:
    // - GenericMailMessage queued with PDF attachment from storage metadata.
    // - donor_event_invoices.sent_at persisted.
    // - Result payload marks operation as success.

    expect(app(DonorInvoiceService::class))->toBeInstanceOf(DonorInvoiceService::class);
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');

it('keeps missing-email guard behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed event-scoped invoice target without deliverable email.

    // Act:
    // - Call DonorInvoiceService::sendInvoice().

    // Assert:
    // - No mail queued.
    // - Service returns danger/warning outcome with actionable message.

    expect(app(DonorInvoiceService::class))->toBeInstanceOf(DonorInvoiceService::class);
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');

it('keeps invoice status summary behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed donor_event_invoices rows in states: paid, overdue, sent, created, not_created.

    // Act:
    // - Call DonorInvoiceService::invoiceStatusSummary().

    // Assert:
    // - Summary counts map exactly to seeded states.
    // - Counts are mutually exclusive.

    expect(app(DonorInvoiceService::class))->toBeInstanceOf(DonorInvoiceService::class);
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');
