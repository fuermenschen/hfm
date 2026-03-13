<?php

namespace App\Components;

use App\Components\Concerns\InteractsWithAdminDatatable;
use App\Models\Donation;
use App\Services\DonationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminDonationTable extends Component
{
    use InteractsWithAdminDatatable;
    use WithPagination;

    public string $sortField = 'created_at';

    protected DonationService $donationService;

    public function boot(DonationService $donationService): void
    {
        $this->donationService = $donationService;
    }

    public function mount(): void
    {
        $this->initializeVisibleColumns();
    }

    public function render(): View
    {
        $donations = $this->queryForTable(ignoreSearch: false)->paginate($this->perPage);

        return view('components.admin.tables.donation-table', [
            'donations' => $donations,
            'pageIds' => $this->pageIds($donations),
        ]);
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

    protected function queryForTable(bool $ignoreSearch): Builder
    {
        $query = $this->baseQuery();

        if (! $ignoreSearch && $this->search !== '') {
            $search = '%'.$this->search.'%';

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

        $sortColumn = $this->resolveSortColumn();

        if ($sortColumn === null) {
            $sortColumn = 'donations.created_at';
        }

        return $query->orderBy($sortColumn, $this->sortDirection);
    }

    protected function baseQuery(): Builder
    {
        return Donation::query()->with(['athlete', 'donator']);
    }

    protected function resolveSortColumn(): ?string
    {
        return match ($this->sortField) {
            'created_at' => 'donations.created_at',
            'verified' => 'donations.verified',
            'amount_per_round' => 'donations.amount_per_round',
            'amount_min' => 'donations.amount_min',
            'amount_max' => 'donations.amount_max',
            default => null,
        };
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

    /**
     * @return array<int, int>
     */
    protected function pageIds(LengthAwarePaginator $paginator): array
    {
        return $paginator->getCollection()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
    }
}
