<?php

use App\Jobs\CreateDonorInvoiceLetter;

it('keeps QR debtor payload mapping documented for donor_event_invoices rebuild', function (): void {
    // Arrange:
    // - Seed donor_event_invoices aggregate with debtor address fields.
    // - Fake LetterService and capture configured QR payload.
    // - Preserve QR key expectations: debtorName, debtorStreet, debtorBuildingNumber([]), debtorPostalCode, debtorCity.

    // Act:
    // - Run CreateDonorInvoiceLetter.

    // Assert:
    // - Debtor name/street/postal/city values map into QR payload.
    // - Additional info text uses event content fallback rules.
    // - Persist PDF metadata keys: disk/path/size when response is successful.

    expect(CreateDonorInvoiceLetter::class)->toBeString();
})->skip('TODO(refactor-external-user): rewrite on donor_event_invoices (GH-134), separate from association donation invoices.');
