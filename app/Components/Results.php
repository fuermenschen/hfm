<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\GetEventRankingsAction;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\Partner;
use App\Services\CurrentDonationEventService;
use App\Services\DonationService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Collection;
use Livewire\Component;

class Results extends Component
{
    private const int METERS_PER_ROUND = 50;

    /**
     * Public state passed to the view.
     *
     * @var array<string, mixed>
     */
    public array $totals = [];

    public function render(): ViewContract
    {
        // Recomputed on every render so wire:poll picks up new rounds and
        // donations. The dataset is a single event; caching here caused
        // same-second staleness that breaks live updates.
        $this->totals = $this->computeTotals();

        return view('components.results');
    }

    /**
     * @return array<string, mixed>
     */
    protected function computeTotals(): array
    {
        $event = resolve(CurrentDonationEventService::class)->current();

        if (! $event instanceof DonationEvent) {
            return ['has_event' => false];
        }

        $registrations = AthleteRegistration::query()
            ->whereBelongsTo($event)
            // Excludes soft-deleted external users; their rows would 500 the
            // ranking (null privacy_name) and skew the totals.
            ->whereHas('externalUser')
            ->with(['partner:id,name', 'eventGroup:id,name', 'externalUser:id,first_name,last_name', 'donations'])
            ->get();

        // Backfill the inverse relation so DonationService never queries
        // per donation (public page, renders every 15 seconds).
        $donations = $registrations->flatMap(function (AthleteRegistration $registration): Collection {
            $registration->donations->each(
                fn (Donation $donation): Donation => $donation->setRelation('athleteRegistration', $registration),
            );

            return $registration->donations;
        });
        $donationService = resolve(DonationService::class);

        $roundsTotal = (int) $registrations->sum('rounds_done');
        $rankings = resolve(GetEventRankingsAction::class)($registrations);

        return [
            'has_event' => true,
            'event_title' => $event->title,
            'athletes' => $registrations->count(),
            'donors' => $donations->pluck('donor_external_user_id')->filter()->unique()->count(),
            'rounds' => $roundsTotal,
            'elevation_m' => $roundsTotal * self::METERS_PER_ROUND,
            'donations_total' => $donationService->calculateActualTotal($donations),
            'per_partner' => $this->perPartnerAmounts($event, $registrations, $donations, $donationService),
            'athlete_ranking' => $rankings['athletes'],
            'group_ranking' => $rankings['groups'],
        ];
    }

    /**
     * Actual donation amounts per partner of the current event. Donations of
     * athletes without an own partner are distributed evenly when the event
     * has the equal-split option, and the legacy 'alle zu gleichen Teilen'
     * partner splits evenly among all other partners.
     *
     * @param  Collection<int, AthleteRegistration>  $registrations
     * @param  Collection<int, Donation>  $donations
     * @return array<string, float>
     */
    protected function perPartnerAmounts(
        DonationEvent $event,
        Collection $registrations,
        Collection $donations,
        DonationService $donationService,
    ): array {
        $partners = $registrations
            ->pluck('partner')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn (Partner $partner): array => [$partner->id => $partner->name]);

        $perPartner = collect($donationService->calculateActualTotalPerPartner($donations))
            ->mapWithKeys(fn (float $amount, int $partnerId): array => [($partners[$partnerId] ?? ('Partner #'.$partnerId)) => $amount]);

        $perPartner = $this->applyLegacyEqualShareRule($perPartner);
        $perPartner = $this->applyEqualSplitOption($event, $registrations, $perPartner, $donationService);

        return $perPartner->sortKeys()->all();
    }

    /**
     * @param  Collection<string, float>  $perPartner
     * @return Collection<string, float>
     */
    protected function applyLegacyEqualShareRule(Collection $perPartner): Collection
    {
        $equalShareName = 'alle zu gleichen Teilen';

        if (! $perPartner->has($equalShareName)) {
            return $perPartner;
        }

        $amountToSplit = (float) $perPartner->pull($equalShareName);
        $targetCount = $perPartner->count();

        if ($amountToSplit <= 0.0 || $targetCount === 0) {
            return $targetCount === 0 ? collect() : $perPartner;
        }

        $share = $amountToSplit / $targetCount;

        return $perPartner->map(fn (float $amount): float => $amount + $share);
    }

    /**
     * @param  Collection<int, AthleteRegistration>  $registrations
     * @param  Collection<string, float>  $perPartner
     * @return Collection<string, float>
     */
    protected function applyEqualSplitOption(
        DonationEvent $event,
        Collection $registrations,
        Collection $perPartner,
        DonationService $donationService,
    ): Collection {
        if (! (bool) $event->has_equal_split_option || $perPartner->isEmpty()) {
            return $perPartner;
        }

        $equalSplitDonations = $registrations->whereNull('partner_id')->flatMap->donations;
        $equalSplitAmount = $donationService->calculateActualTotal($equalSplitDonations);

        if ($equalSplitAmount <= 0.0) {
            return $perPartner;
        }

        $share = $equalSplitAmount / $perPartner->count();

        return $perPartner->map(fn (float $amount): float => $amount + $share);
    }
}
