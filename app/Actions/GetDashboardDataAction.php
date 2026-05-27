<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Services\AthleteService;
use App\Services\DonationService;
use App\Services\DonorService;
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
    public function __invoke(): array
    {
        $greeting = $this->greeting();

        $partners = Partner::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $athleteCount = $this->athleteService->count();
        $donorCount = $this->donorService->count();
        $donationCount = (int) Donation::query()->count();

        $verifiedAthleteCount = $this->athleteService->verifiedCount();
        $verifiedDonationCount = (int) Donation::query()->where('verified', true)->count();

        $meanNumberOfDonations = $athleteCount > 0 ? (float) ($donationCount / $athleteCount) : 0.0;
        $meanNumberOfRounds = $this->meanNumberOfRounds();
        $meanNumberOfDonationsDonor = $donorCount > 0 ? (float) ($donationCount / $donorCount) : 0.0;
        $meanDonationAmount = (float) (Donation::query()->avg('amount_per_round') ?? 0.0);

        $donations = Donation::query()->with(['athleteRegistration.partner', 'athleteRegistration.externalUser'])->get();

        $expectedDonationAmount = $this->donationService->calculateEstimatedTotal($donations);
        $actualTotalAmount = $this->donationService->calculateActualTotal($donations);

        $estimatedAmounts = $this->donationService->calculateEstimatedTotalPerPartner($donations);
        $actualAmounts = $this->donationService->calculateActualTotalPerPartner($donations);

        $mostRecentActivities = $this->buildRecentActivities();

        return compact(
            'greeting',
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
    protected function buildRecentActivities(): array
    {
        $sevenDaysAgo = now()->subDays(7);

        $recentAthletes = $this->athleteService->all()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->latest()
            ->limit(30)
            ->get(['id', 'first_name', 'last_name', 'created_at']);

        $recentDonors = $this->donorService->all()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->latest()
            ->limit(30)
            ->get(['id', 'first_name', 'last_name', 'created_at']);

        $recentDonations = Donation::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->with([
                'donorExternalUser:id,first_name,last_name',
                'athleteRegistration.externalUser:id,first_name,last_name',
            ])
            ->latest()
            ->limit(30)
            ->get(['id', 'donor_external_user_id', 'athlete_registration_id', 'created_at']);

        $activities = [];

        foreach ($recentAthletes as $athlete) {
            if (! $athlete instanceof ExternalUser) {
                continue;
            }

            $activities[] = [
                'type' => 'athlete',
                'name' => $athlete->privacyName(),
                'created_at' => $athlete->created_at,
            ];
        }

        foreach ($recentDonors as $donor) {
            /** @var ExternalUser $donor */
            $activities[] = [
                'type' => 'donor',
                'name' => $donor->privacyName(),
                'created_at' => $donor->created_at,
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

    protected function meanNumberOfRounds(): float
    {
        $mean = AthleteRegistration::query()->avg('rounds_estimated');

        return (float) ($mean ?? 0.0);
    }
}
