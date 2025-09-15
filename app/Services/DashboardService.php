<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donator;
use App\Models\Partner;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(public DonationService $donationService) {}

    /**
     * Build all data required by the admin dashboard view.
     *
     * We intentionally avoid returning large model collections that the view does not use.
     * Instead, we compute metrics via database aggregates and only return lightweight data.
     *
     * @return array{
     *     greeting: string,
     *     partners: Collection<int, Partner>,
     *     athleteCount: int,
     *     donatorCount: int,
     *     donationCount: int,
     *     verifiedAthleteCount: int,
     *     verifiedDonationCount: int,
     *     meanNumberOfDonations: float,
     *     meanNumberOfRounds: float|int|null,
     *     meanNumberOfDonationsDonator: float,
     *     meanDonationAmount: float,
     *     expectedDonationAmount: float,
     *     actualTotalAmount: float,
     *     estimatedAmounts: array<int, float>,
     *     actualAmounts: array<int, float>,
     *     mostRecentActivities: array<int, array<string, mixed>>,
     * }
     */
    public function getData(): array
    {
        $greeting = $this->greeting();

        // Partners needed for per-partner cards (id, name only)
        $partners = Partner::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        // Aggregate metrics without loading full collections
        $athleteCount = (int) Athlete::query()->count();
        $donatorCount = (int) Donator::query()->count();
        $donationCount = (int) Donation::query()->count();

        $verifiedAthleteCount = (int) Athlete::query()->where('verified', true)->count();
        $verifiedDonationCount = (int) Donation::query()->where('verified', true)->count();

        $meanNumberOfDonations = $athleteCount > 0 ? (float) ($donationCount / $athleteCount) : 0.0;
        $meanNumberOfRounds = Athlete::query()->avg('rounds_estimated');
        $meanNumberOfDonationsDonator = $donatorCount > 0 ? (float) ($donationCount / $donatorCount) : 0.0;
        $meanDonationAmount = (float) (Donation::query()->avg('amount_per_round') ?? 0.0);

        // Totals via DonationService (kept as-is)
        $expectedDonationAmount = $this->donationService->calculateEstimatedTotal();
        $actualTotalAmount = $this->donationService->calculateActualTotal();

        $estimatedAmounts = $this->donationService->calculateEstimatedTotalPerPartner();
        $actualAmounts = $this->donationService->calculateActualTotalPerPartner();

        $mostRecentActivities = $this->buildRecentActivities();

        return compact(
            'greeting',
            'partners',
            'athleteCount',
            'donatorCount',
            'donationCount',
            'verifiedAthleteCount',
            'verifiedDonationCount',
            'meanNumberOfDonations',
            'meanNumberOfRounds',
            'meanNumberOfDonationsDonator',
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
     * Build a normalized list of the most recent activities from the past 7 days.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildRecentActivities(): array
    {
        $sevenDaysAgo = now()->subDays(7);

        // Query recent entities with minimal columns
        $recentAthletes = Athlete::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->latest()
            ->limit(30)
            ->get(['id', 'first_name', 'last_name', 'created_at']);

        $recentDonators = Donator::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->latest()
            ->limit(30)
            ->get(['id', 'first_name', 'last_name', 'created_at']);

        $recentDonations = Donation::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->with([
                'donator:id,first_name,last_name',
                'athlete:id,first_name,last_name',
            ])
            ->latest()
            ->limit(30)
            ->get(['id', 'donator_id', 'athlete_id', 'created_at']);

        $activities = [];

        foreach ($recentAthletes as $athlete) {
            $activities[] = [
                'type' => 'athlete',
                'name' => $athlete->privacy_name,
                'created_at' => $athlete->created_at,
            ];
        }

        foreach ($recentDonators as $donator) {
            $activities[] = [
                'type' => 'donator',
                'name' => $donator->privacy_name,
                'created_at' => $donator->created_at,
            ];
        }

        foreach ($recentDonations as $donation) {
            $activities[] = [
                'type' => 'donation',
                'name' => $donation->donator->privacy_name,
                'name2' => $donation->athlete->privacy_name,
                'created_at' => $donation->created_at,
            ];
        }

        // Sort by created_at ASC, keep last 10, then reverse to DESC
        usort($activities, function ($a, $b) {
            return $a['created_at'] <=> $b['created_at'];
        });

        $activities = array_slice($activities, -10);
        $activities = array_reverse($activities);

        return $activities;
    }
}
