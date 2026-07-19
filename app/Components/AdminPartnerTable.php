<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\DeletePartnerAction;
use App\Models\Partner;
use App\Support\AdminFiles\AdminFileStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminPartnerTable extends AbstractDatatableComponent
{
    public string $sortField = 'name';

    protected function tableView(): string
    {
        return 'components.admin.tables.partner-table';
    }

    protected function tableDataKey(): string
    {
        return 'partners';
    }

    /**
     * @return array<int|string, string>
     */
    protected function searchableColumns(): array
    {
        return [
            'name',
            'beneficiary_blurb',
            'url',
        ];
    }

    protected function baseQuery(): Builder
    {
        return Partner::query()->withCount('donationEvents');
    }

    protected function defaultSortColumn(): string
    {
        return 'partners.name';
    }

    /**
     * @return array<string, string>
     */
    protected function sortColumns(): array
    {
        return [
            'name' => 'partners.name',
            'donation_events_count' => 'donation_events_count',
            'created_at' => 'partners.created_at',
            'id' => 'partners.id',
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
            'logo_light_filename' => ['label' => 'Logo hell', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Logo hell'],
            'logo_dark_filename' => ['label' => 'Logo dunkel', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Logo dunkel'],
            'beneficiary_blurb' => ['label' => 'Kurztext', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-60', 'export_key' => 'Kurztext', 'tooltip' => true, 'truncate' => 48],
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
            'logo_light_filename',
            'logo_dark_filename',
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
            'logo_light_filename' => '',
            'logo_dark_filename' => '',
            'beneficiary_blurb' => '',
            'url' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createRules(): array
    {
        $logoPaths = $this->partnerLogoPaths();

        return [
            'createForm.name' => ['required', 'string', 'max:255', Rule::unique('partners', 'name')],
            'createForm.logo_light_filename' => ['required', 'string', 'max:255', Rule::in($logoPaths)],
            'createForm.logo_dark_filename' => ['required', 'string', 'max:255', Rule::in($logoPaths)],
            'createForm.beneficiary_blurb' => ['required', 'string'],
            'createForm.url' => ['required', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function createValidationAttributes(): array
    {
        return [
            'createForm.name' => 'Name',
            'createForm.logo_light_filename' => 'Logo hell',
            'createForm.logo_dark_filename' => 'Logo dunkel',
            'createForm.beneficiary_blurb' => 'Kurztext',
            'createForm.url' => 'URL',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createRecord(array $data): Model
    {
        return Partner::query()->create([
            'name' => $data['name'],
            'logo_light_filename' => trim((string) $data['logo_light_filename']),
            'logo_dark_filename' => trim((string) $data['logo_dark_filename']),
            'beneficiary_blurb' => trim((string) $data['beneficiary_blurb']),
            'url' => trim((string) $data['url']),
        ]);
    }

    protected function editableRecord(int $id): Model
    {
        return Partner::query()->findOrFail($id);
    }

    protected function fillEditForm(Model $record): void
    {
        throw_unless($record instanceof Partner, \LogicException::class, 'Expected partner record.');

        $this->editForm = [
            'name' => $record->name,
            'logo_light_filename' => $record->logo_light_filename,
            'logo_dark_filename' => $record->logo_dark_filename,
            'beneficiary_blurb' => $record->beneficiary_blurb,
            'url' => $record->url,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function editRules(): array
    {
        $logoPaths = $this->partnerLogoPaths();

        return [
            'editForm.name' => ['required', 'string', 'max:255', Rule::unique('partners', 'name')->ignore($this->editingId)],
            'editForm.logo_light_filename' => ['required', 'string', 'max:255', Rule::in($this->allowedPartnerLogoPaths($logoPaths, 'logo_light_filename'))],
            'editForm.logo_dark_filename' => ['required', 'string', 'max:255', Rule::in($this->allowedPartnerLogoPaths($logoPaths, 'logo_dark_filename'))],
            'editForm.beneficiary_blurb' => ['required', 'string'],
            'editForm.url' => ['required', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function editValidationAttributes(): array
    {
        return [
            'editForm.name' => 'Name',
            'editForm.logo_light_filename' => 'Logo hell',
            'editForm.logo_dark_filename' => 'Logo dunkel',
            'editForm.beneficiary_blurb' => 'Kurztext',
            'editForm.url' => 'URL',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveEditedRecord(Model $record, array $data): void
    {
        throw_unless($record instanceof Partner, \LogicException::class, 'Expected partner record.');

        $record->fill([
            'name' => $data['name'],
            'logo_light_filename' => trim((string) $data['logo_light_filename']),
            'logo_dark_filename' => trim((string) $data['logo_dark_filename']),
            'beneficiary_blurb' => trim((string) $data['beneficiary_blurb']),
            'url' => trim((string) $data['url']),
        ])->save();
    }

    protected function deleteRecord(Model $record): void
    {
        throw_unless($record instanceof Partner, \LogicException::class, 'Expected partner record.');

        resolve(DeletePartnerAction::class)->handle($record);
    }

    /**
     * @return array<int, string>
     */
    protected function partnerLogoPaths(): array
    {
        return collect(resolve(AdminFileStorage::class)->files('partners', recursive: true, extensions: ['svg', 'png', 'jpg', 'jpeg', 'webp']))
            ->pluck('path')
            ->map(fn (string $path): string => str($path)->after('partners/')->toString())
            ->all();
    }

    /**
     * @param  array<int, string>  $logoPaths
     * @return array<int, string>
     */
    protected function allowedPartnerLogoPaths(array $logoPaths, string $field): array
    {
        $currentPath = $this->editingId === null
            ? null
            : Partner::query()->whereKey($this->editingId)->value($field);

        return array_values(array_unique(is_string($currentPath) ? [...$logoPaths, $currentPath] : $logoPaths));
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

        return $this->exportRowsToDownload($rows, 'partner_gesamt', $format);
    }

    public function exportSelected(string $format): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine Partner:in aus.');

            return null;
        }

        $rows = [];

        foreach ($this->baseQuery()->whereKey($selectedIds)->orderBy('id')->get() as $row) {
            $rows[] = $this->exportRow($row);
        }

        return $this->exportRowsToDownload($rows, 'partner_auswahl', $format);
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(mixed $row): array
    {
        return [
            'ID' => data_get($row, 'id'),
            'Name' => data_get($row, 'name'),
            'Logo hell' => data_get($row, 'logo_light_filename'),
            'Logo dunkel' => data_get($row, 'logo_dark_filename'),
            'Kurztext' => data_get($row, 'beneficiary_blurb'),
            'URL' => data_get($row, 'url'),
            'Anlässe' => data_get($row, 'donation_events_count'),
            'Erstellt am' => $this->formatDateOrNull(data_get($row, 'created_at')),
            'Aktualisiert am' => $this->formatDateOrNull(data_get($row, 'updated_at')),
        ];
    }
}
