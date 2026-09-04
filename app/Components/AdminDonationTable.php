<?php

declare(strict_types=1);

namespace App\Components;

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Services\CurrentDonationEventService;
use App\Services\DonationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminDonationTable extends AbstractDatatableComponent
{
    public string $sortField = 'created_at';

    #[Url(as: 'anlass', except: '')]
    public ?string $eventSlug = '';

    protected DonationService $donationService;

    protected CurrentDonationEventService $currentDonationEventService;

    public function boot(DonationService $donationService, CurrentDonationEventService $currentDonationEventService): void
    {
        $this->donationService = $donationService;
        $this->currentDonationEventService = $currentDonationEventService;
    }

    public function mount(): void
    {
        if (! request()->query->has('anlass') && ($this->eventSlug === null || $this->eventSlug === '')) {
            $currentEvent = $this->currentDonationEventService->current();
            $this->eventSlug = $currentEvent instanceof DonationEvent ? $currentEvent->slug : '';
        }

        parent::mount();
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

        foreach ($this->queryForTable(ignoreSearch: true)->whereKey($selectedIds)->orderBy('id')->get() as $donation) {
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
            'athleteRegistration.externalUser.first_name',
            'athleteRegistration.externalUser.last_name',
            'donorExternalUser.first_name',
            'donorExternalUser.last_name',
        ];
    }

    protected function baseQuery(): Builder
    {
        $athleteNameQuery = AthleteRegistration::query()
            ->select('external_users.first_name')
            ->join('external_users', 'external_users.id', '=', 'athlete_registrations.external_user_id')
            ->whereColumn('athlete_registrations.id', 'donations.athlete_registration_id')
            ->whereHas('externalUser')
            ->limit(1);

        $donorNameQuery = ExternalUser::query()
            ->select('first_name')
            ->whereColumn('external_users.id', 'donations.donor_external_user_id')
            ->limit(1);

        return Donation::query()
            ->with(['athleteRegistration.donationEvent', 'athleteRegistration.externalUser', 'donorExternalUser'])
            ->addSelect([
                'selected_athlete_name' => $athleteNameQuery,
                'selected_donor_name' => $donorNameQuery,
            ]);
    }

    protected function applyFilters(Builder $query): void
    {
        if ($this->eventSlug === null || $this->eventSlug === '') {
            return;
        }

        $query->whereHas('athleteRegistration.donationEvent', fn (Builder $event): Builder => $event->where('slug', $this->eventSlug));
    }

    protected function tableFilterProperties(): array
    {
        return ['eventSlug'];
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
            'athlete' => 'selected_athlete_name',
            'donor' => 'selected_donor_name',
        ];
    }

    /**
     * @return array<string, array{label:string, sortable:bool, sort_field?:string, align?:string, width?:string, tooltip?:bool, truncate?:int, export_key?:string, formatter?:string}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'donor' => ['label' => 'Spender:in', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-52', 'export_key' => 'Spender:in'],
            'athlete' => ['label' => 'Sportler:in', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-52', 'export_key' => 'Sportler:in'],
            'event' => ['label' => 'Anlass', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-28', 'export_key' => 'Anlass'],
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
            'event',
            'verified',
            'amount_per_round',
            'estimated',
            'actual',
            'created_at',
        ];
    }

    protected function tableViewData(LengthAwarePaginator $paginator): array
    {
        return [
            'events' => DonationEvent::query()->latest('starts_at')->get(['id', 'title', 'slug', 'is_published']),
        ];
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(Donation $donation): array
    {
        return [
            'Spender:in' => $this->donationService->donorPrivacyName($donation),
            'Sportler:in' => $this->donationService->athletePrivacyName($donation),
            'Anlass' => $donation->athleteRegistration?->donationEvent?->slug,
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
