<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Services\AthleteService;
use App\Services\DonationService;
use App\Services\DonorService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetDashboardDataAction
{
    public function __construct(
        public AthleteService $athleteService,
        public DonationService $donationService,
        public DonorService $donorService,
    ) {}

    /**
     * @return array{
     *     greeting: string,
     *     events: Collection<int, DonationEvent>,
     *     selectedEventSlug: ?string,
     *     partners: Collection<int, Partner>,
     *     athleteCount: int,
     *     donorCount: int,
     *     donationCount: int,
     *     eventGroupCount: int,
     *     verifiedAthleteCount: int,
     *     verifiedDonationCount: int,
     *     meanNumberOfDonations: float,
     *     meanNumberOfRounds: float|int|null,
     *     totalEstimatedRounds: int,
     *     totalActualRounds: int,
     *     meanNumberOfDonationsDonor: float,
     *     meanDonationAmount: float,
     *     expectedDonationAmount: float,
     *     actualTotalAmount: float,
     *     estimatedAmounts: array<int, float>,
     *     actualAmounts: array<int, float>,
     *     mostRecentActivities: array<int, array<string, mixed>>,
     *     chartEvents: array<int, array{field: string, slug: string, label: string, colorIndex: int}>,
     *     chartData: array{registrations: array<int, array<string, float|int>>, donations: array<int, array<string, float|int>>, expectedAmount: array<int, array<string, float|int>>},
     *     chartTickValues: array<int, int>,
     *     chartTodayMarkers: array<int, array{slug: string, day: int}>,
     * }
     */
    public function __invoke(?DonationEvent $event = null): array
    {
        $greeting = $this->greeting();
        $events = DonationEvent::query()
            ->latest('starts_at')
            ->get(['id', 'title', 'slug', 'timezone', 'starts_at', 'is_published']);
        $selectedEventSlug = $event?->slug;

        $partners = $event instanceof DonationEvent
            ? $event->partners()->select(['partners.id', 'partners.name'])->orderBy('name')->get()
            : Partner::query()->select(['id', 'name'])->orderBy('name')->get();

        $athleteCount = $event instanceof DonationEvent
            ? (int) $this->athleteService->forEvent($event)->count()
            : $this->athleteService->count();
        $donorCount = $event instanceof DonationEvent
            ? (int) $this->donorService->forEvent($event)->count()
            : $this->donorService->count();
        $donationCount = (int) $this->donationsQuery($event)->count();
        $eventGroupCount = (int) EventGroup::query()
            ->when($event instanceof DonationEvent, fn (Builder $query): Builder => $query->where('donation_event_id', $event->id))
            ->count();

        $verifiedAthleteCount = $event instanceof DonationEvent
            ? (int) $this->registrationsQuery($event)
                ->where('verified', true)
                ->distinct()
                ->count('external_user_id')
            : $this->athleteService->verifiedCount();
        $verifiedDonationCount = (int) $this->donationsQuery($event)->where('verified', true)->count();

        $meanNumberOfDonations = $athleteCount > 0 ? (float) ($donationCount / $athleteCount) : 0.0;
        $meanNumberOfRounds = $this->meanNumberOfRounds($event);
        $totalEstimatedRounds = $this->totalEstimatedRounds($event);
        $totalActualRounds = $this->totalActualRounds($event);
        $meanNumberOfDonationsDonor = $donorCount > 0 ? (float) ($donationCount / $donorCount) : 0.0;
        $meanDonationAmount = (float) ($this->donationsQuery($event)->avg('amount_per_round') ?? 0.0);

        $donations = $this->donationsQuery($event)
            ->with(['athleteRegistration.partner', 'athleteRegistration.externalUser'])
            ->get();

        $expectedDonationAmount = $this->donationService->calculateEstimatedTotal($donations);
        $actualTotalAmount = $this->donationService->calculateActualTotal($donations);

        $estimatedAmounts = $event instanceof DonationEvent
            ? $this->donationService->calculateEstimatedTotalPerEventPartner($event, $partners, $donations)
            : $this->donationService->calculateEstimatedTotalPerPartner($donations);
        $actualAmounts = $event instanceof DonationEvent
            ? $this->donationService->calculateActualTotalPerEventPartner($event, $partners, $donations)
            : $this->donationService->calculateActualTotalPerPartner($donations);
        $chartDonationEvents = $event instanceof DonationEvent ? collect([$event]) : $events;
        $chartEvents = $this->chartEvents($chartDonationEvents);
        ['chartData' => $chartData, 'chartTickValues' => $chartTickValues] = $this->buildChartData($chartDonationEvents, $chartEvents, $donations);
        $chartTodayMarkers = [];

        foreach ($chartDonationEvents as $chartDonationEvent) {
            $day = $this->relativeDay(now($chartDonationEvent->timezone), $chartDonationEvent);
            if ($day >= -60 && $day < 0) {
                $chartTodayMarkers[] = ['slug' => $chartDonationEvent->slug, 'day' => $day];
            }
        }

        $chartTodayValues = array_values(array_unique(array_column($chartTodayMarkers, 'day')));
        sort($chartTodayValues);
        $chartTickValues = array_values(array_unique([...$chartTickValues, ...$chartTodayValues]));
        sort($chartTickValues);

        $mostRecentActivities = $this->buildRecentActivities($event);

        return compact(
            'greeting',
            'events',
            'selectedEventSlug',
            'partners',
            'athleteCount',
            'donorCount',
            'donationCount',
            'eventGroupCount',
            'verifiedAthleteCount',
            'verifiedDonationCount',
            'meanNumberOfDonations',
            'meanNumberOfRounds',
            'totalEstimatedRounds',
            'totalActualRounds',
            'meanNumberOfDonationsDonor',
            'meanDonationAmount',
            'expectedDonationAmount',
            'actualTotalAmount',
            'estimatedAmounts',
            'actualAmounts',
            'mostRecentActivities',
            'chartEvents',
            'chartData',
            'chartTickValues',
            'chartTodayMarkers',
        );
    }

    /**
     * @param  Collection<int, DonationEvent>  $events
     * @return array<int, array{field: string, slug: string, label: string, colorIndex: int}>
     */
    protected function chartEvents(Collection $events): array
    {
        return $events
            ->values()
            ->map(fn (DonationEvent $chartEvent, int $index): array => [
                'field' => 'event_'.$chartEvent->id,
                'slug' => $chartEvent->slug,
                'label' => $chartEvent->title.' ('.$chartEvent->slug.')'.($chartEvent->is_published ? '' : ' - NICHT VERÖFFENTLICHT'),
                'colorIndex' => $index % 6,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, DonationEvent>  $events
     * @param  array<int, array{field: string, slug: string, label: string, colorIndex: int}>  $chartEvents
     * @param  Collection<int, Donation>  $donations
     * @return array{chartData: array{registrations: array<int, array<string, float|int>>, donations: array<int, array<string, float|int>>, expectedAmount: array<int, array<string, float|int>>}, chartTickValues: array<int, int>}
     */
    protected function buildChartData(Collection $events, array $chartEvents, Collection $donations): array
    {
        if ($chartEvents === []) {
            return [
                'chartData' => ['registrations' => [], 'donations' => [], 'expectedAmount' => []],
                'chartTickValues' => [],
            ];
        }

        $eventsById = $events->keyBy('id');
        $deltas = ['registrations' => [], 'donations' => [], 'expectedAmount' => []];
        $days = [];

        foreach (AthleteRegistration::query()
            ->whereIn('donation_event_id', $eventsById->keys())
            ->get(['donation_event_id', 'created_at']) as $registration) {
            $chartEvent = $eventsById->get($registration->donation_event_id);
            if (! $chartEvent instanceof DonationEvent) {
                continue;
            }

            if (! $registration->created_at instanceof Carbon) {
                continue;
            }

            $day = $this->relativeDay($registration->created_at, $chartEvent);
            if ($day > 14) {
                continue;
            }

            $day = max($day, -60);
            $field = 'event_'.$chartEvent->id;
            $deltas['registrations'][$field][$day] = ($deltas['registrations'][$field][$day] ?? 0) + 1;
            $days[] = $day;
        }

        foreach ($donations as $donation) {
            $registration = $donation->athleteRegistration;
            $chartEvent = $eventsById->get($registration->donation_event_id);
            if (! $chartEvent instanceof DonationEvent) {
                continue;
            }

            if (! $donation->created_at instanceof Carbon) {
                continue;
            }

            $day = $this->relativeDay($donation->created_at, $chartEvent);
            if ($day > 14) {
                continue;
            }

            $day = max($day, -60);
            $field = 'event_'.$chartEvent->id;
            $deltas['donations'][$field][$day] = ($deltas['donations'][$field][$day] ?? 0) + 1;
            $deltas['expectedAmount'][$field][$day] = ($deltas['expectedAmount'][$field][$day] ?? 0.0) + $this->donationService->calculateEstimatedAmount($donation);
            $days[] = $day;
        }

        if ($days === []) {
            return [
                'chartData' => ['registrations' => [], 'donations' => [], 'expectedAmount' => []],
                'chartTickValues' => [],
            ];
        }

        $minimumDay = -60;
        $maximumDay = 14;
        $chartData = ['registrations' => [], 'donations' => [], 'expectedAmount' => []];

        foreach (array_keys($chartData) as $metric) {
            $totals = array_fill_keys(array_column($chartEvents, 'field'), 0.0);

            for ($day = $minimumDay; $day <= $maximumDay; $day++) {
                $point = ['day' => $day];

                foreach ($chartEvents as $chartEvent) {
                    $field = $chartEvent['field'];
                    $totals[$field] += $deltas[$metric][$field][$day] ?? 0.0;
                    $point[$field] = $metric === 'expectedAmount' ? round($totals[$field], 2) : (int) $totals[$field];
                }

                $chartData[$metric][] = $point;
            }

        }

        $chartTickValues = [-60, -30, 0, 14];

        return compact('chartData', 'chartTickValues');
    }

    protected function relativeDay(Carbon $createdAt, DonationEvent $event): int
    {
        return (int) $event->starts_at
            ->copy()
            ->startOfDay()
            ->diffInDays($createdAt->copy()->setTimezone($event->timezone)->startOfDay(), false);
    }

    protected function greeting(): string
    {
        $time = (int) date('H');
        $greeting = 'Hallo ';

        if ($time >= 17) {
            $greeting = 'Guten Abend ';
        } elseif ($time >= 12) {
            $greeting = 'Grüezi ';
        } elseif ($time >= 4) {
            $greeting = 'Guten Morgen ';
        }

        return $greeting;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildRecentActivities(?DonationEvent $event): array
    {
        $sevenDaysAgo = now()->subDays(7);

        $recentExternalUsers = ExternalUser::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->when($event instanceof DonationEvent, function (Builder $query) use ($event): void {
                $query->where(function (Builder $participants) use ($event): void {
                    $participants
                        ->whereHas('athleteRegistrations', fn (Builder $registrations): Builder => $registrations->where('donation_event_id', $event->id))
                        ->orWhereHas('donationsAsDonor.athleteRegistration', fn (Builder $registration): Builder => $registration->where('donation_event_id', $event->id));
                });
            })
            ->latest()
            ->limit(30)
            ->get(['id', 'first_name', 'last_name', 'created_at']);

        $recentAthleteRegistrations = $this->registrationsQuery($event)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->with('externalUser:id,first_name,last_name')
            ->latest()
            ->limit(30)
            ->get(['id', 'external_user_id', 'created_at']);

        $recentDonations = $this->donationsQuery($event)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->with([
                'donorExternalUser:id,first_name,last_name',
                'athleteRegistration.externalUser:id,first_name,last_name',
            ])
            ->latest()
            ->limit(30)
            ->get(['id', 'donor_external_user_id', 'athlete_registration_id', 'created_at']);

        $activities = [];

        foreach ($recentExternalUsers as $externalUser) {
            $activities[] = [
                'type' => 'external_user',
                'name' => $externalUser->privacyName(),
                'created_at' => $externalUser->created_at,
            ];
        }

        foreach ($recentAthleteRegistrations as $registration) {
            $activities[] = [
                'type' => 'athlete_registration',
                'name' => $registration->externalUser->privacyName(),
                'created_at' => $registration->created_at,
            ];
        }

        foreach ($recentDonations as $donation) {
            $activities[] = [
                'type' => 'donation',
                'name' => $this->donationService->donorPrivacyName($donation),
                'name2' => $this->donationService->athletePrivacyName($donation),
                'created_at' => $donation->created_at,
            ];
        }

        /** @var array<int, array{type: string, name: string, created_at: mixed, name2?: string}> $activities */
        usort($activities, function (array $a, array $b): int {
            return $a['created_at'] <=> $b['created_at'];
        });

        $activities = array_slice($activities, -10);

        return array_reverse($activities);
    }

    protected function meanNumberOfRounds(?DonationEvent $event): float
    {
        $mean = $this->registrationsQuery($event)->avg('rounds_estimated');

        return (float) ($mean ?? 0.0);
    }

    protected function totalEstimatedRounds(?DonationEvent $event): int
    {
        return (int) $this->registrationsQuery($event)->sum('rounds_estimated');
    }

    protected function totalActualRounds(?DonationEvent $event): int
    {
        return (int) $this->registrationsQuery($event)->sum('rounds_done');
    }

    /** @return Builder<AthleteRegistration> */
    protected function registrationsQuery(?DonationEvent $event): Builder
    {
        return AthleteRegistration::query()
            ->when($event instanceof DonationEvent, fn (Builder $query): Builder => $query->where('donation_event_id', $event->id));
    }

    /** @return Builder<Donation> */
    protected function donationsQuery(?DonationEvent $event): Builder
    {
        return Donation::query()
            ->when($event instanceof DonationEvent, function (Builder $query) use ($event): void {
                $query->whereHas('athleteRegistration', fn (Builder $registration): Builder => $registration->where('donation_event_id', $event->id));
            });
    }
}
