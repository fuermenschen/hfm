<?php

declare(strict_types=1);

namespace App\Components\Concerns;

use App\Support\Datatable\DatatableValueFormatter;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

trait InteractsWithDatatable
{
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 10)]
    public int $perPage = 10;

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    /**
     * @var array<int, int>
     */
    public array $checkboxValues = [];

    /**
     * @var array<int, string>
     */
    public array $visibleColumns = [];

    public ?int $editingId = null;

    public bool $editModalOpen = false;

    public bool $createModalOpen = false;

    public ?int $deletingId = null;

    public ?string $deletingLabel = null;

    /**
     * @var array<string, mixed>
     */
    public array $editForm = [];

    /**
     * @var array<string, mixed>
     */
    public array $createForm = [];

    protected ?DatatableValueFormatter $datatableValueFormatter = null;

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
        return $this->valueFormatter()->truncate($value, $length);
    }

    public function fallbackText(?string $value, string $fallback = '-'): string
    {
        return $this->valueFormatter()->text($value, $fallback);
    }

    public function formatMoney(float|int|string|null $value, string $fallback = '-'): string
    {
        return $this->valueFormatter()->money($value, $fallback);
    }

    public function formatMoneyOrUnlimited(float|int|string|null $value, string $unlimitedLabel = 'unbegrenzt'): string
    {
        return $this->valueFormatter()->moneyOrUnlimited($value, $unlimitedLabel);
    }

    public function formatDate(mixed $value, string $fallback = '-'): string
    {
        return $this->valueFormatter()->date($value, $fallback);
    }

    public function formatDateOrNull(mixed $value): ?string
    {
        return $this->valueFormatter()->dateOrNull($value);
    }

    public function formatDateTime(mixed $value, string $fallback = '-'): string
    {
        return $this->valueFormatter()->dateTime($value, $fallback);
    }

    public function formatDateTimeOrNull(mixed $value): ?string
    {
        return $this->valueFormatter()->dateTimeOrNull($value);
    }

    protected function valueFormatter(): DatatableValueFormatter
    {
        return $this->datatableValueFormatter ??= resolve(DatatableValueFormatter::class);
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

    public function openCreate(): void
    {
        $this->ensureAuthenticated();

        if (! $this->canCreateRows()) {
            return;
        }

        $this->editingId = null;
        $this->createForm = $this->defaultCreateForm();
        $this->resetErrorBag();
        $this->createModalOpen = true;

        Flux::modal($this->createModalName())->show();
    }

    public function cancelCreate(): void
    {
        $this->createForm = [];
        $this->createModalOpen = false;
        $this->resetErrorBag();

        Flux::modal($this->createModalName())->close();
    }

    public function saveCreate(): void
    {
        $this->ensureAuthenticated();

        if (! $this->canCreateRows()) {
            return;
        }

        $validated = $this->validate($this->createRules(), [], $this->createValidationAttributes());
        $formData = data_get($validated, 'createForm', []);

        if (! is_array($formData)) {
            $formData = [];
        }

        $this->createRecord($formData);
        $this->cancelCreate();

        Flux::toast(heading: 'Erstellt', text: 'Eintrag wurde erstellt.', variant: 'success');
    }

    public function confirmDeleteRow(int $id): void
    {
        $this->ensureAuthenticated();

        if (! $this->canDeleteRows()) {
            return;
        }

        $record = $this->deletableRecord($id);

        $this->deletingId = (int) $record->getKey();
        $this->deletingLabel = $this->deleteLabel($record);

        Flux::modal($this->deleteModalName())->show();
    }

    public function cancelDeleteRow(): void
    {
        $this->deletingId = null;
        $this->deletingLabel = null;

        Flux::modal($this->deleteModalName())->close();
    }

    public function deleteRow(): void
    {
        $this->ensureAuthenticated();

        if (! $this->canDeleteRows() || $this->deletingId === null) {
            return;
        }

        try {
            $this->deleteRecord($this->deletableRecord($this->deletingId));
        } catch (\RuntimeException $runtimeException) {
            $this->addError('deletingId', $runtimeException->getMessage());
            Flux::toast(heading: 'Nicht gelöscht', text: $runtimeException->getMessage(), variant: 'danger');

            return;
        }

        $this->cancelDeleteRow();

        Flux::toast(heading: 'Gelöscht', text: 'Eintrag wurde gelöscht.', variant: 'success');
    }

    public function canCreateRows(): bool
    {
        return false;
    }

    public function canDeleteRows(): bool
    {
        return false;
    }

    public function createModalName(): string
    {
        return Str::kebab(class_basename(static::class)).'-create';
    }

    public function deleteModalName(): string
    {
        return Str::kebab(class_basename(static::class)).'-delete';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultCreateForm(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createRules(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function createValidationAttributes(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createRecord(array $data): Model
    {
        throw new \LogicException(static::class.' must define createRecord() to create rows.');
    }

    protected function deletableRecord(int $id): Model
    {
        return $this->editableRecord($id);
    }

    protected function deleteLabel(Model $record): string
    {
        return (string) ($record->getAttribute('name') ?? '#'.$record->getKey());
    }

    protected function deleteRecord(Model $record): void
    {
        $record->delete();
    }

    public function openEdit(int $id): void
    {
        $this->ensureAuthenticated();

        if (! $this->canEditRows()) {
            return;
        }

        $record = $this->editableRecord($id);

        $this->editingId = (int) $record->getKey();
        $this->resetErrorBag();
        $this->fillEditForm($record);
        $this->editModalOpen = true;

        Flux::modal($this->editModalName())->show();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editForm = [];
        $this->editModalOpen = false;
        $this->resetErrorBag();

        Flux::modal($this->editModalName())->close();
    }

    public function saveEdit(): void
    {
        $this->ensureAuthenticated();

        if (! $this->canEditRows() || $this->editingId === null) {
            return;
        }

        $validated = $this->validate($this->editRules(), [], $this->editValidationAttributes());
        $formData = data_get($validated, 'editForm', []);

        if (! is_array($formData)) {
            $formData = [];
        }

        $this->saveEditedRecord($this->editableRecord($this->editingId), $formData);

        $this->cancelEdit();

        Flux::toast(heading: 'Gespeichert', text: 'Eintrag wurde aktualisiert.', variant: 'success');
    }

    public function canEditRows(): bool
    {
        return false;
    }

    public function editModalName(): string
    {
        return Str::kebab(class_basename(static::class)).'-edit';
    }

    protected function editableRecord(int $id): Model
    {
        throw new \LogicException(static::class.' must define editableRecord() to edit rows.');
    }

    protected function fillEditForm(Model $record): void
    {
        throw new \LogicException(static::class.' must define fillEditForm() to edit rows.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function editRules(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function editValidationAttributes(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveEditedRecord(Model $record, array $data): void
    {
        throw new \LogicException(static::class.' must define saveEditedRecord() to edit rows.');
    }

    protected function ensureAuthenticated(): void
    {
        abort_unless(Auth::check(), 403);
    }

    /**
     * @param  array<int, array<string, scalar|null>>  $rows
     */
    protected function exportRowsToDownload(array $rows, string $filePrefix, string $format): ?HttpResponse
    {
        $this->ensureAuthenticated();

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
        $relativePath = sprintf('tmp/%s_%s.%s', $filePrefix, $timestamp, $format);
        $absolutePath = Storage::disk('local')->path($relativePath);

        $writer = SimpleExcelWriter::create($absolutePath);
        $writer->addRows($rows);
        $writer->close();

        return response()->download($absolutePath, sprintf('%s_%s.%s', $filePrefix, $timestamp, $format))->deleteFileAfterSend(true);
    }
}
