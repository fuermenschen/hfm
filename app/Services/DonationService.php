<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\ExternalUser;
use App\Models\Partner;
use LogicException;

/**
 * Service for donation-related calculations.
 */
class DonationService
{
    public function donorPrivacyName(Donation $donation): string
    {
        $donation->loadMissing('donorExternalUser');

        return $this->donorIdentity($donation)->privacyName();
    }

    public function athletePrivacyName(Donation $donation): string
    {
        $donation->loadMissing('athleteRegistration.externalUser');

        return $this->athlete($donation)->privacyName();
    }

    /**
     * Calculate the estimated total amount for a donation based on the
     * athlete's estimated rounds, amount per round, and min/max constraints.
     */
    public function calculateEstimatedAmount(Donation $donation): float
    {
        $roundsEstimated = $this->roundsEstimated($donation);
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
        $roundsDone = $this->roundsDone($donation);
        $perRound = (float) ($donation->amount_per_round ?? 0);

        $subtotal = $roundsDone * $perRound;

        return round($this->applyMinMax($subtotal, $donation->amount_min, $donation->amount_max), 2);
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
            $partner = $this->partner($donation);
            if (! $partner instanceof Partner) {
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
            $partner = $this->partner($donation);
            if (! $partner instanceof Partner) {
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
            return $max;
        }

        return $amount;
    }

    protected function roundsEstimated(Donation $donation): int
    {
        $donation->loadMissing('athleteRegistration');

        return (int) ($this->requireAthleteRegistration($donation)->rounds_estimated ?? 0);
    }

    protected function roundsDone(Donation $donation): int
    {
        $donation->loadMissing('athleteRegistration');

        return (int) ($this->requireAthleteRegistration($donation)->rounds_done ?? 0);
    }

    protected function partner(Donation $donation): ?Partner
    {
        $donation->loadMissing('athleteRegistration.partner');

        return $this->requireAthleteRegistration($donation)->partner;
    }

    protected function athlete(Donation $donation): ExternalUser
    {
        throw_unless($donation->relationLoaded('athleteRegistration'), LogicException::class, 'Donation athlete registration must be loaded.');

        $athleteRegistration = $donation->getRelation('athleteRegistration');

        throw_unless($athleteRegistration instanceof AthleteRegistration, LogicException::class, 'Donation must reference an athlete registration.');

        throw_unless($athleteRegistration->relationLoaded('externalUser'), LogicException::class, 'Donation athlete registration must eager-load externalUser.');

        $externalUser = $athleteRegistration->getRelation('externalUser');

        throw_unless($externalUser instanceof ExternalUser, LogicException::class, 'Donation athlete registration must reference an external user.');

        return $externalUser;
    }

    protected function donorIdentity(Donation $donation): ExternalUser
    {
        if ($donation->relationLoaded('donorExternalUser')) {
            $donorExternalUser = $donation->getRelation('donorExternalUser');

            throw_unless($donorExternalUser instanceof ExternalUser, LogicException::class, 'Donation must reference a donor external user.');

            return $donorExternalUser;
        }

        $donorExternalUser = $donation->donorExternalUser()->first();

        throw_unless($donorExternalUser instanceof ExternalUser, LogicException::class, 'Donation must reference a donor external user.');

        return $donorExternalUser;
    }

    protected function requireAthleteRegistration(Donation $donation): AthleteRegistration
    {
        $athleteRegistration = $donation->athleteRegistration;

        throw_unless($athleteRegistration instanceof AthleteRegistration, LogicException::class, 'Donation must reference an athlete registration.');

        return $athleteRegistration;
    }
}
