<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ExternalUser;

class CollectDonorInvoiceDataAction
{
    /**
     * TODO(refactor-external-user):
     * Move invoice-line collection to event-scoped donor_event_invoices flow (GH-134).
     *
     * Important: donor invoices and association donation invoices stay separate.
     *
     * Keep detailed line semantics from legacy flow:
     * - rounds = athlete.rounds_done fallback 0
     * - subtotal = rounds * amount_per_round
     * - total = apply min/max clamps
     * - include athlete privacy name + partner name in each line
     * - include min/max nullable fields and rounded amounts for rendering
     *
     * Pseudo code for future implementation:
     * - Resolve external user + donation event context.
     * - Load event-scoped donations via athlete_registrations.
     * - Apply min/max logic per donation line.
     * - Return normalized line data arrays for Webling and letter rendering.
     *
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(ExternalUser $externalUser): array
    {
        return [];
    }
}
