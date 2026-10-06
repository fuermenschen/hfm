<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\GetEventRankingsAction;
use App\Enums\PublicEventLifecycle;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\Partner;
use App\Services\CurrentDonationEventService;
use App\Services\DonationService;
use App\Services\PublicDonationEventService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Results extends Component
{
    private const int METERS_PER_ROUND = 50;

    #[Locked]
    public ?int $donationEventId = null;

    /**
     * Public state passed to the view.
     *
     * @var array<string, mixed>
     */
    public array $totals = [];

    public function mount(?DonationEvent $donationEvent = null): void
    {
        $donationEvent ??= resolve(CurrentDonationEventService::class)->current()
            ?? resolve(PublicDonationEventService::class)->results();
        $this->donationEventId = $donationEvent?->id;
    }

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
        if ($this->donationEventId === null) {
            return ['has_event' => false];
        }

        $event = DonationEvent::query()
            ->where('is_published', true)
            ->findOrFail($this->donationEventId);
        $lifecycle = resolve(PublicDonationEventService::class)->lifecycle($event);
        $eventData = [
            'has_event' => true,
            'event_title' => $event->title,
            'event_date' => $event->starts_at->translatedFormat('j. F Y'),
            'event_datetime' => $event->starts_at->toDateString(),
            'lifecycle' => $lifecycle,
        ];

        if ($lifecycle === PublicEventLifecycle::Upcoming) {
            return $eventData;
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
            ...$eventData,
            'athletes' => $registrations->count(),
            'donors' => $donations->pluck('donor_external_user_id')->filter()->unique()->count(),
            'rounds' => $roundsTotal,
            'elevation_m' => $roundsTotal * self::METERS_PER_ROUND,
            'donations_total' => $donationService->calculateActualTotal($donations),
            'per_partner' => $this->perPartnerAmounts($event, $donations, $donationService),
            'rankings' => $rankings,
        ];
    }

    /**
     * Actual donation amounts per published event partner. Equal-split and
     * legacy donations are distributed across every active event partner.
     *
     * @param  Collection<int, Donation>  $donations
     * @return list<array{name: string, amount: float}>
     */
    protected function perPartnerAmounts(
        DonationEvent $event,
        Collection $donations,
        DonationService $donationService,
    ): array {
        $partners = $event->partners()
            ->wherePivot('is_published', true)
            ->orderByPivot('sort_order')
            ->orderBy('name')
            ->get(['partners.id', 'partners.name']);

        $perPartner = $donationService->calculateActualTotalPerEventPartner($event, $partners, $donations);

        return $partners
            ->reject(fn (Partner $partner): bool => $partner->name === 'alle zu gleichen Teilen')
            ->map(fn (Partner $partner): array => ['name' => $partner->name, 'amount' => $perPartner[$partner->id]])
            ->all();
    }
}
