<?php

namespace App\Components;

use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donator;
use App\Models\Partner;
use App\Services\DonationService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Results extends Component
{
    /**
     * Public state passed to the view.
     *
     * @var array<string, mixed>
     */
    public array $totals = [];

    /**
     * @var array<int, array<string, string>>
     */
    public array $athletes = [];

    public function mount(DonationService $donationService): void
    {
        // Compute a version string based on the latest updated_at timestamps
        $athleteUpdatedAt = Athlete::max('updated_at');
        $donationUpdatedAt = Donation::max('updated_at');
        $version = $athleteUpdatedAt.'|'.$donationUpdatedAt;
        $cacheKey = 'components.results.data.'.$version;

        $data = Cache::remember($cacheKey, now()->addHour(), function () use ($donationService): array {
            return $this->collectData($donationService);
        });

        $this->totals = $data['totals'];
        $this->athletes = $data['athletes'];
    }

    public function render(): ViewContract
    {
        // Properties are already prepared in mount() (and cached).
        return view('components.results', [
            'totals' => $this->totals,
            'athletes' => $this->athletes,
        ]);
    }

    /**
     * Collect and prepare all data for the component.
     * Ensures a single initial athletes query including relations (donations, partner, sportType).
     *
     * @return array{totals: array<string, mixed>, athletes: array<int, array<string, string>>}
     */
    protected function collectData(DonationService $donationService): array
    {
        // One initial query for ALL athletes with needed relations
        $allAthletes = Athlete::query()
            ->with(['sportType:id,name', 'partner:id,name', 'donations'])
            ->get();

        // Totals (prefer in-memory where possible)
        $athletesCount = $allAthletes->count();
        $donorsCount = Donator::query()->count();
        $roundsTotal = (int) ($allAthletes->sum('rounds_done') ?? 0);
        $elevationTotal = $roundsTotal * 50; // meters
        $donationsTotal = $donationService->calculateActualTotal();

        // Donations per partner (name => amount)
        $perPartnerRaw = $donationService->calculateActualTotalPerPartner(); // [partner_id => amount]
        $partners = Partner::query()->whereIn('id', array_keys($perPartnerRaw))->pluck('name', 'id');
        $perPartner = collect($perPartnerRaw)
            ->mapWithKeys(function (float $amount, int $partnerId) use ($partners): array {
                return [$partners[$partnerId] ?? ('Partner #'.$partnerId) => $amount];
            })
            ->sortKeys();

        // Special rule: If there's a partner named 'alle zu gleichen Teilen', split evenly among others.
        $equalShareName = 'alle zu gleichen Teilen';
        if ($perPartner->has($equalShareName)) {
            $amountToSplit = (float) $perPartner->get($equalShareName, 0.0);
            $others = $perPartner->except($equalShareName);
            $count = $others->count();
            if ($count > 0) {
                $share = $amountToSplit / $count;
                $perPartner = $others->map(function (float $amount) use ($share): float {
                    return $amount + $share;
                });
            } else {
                // No other partners to split into; remove the special partner.
                $perPartner = collect();
            }
        }

        // Athletes table data (exclude athletes without completed rounds)
        $athletes = $allAthletes
            ->filter(function (Athlete $athlete): bool {
                return (int) ($athlete->rounds_done ?? 0) > 0;
            })
            ->map(function (Athlete $athlete) use ($donationService): array {
                $donationsActual = $donationService->calculateActualTotalForAthlete($athlete);

                $rounds = (int) ($athlete->rounds_done ?? 0);
                $roundsFormatted = number_format($rounds, 0, '.', "'");
                $donationsFormatted = number_format($donationsActual, 2, '.', "'");

                return [
                    'privacy_name' => $athlete->privacy_name,
                    'sport_type' => optional($athlete->sportType)->name ?? '—',
                    'partner' => optional($athlete->partner)->name ?? '—',
                    'rounds_done' => $roundsFormatted,
                    'donations_actual' => $donationsFormatted,
                ];
            })
            ->sortBy('privacy_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return [
            'totals' => [
                'athletes' => $athletesCount,
                'donors' => $donorsCount,
                'rounds' => $roundsTotal,
                'elevation_m' => $elevationTotal,
                'donations_total' => $donationsTotal,
                'per_partner' => $perPartner,
            ],
            'athletes' => $athletes,
        ];
    }
}
