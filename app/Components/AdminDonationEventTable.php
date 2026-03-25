<?php

namespace App\Components;

use App\Models\DonationEvent;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminDonationEventTable extends AbstractDatatableComponent
{
    public string $sortField = 'starts_at';

    protected function tableView(): string
    {
        return 'components.admin.tables.donation-event-table';
    }

    protected function tableDataKey(): string
    {
        return 'donationEvents';
    }

    public function exportAll(string $format): ?HttpResponse
    {
        $rows = [];

        foreach ($this->queryForTable(ignoreSearch: true)->get() as $donationEvent) {
            if (! $donationEvent instanceof DonationEvent) {
                continue;
            }

            $rows[] = $this->exportRow($donationEvent);
        }

        return $this->exportRowsToDownload($rows, 'anlaesse_gesamt', $format);
    }

    public function exportSelected(string $format): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens einen Anlass aus.');

            return null;
        }

        $rows = [];

        foreach ($this->baseQuery()->whereKey($selectedIds)->orderBy('id')->get() as $donationEvent) {
            if (! $donationEvent instanceof DonationEvent) {
                continue;
            }

            $rows[] = $this->exportRow($donationEvent);
        }

        return $this->exportRowsToDownload($rows, 'anlaesse_auswahl', $format);
    }

    /**
     * @return array<int|string, string>
     */
    protected function searchableColumns(): array
    {
        return [
            'slug',
            'title',
            'location_name',
            'location_street',
            'location_postal_code',
            'location_city',
            'location_url',
        ];
    }

    protected function baseQuery(): Builder
    {
        return DonationEvent::query();
    }

    protected function defaultSortColumn(): string
    {
        return 'donation_events.starts_at';
    }

    /**
     * @return array<string, string>
     */
    protected function sortColumns(): array
    {
        return [
            'slug' => 'donation_events.slug',
            'title' => 'donation_events.title',
            'starts_at' => 'donation_events.starts_at',
            'ends_at' => 'donation_events.ends_at',
            'registration_opens_at' => 'donation_events.registration_opens_at',
            'athlete_registration_closes_at' => 'donation_events.athlete_registration_closes_at',
            'donor_registration_closes_at' => 'donation_events.donor_registration_closes_at',
            'location_name' => 'donation_events.location_name',
            'location_street' => 'donation_events.location_street',
            'location_postal_code' => 'donation_events.location_postal_code',
            'location_city' => 'donation_events.location_city',
            'location_url' => 'donation_events.location_url',
            'is_published' => 'donation_events.is_published',
            'created_at' => 'donation_events.created_at',
        ];
    }

    /**
     * @return array<string, array{label:string, sortable:bool, sort_field?:string, align?:string, width?:string, tooltip?:bool, truncate?:int, export_key?:string, formatter?:string}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'slug' => ['label' => 'Jahr', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-24', 'export_key' => 'Jahr'],
            'title' => ['label' => 'Titel', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-56', 'export_key' => 'Titel'],
            'starts_at' => ['label' => 'Start', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Start', 'formatter' => 'datetime_or_dash'],
            'ends_at' => ['label' => 'Ende', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Ende', 'formatter' => 'datetime_or_dash'],
            'registration_opens_at' => ['label' => 'Anmeldung offen', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-44', 'export_key' => 'Anmeldung offen', 'formatter' => 'datetime_or_dash'],
            'athlete_registration_closes_at' => ['label' => 'Anmeldung Sportler:innen bis', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-56', 'export_key' => 'Anmeldung Sportler:innen bis', 'formatter' => 'datetime_or_dash'],
            'donor_registration_closes_at' => ['label' => 'Anmeldung Spender:innen bis', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-56', 'export_key' => 'Anmeldung Spender:innen bis', 'formatter' => 'datetime_or_dash'],
            'location_name' => ['label' => 'Ort Name', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Ort Name'],
            'location_street' => ['label' => 'Strasse', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-48', 'export_key' => 'Strasse'],
            'location_postal_code' => ['label' => 'PLZ', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-24', 'export_key' => 'PLZ'],
            'location_city' => ['label' => 'Stadt', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-32', 'export_key' => 'Stadt'],
            'location_url' => ['label' => 'Kartenlink', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-56', 'tooltip' => true, 'truncate' => 48, 'export_key' => 'Kartenlink'],
            'is_published' => ['label' => 'Veröffentlicht', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-32', 'export_key' => 'Veröffentlicht'],
            'created_at' => ['label' => 'Erstellt am', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'Erstellt am', 'formatter' => 'date'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function defaultVisibleColumns(): array
    {
        return [
            'slug',
            'title',
            'starts_at',
            'ends_at',
            'is_published',
            'location_city',
            'location_url',
        ];
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(DonationEvent $donationEvent): array
    {
        return [
            'Jahr' => $donationEvent->slug,
            'Titel' => $donationEvent->title,
            'Start' => $this->formatDateTimeOrNull($donationEvent->starts_at),
            'Ende' => $this->formatDateTimeOrNull($donationEvent->ends_at),
            'Anmeldung offen' => $this->formatDateTimeOrNull($donationEvent->registration_opens_at),
            'Anmeldung Sportler:innen bis' => $this->formatDateTimeOrNull($donationEvent->athlete_registration_closes_at),
            'Anmeldung Spender:innen bis' => $this->formatDateTimeOrNull($donationEvent->donor_registration_closes_at),
            'Ort Name' => $donationEvent->location_name,
            'Strasse' => $donationEvent->location_street,
            'PLZ' => $donationEvent->location_postal_code,
            'Stadt' => $donationEvent->location_city,
            'Kartenlink' => $donationEvent->location_url,
            'Veröffentlicht' => $donationEvent->is_published ? 'Ja' : 'Nein',
            'Erstellt am' => $this->formatDate($donationEvent->created_at),
        ];
    }
}
