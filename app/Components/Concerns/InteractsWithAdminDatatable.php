<?php

namespace App\Components\Concerns;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

trait InteractsWithAdminDatatable
{
    public string $search = '';

    public int $perPage = 10;

    public string $sortDirection = 'asc';

    /**
     * @var array<int, int>
     */
    public array $checkboxValues = [];

    /**
     * @var array<int, string>
     */
    public array $visibleColumns = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * @param  array<int, int>  $pageIds
     */
    public function toggleSelectPage(array $pageIds): void
    {
        if ($pageIds === []) {
            return;
        }

        $pageIds = array_map('intval', $pageIds);
        $selected = $this->selectedIds();
        $isEveryPageRowSelected = count(array_intersect($pageIds, $selected)) === count($pageIds);

        if ($isEveryPageRowSelected) {
            $this->checkboxValues = array_values(array_diff($selected, $pageIds));

            return;
        }

        $this->checkboxValues = array_values(array_unique(array_merge($selected, $pageIds)));
    }

    /**
     * @return array<int, int>
     */
    protected function selectedIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->checkboxValues)));
    }

    public function clearSelection(): void
    {
        $this->checkboxValues = [];
    }

    public function selectedCount(): int
    {
        return count($this->selectedIds());
    }

    public function tableLoadingTargets(): string
    {
        return 'search,sortField,sortDirection,perPage,nextPage,previousPage,gotoPage,setPage,toggleColumn';
    }

    /**
     * @return array<string, string>
     */
    public function visibleColumnOptions(): array
    {
        return collect($this->columnDefinitions())
            ->mapWithKeys(fn (array $definition, string $key): array => [$key => $definition['label']])
            ->all();
    }

    /**
     * @return array<string, array{
     *     label:string,
     *     sortable:bool,
     *     sort_field?:string,
     *     align?:string,
     *     width?:string,
     *     tooltip?:bool,
     *     truncate?:int,
     *     export_key?:string,
     *     formatter?:string
     * }>
     */
    abstract protected function columnDefinitions(): array;

    /**
     * @return array<string, array{
     *     label:string,
     *     sortable:bool,
     *     sort_field:?string,
     *     align:string,
     *     width:?string,
     *     tooltip:bool,
     *     truncate:?int,
     *     export_key:?string,
     *     formatter:?string
     * }>
     */
    public function visibleColumnDefinitions(): array
    {
        $definitions = $this->columnDefinitions();
        $visibleColumnKeys = array_values(array_filter(
            $this->visibleColumns,
            static fn (string $column): bool => array_key_exists($column, $definitions),
        ));

        $visibleDefinitions = [];

        foreach ($visibleColumnKeys as $column) {
            $definition = $definitions[$column];

            $visibleDefinitions[$column] = [
                'label' => (string) $definition['label'],
                'sortable' => (bool) $definition['sortable'],
                'sort_field' => isset($definition['sort_field']) ? (string) $definition['sort_field'] : null,
                'align' => isset($definition['align']) ? (string) $definition['align'] : 'left',
                'width' => isset($definition['width']) ? (string) $definition['width'] : null,
                'tooltip' => (bool) ($definition['tooltip'] ?? false),
                'truncate' => isset($definition['truncate']) ? (int) $definition['truncate'] : null,
                'export_key' => isset($definition['export_key']) ? (string) $definition['export_key'] : null,
                'formatter' => isset($definition['formatter']) ? (string) $definition['formatter'] : null,
            ];
        }

        return $visibleDefinitions;
    }

    public function toggleColumn(string $column): void
    {
        $allowedColumns = array_keys($this->columnDefinitions());

        if (! in_array($column, $allowedColumns, true)) {
            return;
        }

        if ($this->isColumnVisible($column)) {
            if (count($this->visibleColumns) === 1) {
                Flux::toast(
                    heading: 'Mindestens eine Spalte',
                    text: 'Mindestens eine optionale Spalte muss sichtbar bleiben.',
                    variant: 'warning',
                );

                return;
            }

            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $item): bool => $item !== $column,
            ));
        } else {
            $this->visibleColumns[] = $column;
            $this->visibleColumns = array_values(array_unique($this->visibleColumns));
        }

        $this->persistVisibleColumns();
    }

    public function isColumnVisible(string $column): bool
    {
        return in_array($column, $this->visibleColumns, true);
    }

    protected function persistVisibleColumns(): void
    {
        session([$this->visibleColumnsSessionKey() => $this->visibleColumns]);
    }

    protected function visibleColumnsSessionKey(): string
    {
        $userId = Auth::id() ?? 'guest';

        return 'datatable.visible_columns.'.$userId.'.'.static::class;
    }

    public function truncateText(?string $value, int $length = 42): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return '-';
        }

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - 1).'…';
    }

    public function sortByColumn(string $column): void
    {
        if (! $this->isColumnSortable($column)) {
            return;
        }

        $sortField = (string) data_get($this->columnDefinitions(), $column.'.sort_field', $column);

        if ($sortField === '') {
            return;
        }

        $this->sortBy($sortField);
    }

    public function isColumnSortable(string $column): bool
    {
        return (bool) data_get($this->columnDefinitions(), $column.'.sortable', false);
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }

    public function sortIndicator(string $column): string
    {
        $sortField = $this->columnSortField($column);

        if ($sortField === null || $this->sortField !== $sortField) {
            return 'none';
        }

        return $this->sortDirection === 'asc' ? 'asc' : 'desc';
    }

    public function columnSortField(string $column): ?string
    {
        if (! $this->isColumnSortable($column)) {
            return null;
        }

        $sortField = (string) data_get($this->columnDefinitions(), $column.'.sort_field', $column);

        return $sortField !== '' ? $sortField : null;
    }

    protected function initializeVisibleColumns(): void
    {
        $defaultColumns = $this->defaultVisibleColumns();
        $allowedColumns = array_keys($this->columnDefinitions());
        $sessionColumns = session($this->visibleColumnsSessionKey(), $defaultColumns);

        if (! is_array($sessionColumns)) {
            $sessionColumns = $defaultColumns;
        }

        $this->visibleColumns = array_values(array_intersect($allowedColumns, $sessionColumns));

        if ($this->visibleColumns === []) {
            $this->visibleColumns = array_values(array_intersect($allowedColumns, $defaultColumns));
        }

        $this->persistVisibleColumns();
    }

    /**
     * @return array<int, string>
     */
    abstract protected function defaultVisibleColumns(): array;

    protected function toastNoSelection(string $text): void
    {
        Flux::toast(
            heading: 'Keine Auswahl',
            text: $text,
            variant: 'warning',
        );
    }

    /**
     * @param  array<int, array<string, scalar|null>>  $rows
     */
    protected function exportRowsToDownload(array $rows, string $filePrefix, string $format): ?HttpResponse
    {
        if (! in_array($format, ['xlsx', 'csv'], true)) {
            Flux::toast(
                heading: 'Ungültiges Format',
                text: 'Export ist nur als Excel oder CSV verfügbar.',
                variant: 'danger',
            );

            return null;
        }

        if ($rows === []) {
            Flux::toast(
                heading: 'Keine Daten',
                text: 'Für diesen Export sind keine Daten vorhanden.',
                variant: 'warning',
            );

            return null;
        }

        Storage::disk('local')->makeDirectory('tmp');

        $timestamp = now()->format('Ymd_His');
        $relativePath = "tmp/{$filePrefix}_{$timestamp}.{$format}";
        $absolutePath = Storage::disk('local')->path($relativePath);

        $writer = SimpleExcelWriter::create($absolutePath);
        $writer->addRows($rows);
        $writer->close();

        return response()->download($absolutePath, "{$filePrefix}_{$timestamp}.{$format}")->deleteFileAfterSend(true);
    }
}
