<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Athlete;
use App\Models\Donation;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Services\DonationService;
use App\Services\DonorService;
use Illuminate\Support\Collection;

class GetDashboardDataAction
{
    public function __construct(
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

        $athleteCount = (int) Athlete::query()->count();
        $donorCount = $this->donorService->count();
        $donationCount = (int) Donation::query()->count();

        $verifiedAthleteCount = (int) Athlete::query()->where('verified', true)->count();
        $verifiedDonationCount = (int) Donation::query()->where('verified', true)->count();

        $meanNumberOfDonations = $athleteCount > 0 ? (float) ($donationCount / $athleteCount) : 0.0;
        $meanNumberOfRounds = Athlete::query()->avg('rounds_estimated');
        $meanNumberOfDonationsDonor = $donorCount > 0 ? (float) ($donationCount / $donorCount) : 0.0;
        $meanDonationAmount = (float) (Donation::query()->avg('amount_per_round') ?? 0.0);

        $donations = Donation::query()->with('athlete.partner')->get();

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

        $recentAthletes = Athlete::query()
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
                'athlete:id,first_name,last_name',
            ])
            ->latest()
            ->limit(30)
            ->get(['id', 'donor_external_user_id', 'athlete_id', 'created_at']);

        $activities = [];

        foreach ($recentAthletes as $athlete) {
            $activities[] = [
                'type' => 'athlete',
                'name' => $athlete->privacy_name,
                'created_at' => $athlete->created_at,
            ];
        }

        foreach ($recentDonors as $donor) {
            /** @var ExternalUser $donor */
            $activities[] = [
                'type' => 'donor',
                'name' => $donor->privacy_name,
                'created_at' => $donor->created_at,
            ];
        }

        foreach ($recentDonations as $donation) {
            $activities[] = [
                'type' => 'donation',
                'name' => $donation->donorPrivacyName(),
                'name2' => $donation->athlete->privacy_name ?? 'Legacy Sportler:in',
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
}
