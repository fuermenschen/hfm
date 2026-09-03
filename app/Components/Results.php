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
            'rankings' => $rankings,
        ];
    }

    /**
     * Actual donation amounts per published event partner. Equal-split and
     * legacy donations are distributed across every active event partner.
     *
     * @param  Collection<int, AthleteRegistration>  $registrations
     * @param  Collection<int, Donation>  $donations
     * @return list<array{name: string, amount: float}>
     */
    protected function perPartnerAmounts(
        DonationEvent $event,
        Collection $registrations,
        Collection $donations,
        DonationService $donationService,
    ): array {
        $partners = $event->partners()
            ->wherePivot('is_published', true)
            ->orderByPivot('sort_order')
            ->orderBy('name')
            ->get(['partners.id', 'partners.name']);

        $actualAmounts = $donationService->calculateActualTotalPerPartner($donations);
        $perPartner = $partners->mapWithKeys(
            fn (Partner $partner): array => [(int) $partner->id => (int) round(($actualAmounts[$partner->id] ?? 0) * 100)],
        );

        $perPartner = $this->applyLegacyEqualShareRule($partners, $perPartner);
        $perPartner = $this->applyEqualSplitOption($event, $registrations, $partners, $perPartner, $donationService);

        return $partners
            ->reject(fn (Partner $partner): bool => $partner->name === 'alle zu gleichen Teilen')
            ->map(fn (Partner $partner): array => ['name' => $partner->name, 'amount' => $perPartner[$partner->id] / 100])
            ->all();
    }

    /**
     * @param  Collection<int, Partner>  $partners
     * @param  Collection<int, int>  $perPartner
     * @return Collection<int, int>
     */
    protected function applyLegacyEqualShareRule(Collection $partners, Collection $perPartner): Collection
    {
        $equalShareName = 'alle zu gleichen Teilen';
        $legacyPartnerIds = $partners->where('name', $equalShareName)->pluck('id');
        $amountToSplit = $legacyPartnerIds->sum(fn (int $partnerId): int => $perPartner->pull($partnerId, 0));

        return $this->distribute($perPartner, $amountToSplit);
    }

    /**
     * @param  Collection<int, AthleteRegistration>  $registrations
     * @param  Collection<int, Partner>  $partners
     * @param  Collection<int, int>  $perPartner
     * @return Collection<int, int>
     */
    protected function applyEqualSplitOption(
        DonationEvent $event,
        Collection $registrations,
        Collection $partners,
        Collection $perPartner,
        DonationService $donationService,
    ): Collection {
        if (! (bool) $event->has_equal_split_option || $partners->isEmpty()) {
            return $perPartner;
        }

        $equalSplitDonations = $registrations->whereNull('partner_id')->flatMap->donations;
        $equalSplitAmount = $donationService->calculateActualTotal($equalSplitDonations);

        return $this->distribute($perPartner, (int) round($equalSplitAmount * 100));
    }

    /**
     * @param  Collection<int, int>  $perPartner
     * @return Collection<int, int>
     */
    protected function distribute(Collection $perPartner, int $amount): Collection
    {
        if ($amount <= 0 || $perPartner->isEmpty()) {
            return $perPartner;
        }

        $share = intdiv($amount, $perPartner->count());
        $remainder = $amount % $perPartner->count();

        return $perPartner->map(function (int $currentAmount, int $partnerId) use (&$remainder, $share): int {
            $extraCent = $remainder-- > 0 ? 1 : 0;

            return $currentAmount + $share + $extraCent;
        });
    }
}
