<?php

namespace App\Components;

use App\Models\Donation;
use App\Services\DonationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminDonationTable extends AbstractAdminDatatableComponent
{
    public string $sortField = 'created_at';

    protected DonationService $donationService;

    public function boot(DonationService $donationService): void
    {
        $this->donationService = $donationService;
    }

    protected function tableView(): string
    {
        return 'components.admin.tables.donation-table';
    }

    protected function tableDataKey(): string
    {
        return 'donations';
    }

    public function exportAll(string $format): ?HttpResponse
    {
        $rows = [];

        foreach ($this->queryForTable(ignoreSearch: true)->get() as $donation) {
            if (! $donation instanceof Donation) {
                continue;
            }

            $rows[] = $this->exportRow($donation);
        }

        return $this->exportRowsToDownload($rows, 'spenden_gesamt', $format);
    }

    public function exportSelected(string $format): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine Spende aus.');

            return null;
        }

        $rows = [];

        foreach ($this->baseQuery()->whereKey($selectedIds)->orderBy('id')->get() as $donation) {
            if (! $donation instanceof Donation) {
                continue;
            }

            $rows[] = $this->exportRow($donation);
        }

        return $this->exportRowsToDownload($rows, 'spenden_auswahl', $format);
    }

    public function estimatedAmount(Donation $donation): float
    {
        return $this->donationService->calculateEstimatedAmount($donation);
    }

    public function actualAmount(Donation $donation): float
    {
        return $this->donationService->calculateActualAmount($donation);
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $builder) use ($search): void {
            $builder->where('comment', 'like', $search)
                ->orWhereHas('athlete', function (Builder $athleteQuery) use ($search): void {
                    $athleteQuery->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search);
                })
                ->orWhereHas('donator', function (Builder $donatorQuery) use ($search): void {
                    $donatorQuery->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search);
                });
        });
    }

    protected function baseQuery(): Builder
    {
        return Donation::query()->with(['athlete', 'donator']);
    }

    protected function defaultSortColumn(): string
    {
        return 'donations.created_at';
    }

    /**
     * @return array<string, string>
     */
    protected function sortColumns(): array
    {
        return [
            'created_at' => 'donations.created_at',
            'verified' => 'donations.verified',
            'amount_per_round' => 'donations.amount_per_round',
            'amount_min' => 'donations.amount_min',
            'amount_max' => 'donations.amount_max',
        ];
    }

    /**
     * @return array<string, array{label:string, sortable:bool, sort_field?:string}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'donator' => ['label' => 'Spender:in', 'sortable' => false],
            'athlete' => ['label' => 'Sportler:in', 'sortable' => false],
            'verified' => ['label' => 'Bestätigt', 'sortable' => true],
            'amount_per_round' => ['label' => 'Betrag pro Runde', 'sortable' => true],
            'estimated' => ['label' => 'Geschätzter Betrag', 'sortable' => false],
            'actual' => ['label' => 'Tatsächlicher Betrag', 'sortable' => false],
            'amount_min' => ['label' => 'Minimaler Betrag', 'sortable' => true],
            'amount_max' => ['label' => 'Maximaler Betrag', 'sortable' => true],
            'created_at' => ['label' => 'Erstellt am', 'sortable' => true],
            'comment' => ['label' => 'Kommentar', 'sortable' => false],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function defaultVisibleColumns(): array
    {
        return [
            'donator',
            'athlete',
            'verified',
            'amount_per_round',
            'estimated',
            'actual',
            'created_at',
        ];
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(Donation $donation): array
    {
        return [
            'Spender:in' => $donation->donator->privacy_name,
            'Sportler:in' => $donation->athlete->privacy_name,
            'Bestätigt' => $donation->verified ? 'Ja' : 'Nein',
            'Betrag pro Runde' => $donation->amount_per_round,
            'Geschätzter Betrag' => $this->estimatedAmount($donation),
            'Tatsächlicher Betrag' => $this->actualAmount($donation),
            'Minimaler Betrag' => $donation->amount_min,
            'Maximaler Betrag' => $donation->amount_max,
            'Erstellt am' => Carbon::parse($donation->created_at)->format('d.m.Y'),
            'Kommentar' => $donation->comment,
        ];
    }
}
