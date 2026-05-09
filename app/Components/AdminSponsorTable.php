<?php

declare(strict_types=1);

namespace App\Components;

use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminSponsorTable extends AbstractDatatableComponent
{
    public string $sortField = 'name';

    protected function tableView(): string
    {
        return 'components.admin.tables.sponsor-table';
    }

    protected function tableDataKey(): string
    {
        return 'sponsors';
    }

    /**
     * @return array<int|string, string>
     */
    protected function searchableColumns(): array
    {
        return [
            'name',
            'description',
            'url',
        ];
    }

    protected function baseQuery(): Builder
    {
        return Sponsor::query()->withCount('donationEvents');
    }

    protected function defaultSortColumn(): string
    {
        return 'sponsors.name';
    }

    /**
     * @return array<string, string>
     */
    protected function sortColumns(): array
    {
        return [
            'name' => 'sponsors.name',
            'donation_events_count' => 'donation_events_count',
            'created_at' => 'sponsors.created_at',
            'id' => 'sponsors.id',
        ];
    }

    /**
     * @return array<string, array{label:string, sortable:bool, sort_field?:string, align?:string, width?:string, tooltip?:bool, truncate?:int, export_key?:string, formatter?:string}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => ['label' => 'ID', 'sortable' => true, 'align' => 'right', 'width' => 'min-w-28', 'export_key' => 'ID'],
            'name' => ['label' => 'Name', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Name'],
            'description' => ['label' => 'Beschreibung', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-60', 'export_key' => 'Beschreibung', 'tooltip' => true, 'truncate' => 48],
            'logo_filename' => ['label' => 'Logo', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Logo'],
            'url' => ['label' => 'URL', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-56', 'export_key' => 'URL', 'tooltip' => true, 'truncate' => 48],
            'donation_events_count' => ['label' => 'Anlässe', 'sortable' => true, 'align' => 'right', 'width' => 'min-w-28', 'export_key' => 'Anlässe'],
            'created_at' => ['label' => 'Erstellt am', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'Erstellt am', 'formatter' => 'date'],
            'updated_at' => ['label' => 'Aktualisiert am', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'Aktualisiert am', 'formatter' => 'date'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function defaultVisibleColumns(): array
    {
        return [
            'name',
            'logo_filename',
            'url',
            'donation_events_count',
            'created_at',
        ];
    }

    public function displayValue(mixed $row, string $column): string
    {
        $definition = $this->columnDefinitions()[$column] ?? [];
        $formatter = (string) ($definition['formatter'] ?? 'text');
        $value = data_get($row, $column);

        return match ($formatter) {
            'money' => $this->formatMoney($this->toNumeric($value)),
            'date' => $this->formatDate($value),
            'date_time' => $this->formatDateTime($value),
            'yes_no' => is_bool($value) ? ($value ? 'Ja' : 'Nein') : '-',
            default => $this->fallbackText(is_scalar($value) ? (string) $value : null),
        };
    }

    protected function toNumeric(mixed $value): float|int|string|null
    {
        if (is_numeric($value) || $value === null) {
            return $value;
        }

        return null;
    }

    public function exportAll(string $format): ?HttpResponse
    {
        $rows = [];

        foreach ($this->queryForTable(ignoreSearch: true)->get() as $row) {
            $rows[] = $this->exportRow($row);
        }

        return $this->exportRowsToDownload($rows, 'sponsorinnen_gesamt', $format);
    }

    public function exportSelected(string $format): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine Sponsor:in aus.');

            return null;
        }

        $rows = [];

        foreach ($this->baseQuery()->whereKey($selectedIds)->orderBy('id')->get() as $row) {
            $rows[] = $this->exportRow($row);
        }

        return $this->exportRowsToDownload($rows, 'sponsorinnen_auswahl', $format);
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(mixed $row): array
    {
        return [
            'ID' => data_get($row, 'id'),
            'Name' => data_get($row, 'name'),
            'Beschreibung' => data_get($row, 'description'),
            'Logo' => data_get($row, 'logo_filename'),
            'URL' => data_get($row, 'url'),
            'Anlässe' => data_get($row, 'donation_events_count'),
            'Erstellt am' => $this->formatDateOrNull(data_get($row, 'created_at')),
            'Aktualisiert am' => $this->formatDateOrNull(data_get($row, 'updated_at')),
        ];
    }
}
