<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\DownloadAthleteDocumentAction;
use App\Actions\DownloadAthleteDocumentArchiveAction;
use App\Enums\AthleteDocumentType;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Services\AthleteService;
use App\Services\CurrentDonationEventService;
use App\Services\DonorService;
use Closure;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminPersonTable extends AbstractDatatableComponent
{
    public string $sortField = 'first_name';

    #[Locked]
    public string $role = '';

    #[Url(as: 'anlass', except: '')]
    public ?string $eventSlug = '';

    protected AthleteService $athleteService;

    protected DonorService $donorService;

    protected CurrentDonationEventService $currentDonationEventService;

    protected DownloadAthleteDocumentAction $downloadAthleteDocumentAction;

    protected DownloadAthleteDocumentArchiveAction $downloadAthleteDocumentArchiveAction;

    public function boot(
        AthleteService $athleteService,
        DonorService $donorService,
        CurrentDonationEventService $currentDonationEventService,
        DownloadAthleteDocumentAction $downloadAthleteDocumentAction,
        DownloadAthleteDocumentArchiveAction $downloadAthleteDocumentArchiveAction,
    ): void {
        $this->athleteService = $athleteService;
        $this->donorService = $donorService;
        $this->currentDonationEventService = $currentDonationEventService;
        $this->downloadAthleteDocumentAction = $downloadAthleteDocumentAction;
        $this->downloadAthleteDocumentArchiveAction = $downloadAthleteDocumentArchiveAction;
    }

    public function mount(string $role = ''): void
    {
        throw_unless(in_array($role, ['athlete', 'donor'], true), \InvalidArgumentException::class, 'Invalid person role.');

        $this->role = $role;

        if (! request()->query->has('anlass') && ($this->eventSlug === null || $this->eventSlug === '')) {
            $currentEvent = $this->currentDonationEventService->current();
            $this->eventSlug = $currentEvent instanceof DonationEvent ? $currentEvent->slug : '';
        }

        parent::mount();
    }

    protected function tableView(): string
    {
        return 'components.admin.tables.person-table';
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
        if ($this->role === 'athlete') {
            return $this->athleteService->all()->with('athleteRegistrations.donationEvent');
        }

        return $this->donorService->all()->with('donationsAsDonor.athleteRegistration.donationEvent');
    }

    protected function applyFilters(Builder $query): void
    {
        if ($this->eventSlug === null || $this->eventSlug === '') {
            return;
        }

        if ($this->role === 'athlete') {
            $query->whereHas('athleteRegistrations.donationEvent', fn (Builder $event): Builder => $event->where('slug', $this->eventSlug));

            return;
        }

        $query->whereHas('donationsAsDonor.athleteRegistration.donationEvent', fn (Builder $event): Builder => $event->where('slug', $this->eventSlug));
    }

    protected function tableFilterProperties(): array
    {
        return ['eventSlug'];
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
            'events' => ['label' => 'Anlässe', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Anlässe'],
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
            'events',
        ];
    }

    /**
     * @return Collection<int, DonationEvent>
     */
    public function linkedEvents(ExternalUser $person): Collection
    {
        $events = $this->role === 'athlete'
            ? $person->athleteRegistrations->pluck('donationEvent')
            : $person->donationsAsDonor->pluck('athleteRegistration.donationEvent');

        return $events
            ->filter(fn (mixed $event): bool => $event instanceof DonationEvent)
            ->unique('id')
            ->sortByDesc('starts_at')
            ->values();
    }

    public function roleLabel(): string
    {
        return $this->role === 'athlete' ? 'Sportler:innen' : 'Spender:innen';
    }

    protected function tableViewData(LengthAwarePaginator $paginator): array
    {
        return [
            'events' => DonationEvent::query()->latest('starts_at')->get(['id', 'title', 'slug', 'is_published']),
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

        return $this->exportRowsToDownload($rows, $this->exportPrefix().'_gesamt', $format);
    }

    public function exportSelected(string $format): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine Zeile aus.');

            return null;
        }

        $rows = [];

        foreach ($this->queryForTable(ignoreSearch: true)->whereKey($selectedIds)->orderBy('id')->get() as $row) {
            $rows[] = $this->exportRow($row);
        }

        return $this->exportRowsToDownload($rows, $this->exportPrefix().'_auswahl', $format);
    }

    public function documentDownloadsEnabled(): bool
    {
        return $this->role === 'athlete' && $this->eventSlug !== null && $this->eventSlug !== '';
    }

    public function downloadAthleteDocument(int $externalUserId, string $type): ?HttpResponse
    {
        $event = $this->documentEvent();
        $documentType = $this->documentType($type);

        if (! $event instanceof DonationEvent || ! $documentType instanceof AthleteDocumentType) {
            return null;
        }

        try {
            return $this->withDocumentDownloadLock(fn (): HttpResponse => ($this->downloadAthleteDocumentAction)($event, $externalUserId, $documentType));
        } catch (ModelNotFoundException|\InvalidArgumentException $exception) {
            $this->toastDocumentError($exception->getMessage());

            return null;
        }
    }

    public function downloadAllAthleteDocuments(string $type): ?HttpResponse
    {
        return $this->downloadAthleteDocumentArchive($type);
    }

    public function downloadSelectedAthleteDocuments(string $type): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine Sportler:in aus.');

            return null;
        }

        return $this->downloadAthleteDocumentArchive($type, $selectedIds);
    }

    /**
     * @param  array<int, int>|null  $externalUserIds
     */
    protected function downloadAthleteDocumentArchive(string $type, ?array $externalUserIds = null): ?HttpResponse
    {
        $event = $this->documentEvent();
        $documentType = $this->documentType($type);

        if (! $event instanceof DonationEvent || ! $documentType instanceof AthleteDocumentType) {
            return null;
        }

        try {
            return $this->withDocumentDownloadLock(fn (): HttpResponse => ($this->downloadAthleteDocumentArchiveAction)($event, $documentType, $externalUserIds));
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->toastDocumentError($invalidArgumentException->getMessage());

            return null;
        }
    }

    /**
     * @param  Closure():HttpResponse  $download
     */
    protected function withDocumentDownloadLock(Closure $download): ?HttpResponse
    {
        $lock = Cache::lock('admin-athlete-document-download:'.Auth::id(), 600);

        if (! $lock->get()) {
            Flux::toast(
                heading: 'Dokumente werden bereits erstellt',
                text: 'Bitte warte, bis der aktuelle Download abgeschlossen ist.',
                variant: 'warning',
            );

            return null;
        }

        try {
            return $download();
        } finally {
            $lock->release();
        }
    }

    protected function documentEvent(): ?DonationEvent
    {
        $this->ensureAuthenticated();

        if (! $this->documentDownloadsEnabled()) {
            Flux::toast(
                heading: 'Anlass auswählen',
                text: 'Dokumente können nur für einen ausgewählten Anlass erstellt werden.',
                variant: 'warning',
            );

            return null;
        }

        $event = DonationEvent::query()->where('slug', $this->eventSlug)->first();

        if ($event instanceof DonationEvent) {
            return $event;
        }

        Flux::toast(
            heading: 'Anlass nicht gefunden',
            text: 'Der ausgewählte Anlass ist nicht mehr verfügbar.',
            variant: 'danger',
        );

        return null;
    }

    protected function documentType(string $type): ?AthleteDocumentType
    {
        $documentType = AthleteDocumentType::tryFrom($type);

        if ($documentType instanceof AthleteDocumentType) {
            return $documentType;
        }

        Flux::toast(
            heading: 'Ungültiges Dokument',
            text: 'Dieser Dokumenttyp ist nicht verfügbar.',
            variant: 'danger',
        );

        return null;
    }

    protected function toastDocumentError(string $text): void
    {
        Flux::toast(heading: 'Dokumente nicht erstellt', text: $text, variant: 'danger');
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
            'Anlässe' => $row instanceof ExternalUser ? $this->linkedEvents($row)->pluck('slug')->implode(', ') : '',
        ];
    }

    protected function exportPrefix(): string
    {
        return $this->role === 'athlete' ? 'sportler-innen' : 'spender-innen';
    }
}
