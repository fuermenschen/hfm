<?php

use App\Jobs\CheckDonorInvoicesStatus;

it('keeps payment-status sync behavior documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed donor_event_invoices aggregates with known debitor ids.
    // - Fake Webling index responses for paid and overdue sets.
    // - Preserve filter contracts:
    //   paid => ['state' => 'paid']
    //   overdue => [['state', '!=', 'paid'], ['duedate', '<', 'TODAY()']]

    // Act:
    // - Dispatch CheckDonorInvoicesStatus synchronously.

    // Assert:
    // - Matching aggregates marked paid or overdue.
    // - Non-matching aggregates unchanged.
    // - Paid status not overwritten by overdue pass.

    expect(CheckDonorInvoicesStatus::class)->toBeString();
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');
