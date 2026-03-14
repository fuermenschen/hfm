<?php

namespace App\Actions;

use App\Models\Donor;

class CollectDonorInvoiceDataAction
{
    public function __invoke(Donor $donor): array
    {
        $donor->load('donations.athlete.partner');
        $donations = $donor->donations;

        $lines = [];

        foreach ($donations as $donation) {
            $rounds = (int) ($donation->athlete->rounds_done ?? 0);
            $perRound = (float) ($donation->amount_per_round ?? 0);
            $subtotal = $rounds * $perRound;

            $lineTotal = $this->applyMinMax($subtotal, $donation->amount_min, $donation->amount_max);

            $lines[] = [
                'athlete' => $donation->athlete->privacy_name,
                'partner' => optional($donation->athlete->partner)->name,
                'rounds' => $rounds,
                'amount_per_round' => round($perRound, 2),
                'subtotal' => round($subtotal, 2),
                'min' => $donation->amount_min !== null ? round((float) $donation->amount_min, 2) : null,
                'max' => $donation->amount_max !== null ? round((float) $donation->amount_max, 2) : null,
                'total' => round($lineTotal, 2),
            ];
        }

        return $lines;
    }

    protected function applyMinMax(float $amount, ?float $min, ?float $max): float
    {
        if ($min !== null && $amount < $min) {
            $amount = $min;
        }

        if ($max !== null && $amount > $max) {
            $amount = $max;
        }

        return $amount;
    }
}
