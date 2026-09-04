<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\DownloadAthleteDocumentAction;
use App\Actions\DownloadAthleteDocumentArchiveAction;
use App\Actions\DownloadAthleteStoryImageArchiveAction;
use App\Enums\AthleteDocumentType;
use App\Models\AthleteRegistration;
use App\Models\Donation;
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
    #[Url]
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

    protected DownloadAthleteStoryImageArchiveAction $downloadAthleteStoryImageArchiveAction;

    public function boot(
        AthleteService $athleteService,
        DonorService $donorService,
        CurrentDonationEventService $currentDonationEventService,
        DownloadAthleteDocumentAction $downloadAthleteDocumentAction,
        DownloadAthleteDocumentArchiveAction $downloadAthleteDocumentArchiveAction,
        DownloadAthleteStoryImageArchiveAction $downloadAthleteStoryImageArchiveAction,
    ): void {
        $this->athleteService = $athleteService;
        $this->donorService = $donorService;
        $this->currentDonationEventService = $currentDonationEventService;
        $this->downloadAthleteDocumentAction = $downloadAthleteDocumentAction;
        $this->downloadAthleteDocumentArchiveAction = $downloadAthleteDocumentArchiveAction;
        $this->downloadAthleteStoryImageArchiveAction = $downloadAthleteStoryImageArchiveAction;
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
            'public_id',
            'athleteRegistrations.partner.name',
        ];
    }

    protected function baseQuery(): Builder
    {
        if ($this->role === 'athlete') {
            $query = $this->athleteService->all()->with([
                'athleteRegistrations.donationEvent',
                'athleteRegistrations.partner',
                'athleteRegistrations.eventGroup',
            ]);
            $partnerQuery = AthleteRegistration::query()
                ->select('partners.name')
                ->join('partners', 'partners.id', '=', 'athlete_registrations.partner_id')
                ->whereColumn('athlete_registrations.external_user_id', 'external_users.id')
                ->limit(1);

            if ($this->eventSlug !== null && $this->eventSlug !== '') {
                $partnerQuery->whereHas('donationEvent', fn (Builder $event): Builder => $event->where('slug', $this->eventSlug));
            }

            $registrationQuery = AthleteRegistration::query()
                ->select('athlete_registrations.created_at')
                ->whereColumn('athlete_registrations.external_user_id', 'external_users.id')
                ->limit(1);

            if ($this->eventSlug !== null && $this->eventSlug !== '') {
                $registrationQuery->whereHas('donationEvent', fn (Builder $event): Builder => $event->where('slug', $this->eventSlug));
            }

            $donationCountQuery = Donation::query()
                ->selectRaw('count(*)')
                ->whereHas('athleteRegistration', function (Builder $registration): void {
                    $registration->whereColumn('external_user_id', 'external_users.id');

                    if ($this->eventSlug !== null && $this->eventSlug !== '') {
                        $registration->whereHas('donationEvent', fn (Builder $event): Builder => $event->where('slug', $this->eventSlug));
                    }
                });

            return $query->addSelect([
                'selected_partner_name' => $partnerQuery,
                'selected_registration_created_at' => $registrationQuery,
                'selected_donation_count' => $donationCountQuery,
            ]);
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
        $columns = [
            'first_name' => 'external_users.first_name',
            'last_name' => 'external_users.last_name',
            'email' => 'external_users.email',
        ];

        if ($this->role === 'athlete') {
            $columns['partner'] = 'selected_partner_name';
            $columns['registration_time'] = 'selected_registration_created_at';
        }

        return $columns;
    }

    /**
     * @return array<string, array{label:string, sortable:bool, sort_field?:string, align?:string, width?:string, tooltip?:bool, truncate?:int, export_key?:string, formatter?:string}>
     */
    protected function columnDefinitions(): array
    {
        $columns = [
            'first_name' => ['label' => 'Vorname', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Vorname'],
            'last_name' => ['label' => 'Nachname', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Nachname'],
        ];

        if ($this->role === 'athlete') {
            $columns['registration_time'] = ['label' => 'Anmeldedatum', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'Anmeldedatum', 'formatter' => 'date_time'];
            $columns['donation_count'] = ['label' => 'Anzahl Spenden', 'sortable' => false, 'align' => 'right', 'width' => 'min-w-32', 'export_key' => 'Anzahl Spenden'];
        }

        $columns += [
            'email' => ['label' => 'E-Mail', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-56', 'export_key' => 'E-Mail', 'tooltip' => true, 'truncate' => 52],
            'phone_number' => ['label' => 'Telefon', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Telefon'],
            'city' => ['label' => 'Ort', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Ort'],
            'country_of_residence' => ['label' => 'Wohnsitzland', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Wohnsitzland'],
        ];

        if ($this->role === 'athlete') {
            $columns['public_id_string'] = ['label' => 'Öffentliche ID', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-32', 'export_key' => 'Öffentliche ID'];
            $columns['partner'] = ['label' => 'Benefizpartner:in', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-48', 'export_key' => 'Benefizpartner:in'];
            $columns['group'] = ['label' => 'Gruppe', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Gruppe'];
            $columns['confirmed'] = ['label' => 'OK', 'sortable' => false, 'align' => 'center', 'width' => 'w-16 min-w-16', 'export_key' => 'OK'];
        }

        $columns['events'] = ['label' => 'Anlässe', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Anlässe'];

        return $columns;
    }

    /**
     * @return array<int, string>
     */
    protected function defaultVisibleColumns(): array
    {
        $columns = [
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'city',
            'events',
        ];

        if ($this->role === 'athlete') {
            array_splice($columns, 2, 0, ['registration_time', 'donation_count']);
            array_splice($columns, 7, 0, ['partner', 'group', 'confirmed']);
        }

        return $columns;
    }

    protected function visibleColumnsSessionKey(): string
    {
        return parent::visibleColumnsSessionKey().'.'.$this->role;
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

    public function selectedAthletePartner(ExternalUser $person): string
    {
        if ($this->role !== 'athlete' || $this->eventSlug === null || $this->eventSlug === '') {
            return '-';
        }

        $registration = $person->athleteRegistrations->first(
            fn (AthleteRegistration $registration): bool => $registration->donationEvent->slug === $this->eventSlug,
        );

        if (! $registration instanceof AthleteRegistration) {
            return '-';
        }

        return $registration->partner->name ?? __('app.equal_split_full');
    }

    public function selectedAthleteGroup(ExternalUser $person): string
    {
        $registration = $this->selectedAthleteRegistration($person);

        return $registration->eventGroup->name ?? '-';
    }

    public function selectedAthleteConfirmed(ExternalUser $person): ?bool
    {
        $registration = $this->selectedAthleteRegistration($person);

        return $registration->verified ?? null;
    }

    public function selectedRegistrationCreatedAt(ExternalUser $person): ?string
    {
        $registration = $this->selectedAthleteRegistration($person);

        return $registration?->created_at?->format('Y-m-d H:i:s');
    }

    public function selectedAthleteRegistration(ExternalUser $person): ?AthleteRegistration
    {
        if ($this->role !== 'athlete' || $this->eventSlug === null || $this->eventSlug === '') {
            return null;
        }

        $registration = $person->athleteRegistrations->first(
            fn (AthleteRegistration $registration): bool => $registration->donationEvent->slug === $this->eventSlug,
        );

        return $registration instanceof AthleteRegistration ? $registration : null;
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
        } catch (ModelNotFoundException) {
            $this->toastDocumentError('Die Sportler:in wurde im ausgewählten Anlass nicht gefunden.');

            return null;
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->toastDocumentError($invalidArgumentException->getMessage());

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

    public function downloadAllAthleteStoryImages(): ?HttpResponse
    {
        return $this->downloadAthleteStoryImageArchive();
    }

    public function downloadSelectedAthleteStoryImages(): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine Sportler:in aus.');

            return null;
        }

        return $this->downloadAthleteStoryImageArchive($selectedIds);
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
     * @param  array<int, int>|null  $externalUserIds
     */
    protected function downloadAthleteStoryImageArchive(?array $externalUserIds = null): ?HttpResponse
    {
        $event = $this->documentEvent();

        if (! $event instanceof DonationEvent) {
            return null;
        }

        try {
            return $this->withDocumentDownloadLock(fn (): HttpResponse => ($this->downloadAthleteStoryImageArchiveAction)($event, $externalUserIds));
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
        $export = [
            'Vorname' => data_get($row, 'first_name'),
            'Nachname' => data_get($row, 'last_name'),
            'E-Mail' => data_get($row, 'email'),
            'Telefon' => data_get($row, 'phone_number'),
            'Ort' => data_get($row, 'city'),
            'Wohnsitzland' => data_get($row, 'country_of_residence'),
            'Anlässe' => $row instanceof ExternalUser ? $this->linkedEvents($row)->pluck('slug')->implode(', ') : '',
        ];

        if ($this->role === 'athlete' && $row instanceof ExternalUser) {
            $export['Öffentliche ID'] = data_get($row, 'public_id_string');
            $export['Benefizpartner:in'] = $this->selectedAthletePartner($row);
            $export['Gruppe'] = $this->selectedAthleteGroup($row);

            $confirmed = $this->selectedAthleteConfirmed($row);
            $export['OK'] = $confirmed === null ? '-' : ($confirmed ? 'OK' : 'NOK');
            $export['Anmeldedatum'] = $this->selectedRegistrationCreatedAt($row) ?? '-';
            $export['Anzahl Spenden'] = (int) data_get($row, 'selected_donation_count', 0);
        }

        return $export;
    }

    protected function exportPrefix(): string
    {
        return $this->role === 'athlete' ? 'sportler-innen' : 'spender-innen';
    }
}
