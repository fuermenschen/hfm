<?php

declare(strict_types=1);

namespace App\Components;

use App\Models\Donation;
use App\Services\DonationService;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminDonationTable extends AbstractDatatableComponent
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

    /**
     * @return array<int|string, string>
     */
    protected function searchableColumns(): array
    {
        return [
            'comment',
            'athlete.first_name',
            'athlete.last_name',
            'donor.first_name',
            'donor.last_name',
        ];
    }

    protected function baseQuery(): Builder
    {
        return Donation::query()->with(['athlete', 'donor']);
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
     * @return array<string, array{label:string, sortable:bool, sort_field?:string, align?:string, width?:string, tooltip?:bool, truncate?:int, export_key?:string, formatter?:string}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'donor' => ['label' => 'Spender:in', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-52', 'export_key' => 'Spender:in'],
            'athlete' => ['label' => 'Sportler:in', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-52', 'export_key' => 'Sportler:in'],
            'verified' => ['label' => 'Bestätigt', 'sortable' => true, 'align' => 'center', 'width' => 'min-w-28', 'export_key' => 'Bestätigt', 'formatter' => 'yes_no'],
            'amount_per_round' => ['label' => 'Betrag pro Runde', 'sortable' => true, 'align' => 'right', 'width' => 'min-w-40', 'export_key' => 'Betrag pro Runde', 'formatter' => 'money'],
            'estimated' => ['label' => 'Geschätzter Betrag', 'sortable' => false, 'align' => 'right', 'width' => 'min-w-44', 'export_key' => 'Geschätzter Betrag', 'formatter' => 'money'],
            'actual' => ['label' => 'Tatsächlicher Betrag', 'sortable' => false, 'align' => 'right', 'width' => 'min-w-44', 'export_key' => 'Tatsächlicher Betrag', 'formatter' => 'money'],
            'amount_min' => ['label' => 'Minimaler Betrag', 'sortable' => true, 'align' => 'right', 'width' => 'min-w-40', 'export_key' => 'Minimaler Betrag', 'formatter' => 'money_or_unlimited'],
            'amount_max' => ['label' => 'Maximaler Betrag', 'sortable' => true, 'align' => 'right', 'width' => 'min-w-40', 'export_key' => 'Maximaler Betrag', 'formatter' => 'money_or_unlimited'],
            'created_at' => ['label' => 'Erstellt am', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'Erstellt am', 'formatter' => 'date'],
            'comment' => ['label' => 'Kommentar', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-60', 'tooltip' => true, 'truncate' => 48, 'export_key' => 'Kommentar'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function defaultVisibleColumns(): array
    {
        return [
            'donor',
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
            'Spender:in' => $donation->donor->privacy_name ?? 'Legacy Spender:in',
            'Sportler:in' => $donation->athlete->privacy_name ?? 'Legacy Sportler:in',
            'Bestätigt' => $donation->verified ? 'Ja' : 'Nein',
            'Betrag pro Runde' => $donation->amount_per_round,
            'Geschätzter Betrag' => $this->estimatedAmount($donation),
            'Tatsächlicher Betrag' => $this->actualAmount($donation),
            'Minimaler Betrag' => $donation->amount_min,
            'Maximaler Betrag' => $donation->amount_max,
            'Erstellt am' => $this->formatDate($donation->created_at),
            'Kommentar' => $donation->comment,
        ];
    }
}
