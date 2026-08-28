<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\DeleteFaqAction;
use App\Models\DonationEvent;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminFaqTable extends AbstractDatatableComponent
{
    public string $sortField = 'title';

    protected function tableView(): string
    {
        return 'components.admin.tables.faq-table';
    }

    protected function tableDataKey(): string
    {
        return 'faqs';
    }

    /**
     * @return array<int|string, string>
     */
    protected function searchableColumns(): array
    {
        return [
            'title',
            'content_md',
        ];
    }

    protected function baseQuery(): Builder
    {
        return Faq::query()->with('donationEvents');
    }

    /**
     * @return Collection<int, DonationEvent>
     */
    public function linkedEvents(Faq $faq): Collection
    {
        return $faq->donationEvents->sortByDesc('starts_at')->values();
    }

    protected function defaultSortColumn(): string
    {
        return 'faqs.title';
    }

    /**
     * @return array<string, string>
     */
    protected function sortColumns(): array
    {
        return [
            'title' => 'faqs.title',
            'created_at' => 'faqs.created_at',
            'id' => 'faqs.id',
        ];
    }

    /**
     * @return array<string, array{label:string, sortable:bool, sort_field?:string, align?:string, width?:string, tooltip?:bool, truncate?:int, export_key?:string, formatter?:string}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => ['label' => 'ID', 'sortable' => true, 'align' => 'right', 'width' => 'min-w-28', 'export_key' => 'ID'],
            'title' => ['label' => 'Titel', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-52', 'export_key' => 'Titel'],
            'content_md' => ['label' => 'Inhalt', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-64', 'export_key' => 'Inhalt', 'tooltip' => true, 'truncate' => 60],
            'events' => ['label' => 'Anlässe', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Anlässe'],
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
            'title',
            'content_md',
            'events',
            'created_at',
        ];
    }

    public function canDeleteRows(): bool
    {
        return true;
    }

    protected function deleteRecord(Model $record): void
    {
        throw_unless($record instanceof Faq, \LogicException::class, 'Expected faq record.');

        resolve(DeleteFaqAction::class)->handle($record);
    }

    protected function deleteLabel(Model $record): string
    {
        return $record instanceof Faq && filled($record->title)
            ? $record->title
            : '#'.$record->getKey();
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

        return $this->exportRowsToDownload($rows, 'faqs_gesamt', $format);
    }

    public function exportSelected(string $format): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens einen FAQ-Eintrag aus.');

            return null;
        }

        $rows = [];

        foreach ($this->baseQuery()->whereKey($selectedIds)->orderBy('id')->get() as $row) {
            $rows[] = $this->exportRow($row);
        }

        return $this->exportRowsToDownload($rows, 'faqs_auswahl', $format);
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(mixed $row): array
    {
        return [
            'ID' => data_get($row, 'id'),
            'Titel' => data_get($row, 'title'),
            'Inhalt' => data_get($row, 'content_md'),
            'Anlässe' => $row instanceof Faq ? $this->linkedEvents($row)->pluck('slug')->implode(', ') : '',
            'Erstellt am' => $this->formatDateOrNull(data_get($row, 'created_at')),
            'Aktualisiert am' => $this->formatDateOrNull(data_get($row, 'updated_at')),
        ];
    }
}
