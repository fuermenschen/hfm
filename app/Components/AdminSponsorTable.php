<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\DeleteSponsorAction;
use App\Models\Sponsor;
use App\Support\AdminFiles\AdminFileStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
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

    public function canEditRows(): bool
    {
        return true;
    }

    public function canCreateRows(): bool
    {
        return true;
    }

    public function canDeleteRows(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultCreateForm(): array
    {
        return [
            'name' => '',
            'description' => null,
            'logo_filename' => '',
            'url' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createRules(): array
    {
        $logoPaths = $this->sponsorLogoPaths();

        return [
            'createForm.name' => ['required', 'string', 'max:255', Rule::unique('sponsors', 'name')],
            'createForm.description' => ['nullable', 'string'],
            'createForm.logo_filename' => ['required', 'string', 'max:255', Rule::in($logoPaths)],
            'createForm.url' => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function createValidationAttributes(): array
    {
        return [
            'createForm.name' => 'Name',
            'createForm.description' => 'Beschreibung',
            'createForm.logo_filename' => 'Logo',
            'createForm.url' => 'URL',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createRecord(array $data): Model
    {
        return Sponsor::query()->create([
            'name' => $data['name'],
            'description' => $this->nullableString($data['description'] ?? null),
            'logo_filename' => trim((string) $data['logo_filename']),
            'url' => $this->nullableString($data['url'] ?? null),
        ]);
    }

    protected function editableRecord(int $id): Model
    {
        return Sponsor::query()->findOrFail($id);
    }

    protected function fillEditForm(Model $record): void
    {
        throw_unless($record instanceof Sponsor, \LogicException::class, 'Expected sponsor record.');

        $this->editForm = [
            'name' => $record->name,
            'description' => $record->description,
            'logo_filename' => $record->logo_filename,
            'url' => $record->url,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function editRules(): array
    {
        $logoPaths = $this->sponsorLogoPaths();

        return [
            'editForm.name' => ['required', 'string', 'max:255', Rule::unique('sponsors', 'name')->ignore($this->editingId)],
            'editForm.description' => ['nullable', 'string'],
            'editForm.logo_filename' => ['required', 'string', 'max:255', Rule::in($this->allowedSponsorLogoPaths($logoPaths))],
            'editForm.url' => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function editValidationAttributes(): array
    {
        return [
            'editForm.name' => 'Name',
            'editForm.description' => 'Beschreibung',
            'editForm.logo_filename' => 'Logo',
            'editForm.url' => 'URL',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveEditedRecord(Model $record, array $data): void
    {
        throw_unless($record instanceof Sponsor, \LogicException::class, 'Expected sponsor record.');

        $record->fill([
            'name' => $data['name'],
            'description' => $this->nullableString($data['description'] ?? null),
            'logo_filename' => trim((string) $data['logo_filename']),
            'url' => $this->nullableString($data['url'] ?? null),
        ])->save();
    }

    protected function deleteRecord(Model $record): void
    {
        throw_unless($record instanceof Sponsor, \LogicException::class, 'Expected sponsor record.');

        resolve(DeleteSponsorAction::class)->handle($record);
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<int, string>
     */
    protected function sponsorLogoPaths(): array
    {
        return collect(resolve(AdminFileStorage::class)->files('sponsors', recursive: true, extensions: ['svg', 'png', 'jpg', 'jpeg', 'webp']))
            ->pluck('path')
            ->map(fn (string $path): string => str($path)->after('sponsors/')->toString())
            ->all();
    }

    /**
     * @param  array<int, string>  $logoPaths
     * @return array<int, string>
     */
    protected function allowedSponsorLogoPaths(array $logoPaths): array
    {
        $currentPath = $this->editingId === null
            ? null
            : $this->nullableString(Sponsor::query()->whereKey($this->editingId)->value('logo_filename'));

        return array_values(array_unique(array_filter([...$logoPaths, $currentPath])));
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
