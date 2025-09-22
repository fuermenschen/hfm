<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Partner;

/**
 * Service for donation-related calculations.
 */
class DonationService
{
    /**
     * Calculate the estimated total amount for a donation based on the
     * athlete's estimated rounds, amount per round, and min/max constraints.
     */
    public function calculateEstimatedAmount(Donation $donation): float
    {
        $roundsEstimated = (int) ($donation->athlete->rounds_estimated ?? 0);
        $perRound = (float) ($donation->amount_per_round ?? 0);

        $subtotal = $roundsEstimated * $perRound;

        return round($this->applyMinMax($subtotal, $donation->amount_min, $donation->amount_max), 2);
    }

    /**
     * Calculate the actual total amount for a donation based on the
     * athlete's completed rounds, amount per round, and min/max constraints.
     */
    public function calculateActualAmount(Donation $donation): float
    {
        $roundsDone = (int) ($donation->athlete->rounds_done ?? 0);
        $perRound = (float) ($donation->amount_per_round ?? 0);

        $subtotal = $roundsDone * $perRound;

        return round($this->applyMinMax($subtotal, $donation->amount_min, $donation->amount_max), 2);
    }

    /**
     * Calculate the estimated total amount across all donations for a given athlete.
     */
    public function calculateEstimatedTotalForAthlete(Athlete $athlete): float
    {
        if (! $athlete->relationLoaded('donations')) {
            $athlete->load('donations');
        }

        $total = 0.0;

        foreach ($athlete->donations as $donation) {
            // Ensure the donation references this athlete to avoid extra queries
            $donation->setRelation('athlete', $athlete);
            $total += $this->calculateEstimatedAmount($donation);
        }

        return round($total, 2);
    }

    /**
     * Calculate the actual total amount across all donations for a given athlete.
     */
    public function calculateActualTotalForAthlete(Athlete $athlete): float
    {
        if (! $athlete->relationLoaded('donations')) {
            $athlete->load('donations');
        }

        $total = 0.0;

        foreach ($athlete->donations as $donation) {
            // Ensure the donation references this athlete to avoid extra queries
            $donation->setRelation('athlete', $athlete);
            $total += $this->calculateActualAmount($donation);
        }

        return round($total, 2);
    }

    /**
     * Calculate the estimated total amount across all donations in the system.
     */
    public function calculateEstimatedTotal(iterable $donations): float
    {
        $total = 0.0;

        foreach ($donations as $donation) {
            $total += $this->calculateEstimatedAmount($donation);
        }

        return round($total, 2);
    }

    /**
     * Calculate the actual total amount across all donations in the system.
     */
    public function calculateActualTotal(iterable $donations): float
    {
        $total = 0.0;

        foreach ($donations as $donation) {
            $total += $this->calculateActualAmount($donation);
        }

        return round($total, 2);
    }

    /**
     * Calculate estimated totals grouped by partner id.
     *
     * @return array<int, float> key: partner_id, value: total
     */
    public function calculateEstimatedTotalPerPartner(iterable $donations): array
    {
        $totals = [];

        foreach ($donations as $donation) {
            /** @var Partner|null $partner */
            $partner = optional($donation->athlete)->partner;
            if (! $partner) {
                // Skip if athlete has no partner assigned
                continue;
            }

            $partnerId = (int) $partner->id;
            $totals[$partnerId] = ($totals[$partnerId] ?? 0.0) + $this->calculateEstimatedAmount($donation);
        }

        // Round each value to 2 decimals for consistency
        foreach ($totals as $id => $value) {
            $totals[$id] = round($value, 2);
        }

        return $totals;
    }

    /**
     * Calculate actual totals grouped by partner id.
     *
     * @return array<int, float> key: partner_id, value: total
     */
    public function calculateActualTotalPerPartner(iterable $donations): array
    {
        $totals = [];

        foreach ($donations as $donation) {
            /** @var Partner|null $partner */
            $partner = optional($donation->athlete)->partner;
            if (! $partner) {
                // Skip if athlete has no partner assigned
                continue;
            }

            $partnerId = (int) $partner->id;
            $totals[$partnerId] = ($totals[$partnerId] ?? 0.0) + $this->calculateActualAmount($donation);
        }

        // Round each value to 2 decimals for consistency
        foreach ($totals as $id => $value) {
            $totals[$id] = round($value, 2);
        }

        return $totals;
    }

    /**
     * Apply min and max caps to an amount.
     */
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
