<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use Illuminate\Support\Collection;
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
     * Calculate estimated totals for event partners, including donations from
     * registrations that selected equal split.
     *
     * @param  Collection<int, Partner>  $partners
     * @return array<int, float>
     */
    public function calculateEstimatedTotalPerEventPartner(DonationEvent $event, Collection $partners, iterable $donations): array
    {
        return $this->calculateTotalPerEventPartner($event, $partners, $donations, false);
    }

    /**
     * Calculate actual totals for event partners, including donations from
     * registrations that selected equal split.
     *
     * @param  Collection<int, Partner>  $partners
     * @return array<int, float>
     */
    public function calculateActualTotalPerEventPartner(DonationEvent $event, Collection $partners, iterable $donations): array
    {
        return $this->calculateTotalPerEventPartner($event, $partners, $donations, true);
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

    /**
     * @param  Collection<int, Partner>  $partners
     * @return array<int, float>
     */
    protected function calculateTotalPerEventPartner(DonationEvent $event, Collection $partners, iterable $donations, bool $actual): array
    {
        $donations = collect($donations);
        $totals = $partners->mapWithKeys(
            fn (Partner $partner): array => [$partner->id => 0],
        );
        $equalSplitAmount = 0;

        foreach ($donations as $donation) {
            $registration = $this->requireAthleteRegistration($donation);
            $amount = $actual
                ? $this->calculateActualAmount($donation)
                : $this->calculateEstimatedAmount($donation);
            $amountInCents = (int) round($amount * 100);

            if ($registration->partner_id === null) {
                $equalSplitAmount += $amountInCents;

                continue;
            }

            if ($totals->has($registration->partner_id)) {
                $totals[$registration->partner_id] += $amountInCents;
            }
        }

        $legacyPartnerIds = $partners->where('name', 'alle zu gleichen Teilen')->pluck('id');
        $legacyAmount = $legacyPartnerIds->sum(fn (int $partnerId): int => $totals->pull($partnerId, 0));
        $totals = $this->distributeCents($totals, $legacyAmount);

        if ($event->has_equal_split_option) {
            $totals = $this->distributeCents($totals, $equalSplitAmount);
        }

        return $totals
            ->map(fn (int $amount): float => $amount / 100)
            ->all();
    }

    /**
     * @param  Collection<int, int>  $totals
     * @return Collection<int, int>
     */
    protected function distributeCents(Collection $totals, int $amount): Collection
    {
        if ($amount <= 0 || $totals->isEmpty()) {
            return $totals;
        }

        $share = intdiv($amount, $totals->count());
        $remainder = $amount % $totals->count();

        return $totals->map(function (int $total) use (&$remainder, $share): int {
            return $total + $share + ($remainder-- > 0 ? 1 : 0);
        });
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
