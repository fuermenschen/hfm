<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Services\AthleteService;
use App\Services\DonationService;
use App\Services\DonorService;
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
     *     verifiedAthleteCount: int,
     *     verifiedDonationCount: int,
     *     meanNumberOfDonations: float,
     *     meanNumberOfRounds: float|int|null,
     *     meanNumberOfDonationsDonor: float,
     *     meanDonationAmount: float,
     *     expectedDonationAmount: float,
     *     actualTotalAmount: float,
     *     estimatedAmounts: array<int, float>,
     *     actualAmounts: array<int, float>,
     *     mostRecentActivities: array<int, array<string, mixed>>,
     * }
     */
    public function __invoke(?DonationEvent $event = null): array
    {
        $greeting = $this->greeting();
        $events = DonationEvent::query()
            ->latest('starts_at')
            ->get(['id', 'title', 'slug', 'is_published']);
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

        $verifiedAthleteCount = $event instanceof DonationEvent
            ? (int) $this->registrationsQuery($event)
                ->where('verified', true)
                ->distinct()
                ->count('external_user_id')
            : $this->athleteService->verifiedCount();
        $verifiedDonationCount = (int) $this->donationsQuery($event)->where('verified', true)->count();

        $meanNumberOfDonations = $athleteCount > 0 ? (float) ($donationCount / $athleteCount) : 0.0;
        $meanNumberOfRounds = $this->meanNumberOfRounds($event);
        $meanNumberOfDonationsDonor = $donorCount > 0 ? (float) ($donationCount / $donorCount) : 0.0;
        $meanDonationAmount = (float) ($this->donationsQuery($event)->avg('amount_per_round') ?? 0.0);

        $donations = $this->donationsQuery($event)
            ->with(['athleteRegistration.partner', 'athleteRegistration.externalUser'])
            ->get();

        $expectedDonationAmount = $this->donationService->calculateEstimatedTotal($donations);
        $actualTotalAmount = $this->donationService->calculateActualTotal($donations);

        $estimatedAmounts = $this->donationService->calculateEstimatedTotalPerPartner($donations);
        $actualAmounts = $this->donationService->calculateActualTotalPerPartner($donations);

        $mostRecentActivities = $this->buildRecentActivities($event);

        return compact(
            'greeting',
            'events',
            'selectedEventSlug',
            'partners',
            'athleteCount',
            'donorCount',
            'donationCount',
            'verifiedAthleteCount',
            'verifiedDonationCount',
            'meanNumberOfDonations',
            'meanNumberOfRounds',
            'meanNumberOfDonationsDonor',
            'meanDonationAmount',
            'expectedDonationAmount',
            'actualTotalAmount',
            'estimatedAmounts',
            'actualAmounts',
            'mostRecentActivities',
        );
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
