<?php

namespace App\Components;

use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Partner;
use App\Services\DonationService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Results extends Component
{
    /**
     * Public state passed to the view.
     *
     * @var array<string, mixed>
     */
    public array $totals = [];

    public function mount(DonationService $donationService): void
    {
        // Compute a version string based on the latest updated_at timestamps
        $row = DB::selectOne('
            select
              (select max(updated_at) from athletes)  as a,
              (select max(updated_at) from donations) as d
        ');
        $version = hash('sha256', ($row->a ?? '0').($row->d ?? '0'));
        $cacheKey = 'components.results.data.'.$version;

        $data = Cache::remember($cacheKey, now()->addHour(), function () use ($donationService): array {
            return $this->collectData($donationService);
        });

        $this->totals = $data['totals'];
    }

    public function render(): ViewContract
    {
        // Properties are already prepared in mount() (and cached).
        return view('components.results');
    }

    /**
     * Collect and prepare all data for the component.
     * Ensures a single initial athletes query including relations (donations, partner, sportType).
     *
     * @return array{totals: array<string, mixed>}
     */
    protected function collectData(DonationService $donationService): array
    {
        // One initial query for ALL athletes with needed relations
        $allAthletes = Athlete::query()
            ->with(['sportType:id,name', 'partner:id,name', 'donationEvent:id,has_equal_split_option', 'donations'])
            ->get();

        // Totals (prefer in-memory where possible)
        $athletesCount = $allAthletes->count();
        $donorsCount = $allAthletes->flatMap->donations
            ->pluck('donor_id')->filter()->unique()->count();
        $roundsTotal = (int) ($allAthletes->sum('rounds_done') ?? 0);
        $elevationTotal = $roundsTotal * 50; // meters

        // Build donations list from already loaded athletes to avoid extra queries
        $donations = $allAthletes->flatMap(function (Athlete $athlete) {
            return collect($athlete->donations)->map(function (Donation $donation) use ($athlete) {
                // Ensure the donation has the athlete relation (with partner) set to avoid N+1 later
                $donation->setRelation('athlete', $athlete);

                return $donation;
            });
        });
        $donationsTotal = $donationService->calculateActualTotal($donations);

        // Donations per partner (name => amount)
        $perPartnerRaw = $donationService->calculateActualTotalPerPartner($donations); // [partner_id => amount]
        // Build partner id => name map from already eager-loaded athletes to avoid extra query
        $partners = $allAthletes
            ->pluck('partner')
            ->filter()
            ->keyBy('id')
            ->map(function (Partner $partner) {
                return $partner->name;
            });
        $perPartner = collect($perPartnerRaw)
            ->mapWithKeys(function (float $amount, int $partnerId) use ($partners): array {
                return [($partners[$partnerId] ?? ('Partner #'.$partnerId)) => $amount];
            })
            ->sortKeys();

        // Legacy special rule: If there's a partner named 'alle zu gleichen Teilen', split evenly among others.
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

        // New rule: for each event with equal split enabled, distribute those donations
        // only among the partners active in that event (i.e. athletes with a partner in that event).
        // This prevents cross-event bleed when multiple events have different partner sets.
        $allAthletes
            ->groupBy('donation_event_id')
            ->each(function ($eventAthletes, mixed $eventId) use (&$perPartner, $donations, $donationService, $partners): void {
                if (! $eventId) {
                    return;
                }

                $event = $eventAthletes->first()?->donationEvent;
                if ($event === null || ! (bool) $event->has_equal_split_option) {
                    return;
                }

                // Athletes in this event who selected "equal split" (no partner assigned)
                $equalSplitAthleteIds = array_flip(
                    $eventAthletes
                        ->whereNull('partner_id')
                        ->pluck('id')
                        ->map(fn (mixed $id): int => (int) $id)
                        ->all()
                );

                if ($equalSplitAthleteIds === []) {
                    return;
                }

                $eventEqualAmount = $donations
                    ->filter(fn (Donation $d): bool => isset($equalSplitAthleteIds[(int) $d->athlete_id]))
                    ->sum(fn (Donation $d): float => $donationService->calculateActualAmount($d));

                if ($eventEqualAmount <= 0.0) {
                    return;
                }

                // Only distribute to partners that appear in this event's athlete registrations
                $eventPartnerNameSet = array_flip(
                    $eventAthletes
                        ->pluck('partner_id')
                        ->filter()
                        ->unique()
                        ->map(fn (mixed $id): string => (string) ($partners->get((int) $id) ?? ''))
                        ->filter(fn (string $name): bool => $name !== '')
                        ->values()
                        ->all()
                );

                $targetCount = count($eventPartnerNameSet);
                if ($targetCount <= 0) {
                    return;
                }

                // Ensure every target partner has an entry so the share is never dropped.
                foreach (array_keys($eventPartnerNameSet) as $name) {
                    if (! $perPartner->has($name)) {
                        $perPartner->put($name, 0.0);
                    }
                }

                $share = $eventEqualAmount / $targetCount;
                $perPartner = $perPartner->map(function (float $amount, string $partnerName) use ($share, $eventPartnerNameSet): float {
                    return isset($eventPartnerNameSet[$partnerName]) ? $amount + $share : $amount;
                });
            });

        return [
            'totals' => [
                'athletes' => $athletesCount,
                'donors' => $donorsCount,
                'rounds' => $roundsTotal,
                'elevation_m' => $elevationTotal,
                'donations_total' => $donationsTotal,
                'per_partner' => $perPartner->toArray(),
            ],
        ];
    }
}
