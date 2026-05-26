<?php

use App\Jobs\CheckDonorInvoicesStatus;

it('keeps failure-propagation behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Fake Webling service exception during paid index fetch.

    // Act:
    // - Dispatch CheckDonorInvoicesStatus synchronously.

    // Assert:
    // - Exception bubbles to caller for UI/operator visibility.
    // - No partial status writes are persisted.

    expect(CheckDonorInvoicesStatus::class)->toBeString();
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');
