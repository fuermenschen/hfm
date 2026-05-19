<?php

declare(strict_types=1);

namespace App\Components;

use App\Models\ExternalUser;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminExternalUserTable extends AbstractDatatableComponent
{
    public string $sortField = 'first_name';

    protected function tableView(): string
    {
        return 'components.admin.tables.external-users-table';
    }

    protected function tableDataKey(): string
    {
        return 'external_users';
    }

    /**
     * @return array<int|string, string>
     */
    protected function searchableColumns(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'city',
            'country_of_residence',
        ];
    }

    protected function baseQuery(): Builder
    {
        return ExternalUser::query();
    }

    protected function defaultSortColumn(): string
    {
        return 'external_users.first_name';
    }

    /**
     * @return array<string, string>
     */
    protected function sortColumns(): array
    {
        return [
            'first_name' => 'external_users.first_name',
            'last_name' => 'external_users.last_name',
            'email' => 'external_users.email',
            'created_at' => 'external_users.created_at',
        ];
    }

    /**
     * @return array<string, array{label:string, sortable:bool, sort_field?:string, align?:string, width?:string, tooltip?:bool, truncate?:int, export_key?:string, formatter?:string}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'first_name' => ['label' => 'Vorname', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Vorname'],
            'last_name' => ['label' => 'Nachname', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Nachname'],
            'email' => ['label' => 'E-Mail', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-56', 'export_key' => 'E-Mail', 'tooltip' => true, 'truncate' => 52],
            'phone_number' => ['label' => 'Telefon', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Telefon'],
            'city' => ['label' => 'Ort', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Ort'],
            'country_of_residence' => ['label' => 'Wohnsitzland', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Wohnsitzland'],
            'created_at' => ['label' => 'Erstellt am', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Erstellt am', 'formatter' => 'date_time'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function defaultVisibleColumns(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'city',
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

        return $this->exportRowsToDownload($rows, 'external_users_gesamt', $format);
    }

    public function exportSelected(string $format): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine Zeile aus.');

            return null;
        }

        $rows = [];

        foreach ($this->baseQuery()->whereKey($selectedIds)->orderBy('id')->get() as $row) {
            $rows[] = $this->exportRow($row);
        }

        return $this->exportRowsToDownload($rows, 'external_users_auswahl', $format);
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(mixed $row): array
    {
        return [
            'Vorname' => data_get($row, 'first_name'),
            'Nachname' => data_get($row, 'last_name'),
            'E-Mail' => data_get($row, 'email'),
            'Telefon' => data_get($row, 'phone_number'),
            'Ort' => data_get($row, 'city'),
            'Wohnsitzland' => data_get($row, 'country_of_residence'),
            'Erstellt am' => $this->formatDateTimeOrNull(data_get($row, 'created_at')),
        ];
    }
}
