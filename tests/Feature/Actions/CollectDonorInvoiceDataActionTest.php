<?php

use App\Actions\CollectDonorInvoiceDataAction;

it('keeps invoice line collection behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Create external user donor identity.
    // - Create donation event and athlete registrations.
    // - Seed event-scoped donations with min/max boundary cases.

    // Act:
    // - Invoke CollectDonorInvoiceDataAction for event-scoped donor invoice aggregate.

    // Assert:
    // - One normalized line per donation.
    // - Min/max caps applied exactly.
    // - Athlete/partner display fields resolved from new graph.

    expect(app(CollectDonorInvoiceDataAction::class))->toBeInstanceOf(CollectDonorInvoiceDataAction::class);
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');

it('keeps multi-donation aggregation behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed two or more event-scoped donations for same external user.

    // Act:
    // - Invoke CollectDonorInvoiceDataAction.

    // Assert:
    // - Line count matches seeded donations.
    // - Totals match deterministic expected values.

    expect(app(CollectDonorInvoiceDataAction::class))->toBeInstanceOf(CollectDonorInvoiceDataAction::class);
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');
