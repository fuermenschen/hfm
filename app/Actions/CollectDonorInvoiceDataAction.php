<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Donation;
use App\Models\DonorEventInvoice;

class CollectDonorInvoiceDataAction
{
    /**
     * @return list<array{athlete:string,partner:?string,rounds:int,amount_per_round_cents:int,subtotal_cents:int,min_cents:?int,max_cents:?int,total_cents:int}>
     */
    public function __invoke(DonorEventInvoice $invoice): array
    {
        $donations = Donation::query()
            ->where('donor_external_user_id', $invoice->external_user_id)
            ->whereHas('athleteRegistration', fn ($query) => $query->where('donation_event_id', $invoice->donation_event_id))
            ->with([
                'athleteRegistration.externalUser:id,first_name,last_name',
                'athleteRegistration.partner:id,name',
            ])
            ->orderBy('id')
            ->get();

        return $donations->map(function (Donation $donation): array {
            $registration = $donation->athleteRegistration;
            $rounds = $registration->rounds_done ?? 0;
            $amountPerRoundCents = $this->toCents($donation->amount_per_round);
            $subtotalCents = $rounds * $amountPerRoundCents;
            $minCents = $donation->amount_min === null ? null : $this->toCents($donation->amount_min);
            $maxCents = $donation->amount_max === null ? null : $this->toCents($donation->amount_max);
            $totalCents = $this->applyMinMax($subtotalCents, $minCents, $maxCents);

            return [
                'athlete' => $registration->externalUser->privacy_name,
                'partner' => $registration->partner?->name,
                'rounds' => $rounds,
                'amount_per_round_cents' => $amountPerRoundCents,
                'subtotal_cents' => $subtotalCents,
                'min_cents' => $minCents,
                'max_cents' => $maxCents,
                'total_cents' => $totalCents,
            ];
        })->all();
    }

    protected function applyMinMax(int $amountCents, ?int $minCents, ?int $maxCents): int
    {
        if ($minCents !== null && $amountCents < $minCents) {
            $amountCents = $minCents;
        }

        if ($maxCents !== null && $amountCents > $maxCents) {
            return $maxCents;
        }

        return $amountCents;
    }

    protected function toCents(float|int $amount): int
    {
        return (int) round($amount * 100);
    }
}
