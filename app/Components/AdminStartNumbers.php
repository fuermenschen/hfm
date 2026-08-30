<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\AssignStartNumbersAction;
use App\Actions\ClearStartNumbersAction;
use App\Actions\SetAthleteEventStateAction;
use App\Actions\SetAthleteRoundsAction;
use App\Actions\SetStartNumberAction;
use App\Enums\EventState;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Services\CurrentDonationEventService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminStartNumbers extends AbstractDatatableComponent
{
    #[Url]
    public string $sortField = 'name';

    #[Url(as: 'anlass', except: '')]
    public ?string $eventSlug = '';

    public int $firstNumber = 1;

    public ?int $editingNumberId = null;

    #[Validate('required', message: 'Bitte gib eine Startnummer ein.')]
    #[Validate('integer', message: 'Die Startnummer muss eine Zahl sein.')]
    #[Validate('min:1', message: 'Die Startnummer muss mindestens 1 sein.')]
    #[Validate('max:65535', message: 'Die Startnummer darf höchstens 65535 sein.')]
    public ?int $numberInput = null;

    public ?int $editingRoundsId = null;

    #[Validate('required', message: 'Bitte gib die Runden ein.')]
    #[Validate('integer', message: 'Die Runden müssen eine Zahl sein.')]
    #[Validate('min:0', message: 'Die Runden dürfen nicht negativ sein.')]
    #[Validate('max:255', message: 'Die Runden dürfen höchstens 255 sein.')]
    public ?int $roundsInput = null;

    protected CurrentDonationEventService $currentDonationEventService;

    protected AssignStartNumbersAction $assignStartNumbersAction;

    protected SetStartNumberAction $setStartNumberAction;

    protected SetAthleteRoundsAction $setAthleteRoundsAction;

    protected SetAthleteEventStateAction $setAthleteEventStateAction;

    protected ClearStartNumbersAction $clearStartNumbersAction;

    public function boot(
        CurrentDonationEventService $currentDonationEventService,
        AssignStartNumbersAction $assignStartNumbersAction,
        SetStartNumberAction $setStartNumberAction,
        SetAthleteRoundsAction $setAthleteRoundsAction,
        SetAthleteEventStateAction $setAthleteEventStateAction,
        ClearStartNumbersAction $clearStartNumbersAction,
    ): void {
        $this->currentDonationEventService = $currentDonationEventService;
        $this->assignStartNumbersAction = $assignStartNumbersAction;
        $this->setStartNumberAction = $setStartNumberAction;
        $this->setAthleteRoundsAction = $setAthleteRoundsAction;
        $this->setAthleteEventStateAction = $setAthleteEventStateAction;
        $this->clearStartNumbersAction = $clearStartNumbersAction;
    }

    public function mount(): void
    {
        if (! request()->query->has('anlass') && ($this->eventSlug === null || $this->eventSlug === '')) {
            $currentEvent = $this->currentDonationEventService->current();
            $this->eventSlug = $currentEvent instanceof DonationEvent ? $currentEvent->slug : '';
        }

        parent::mount();

        $this->firstNumber = $this->nextFreeNumber();
    }

    public function updated(string $property): void
    {
        parent::updated($property);

        if ($property === 'eventSlug') {
            $this->firstNumber = $this->nextFreeNumber();
            $this->dispatch('anlass-changed', slug: $this->eventSlug ?? '');
        }
    }

    #[On('anlass-changed')]
    public function syncEventSlug(string $slug): void
    {
        if ($this->eventSlug === $slug) {
            return;
        }

        $this->eventSlug = $slug;
    }

    protected function tableView(): string
    {
        return 'components.admin.tables.start-numbers-table';
    }

    protected function tableDataKey(): string
    {
        return 'registrations';
    }

    /**
     * @return array<int|string, string>
     */
    protected function searchableColumns(): array
    {
        return [
            'externalUser.first_name',
            'externalUser.last_name',
            'externalUser.public_id',
            'start_number',
        ];
    }

    protected function baseQuery(): Builder
    {
        $event = $this->selectedEvent();

        if (! $event instanceof DonationEvent) {
            return AthleteRegistration::query()->whereRaw('0 = 1');
        }

        return AthleteRegistration::query()
            ->whereBelongsTo($event)
            ->whereHas('externalUser')
            ->join('external_users', 'external_users.id', '=', 'athlete_registrations.external_user_id')
            ->select('athlete_registrations.*')
            ->with('externalUser:id,first_name,last_name,public_id');
    }

    protected function queryForTable(bool $ignoreSearch): Builder
    {
        // Tiebreaker appended after the primary sort from the parent.
        return parent::queryForTable($ignoreSearch)->orderBy('external_users.last_name');
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
            'name' => 'external_users.first_name',
            'rounds_done' => 'athlete_registrations.rounds_done',
            'start_number' => 'athlete_registrations.start_number',
        ];
    }

    /**
     * @return array<string, array{label:string, sortable:bool, align?:string, width?:string, export_key?:string}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'start_number' => ['label' => 'Startnummer', 'sortable' => true, 'align' => 'center', 'width' => 'w-24', 'export_key' => 'Startnummer'],
            'name' => ['label' => 'Name', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-44', 'export_key' => 'Name'],
            'public_id' => ['label' => 'Öffentliche ID', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-32', 'export_key' => 'Öffentliche ID'],
            'event_state' => ['label' => 'Status', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-32', 'export_key' => 'Status'],
            'rounds_done' => ['label' => 'Runden', 'sortable' => true, 'align' => 'right', 'width' => 'w-20', 'export_key' => 'Runden'],
            'rounds_estimated' => ['label' => 'Geschätzt', 'sortable' => false, 'align' => 'right', 'width' => 'w-20', 'export_key' => 'Geschätzt'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function defaultVisibleColumns(): array
    {
        return ['start_number', 'name', 'public_id', 'event_state', 'rounds_done', 'rounds_estimated'];
    }

    protected function initializeVisibleColumns(): void
    {
        // Fixed column set: this table has no visibility toggle, so stale
        // per-user sessions must never hide columns.
        $this->visibleColumns = array_keys($this->columnDefinitions());
    }

    /**
     * @return array<string, mixed>
     */
    protected function tableViewData(LengthAwarePaginator $paginator): array
    {
        return [
            'events' => DonationEvent::query()->latest('starts_at')->get(['id', 'title', 'slug', 'is_published']),
        ];
    }

    public function nextFreeNumber(): int
    {
        $event = $this->selectedEvent();

        if (! $event instanceof DonationEvent) {
            return 1;
        }

        $max = (int) AthleteRegistration::query()
            ->whereBelongsTo($event)
            ->max('start_number');

        return max(1, $max + 1);
    }

    public function selectedEvent(): ?DonationEvent
    {
        if ($this->eventSlug === null || $this->eventSlug === '') {
            return null;
        }

        return DonationEvent::query()->where('slug', $this->eventSlug)->first();
    }

    public function confirmAssignAll(): void
    {
        $this->ensureAuthenticated();
        Flux::modal('start-numbers-assign-all')->show();
    }

    public function assignAll(): void
    {
        $this->runBatchAssignment(onlyMissing: false);
    }

    public function assignMissing(): void
    {
        $this->runBatchAssignment(onlyMissing: true);
    }

    public function assignNextNumber(int $registrationId): void
    {
        $this->ensureAuthenticated();

        $registration = $this->findRegistration($registrationId);

        if (! $registration instanceof AthleteRegistration) {
            return;
        }

        try {
            $number = $this->nextFreeNumber();
            ($this->setStartNumberAction)($registration, $number);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            Flux::toast(heading: 'Nicht vergeben', text: $invalidArgumentException->getMessage(), variant: 'danger');

            return;
        }

        $this->firstNumber = $this->nextFreeNumber();

        Flux::toast(heading: 'Startnummer vergeben', text: 'Startnummer '.$number.' wurde vergeben.', variant: 'success');
    }

    public function clearNumber(int $registrationId): void
    {
        $this->ensureAuthenticated();

        $registration = $this->findRegistration($registrationId);

        if (! $registration instanceof AthleteRegistration) {
            return;
        }

        ($this->setStartNumberAction)($registration, null);

        Flux::toast(heading: 'Startnummer entfernt', text: 'Die Startnummer wurde entfernt.', variant: 'success');
    }

    public function confirmClearAll(): void
    {
        $this->ensureAuthenticated();
        Flux::modal('start-numbers-confirm-clear')->show();
    }

    public function clearAllNumbers(): void
    {
        $this->ensureAuthenticated();

        $event = $this->selectedEvent();

        if (! $event instanceof DonationEvent) {
            Flux::toast(heading: 'Anlass auswählen', text: 'Bitte wähle einen Anlass aus.', variant: 'warning');

            return;
        }

        $cleared = ($this->clearStartNumbersAction)($event);

        Flux::modal('start-numbers-confirm-clear')->close();

        $this->firstNumber = $this->nextFreeNumber();

        if ($cleared === 0) {
            Flux::toast(heading: 'Nichts entfernt', text: 'Es gab keine Startnummern zum Entfernen.', variant: 'warning');

            return;
        }

        Flux::toast(heading: 'Startnummern entfernt', text: $cleared.' Startnummern wurden entfernt.', variant: 'success');
    }

    public function openNumberEditor(int $registrationId): void
    {
        $this->ensureAuthenticated();

        $registration = $this->findRegistration($registrationId);

        if (! $registration instanceof AthleteRegistration) {
            return;
        }

        $this->editingNumberId = $registration->getKey();
        $this->numberInput = $registration->start_number;
        $this->resetValidation();
        Flux::modal('start-numbers-set-number')->show();
    }

    public function setNumber(): void
    {
        $this->ensureAuthenticated();

        $registration = $this->editingNumberId === null ? null : $this->findRegistration($this->editingNumberId);

        if (! $registration instanceof AthleteRegistration) {
            return;
        }

        $this->validateOnly('numberInput');

        try {
            ($this->setStartNumberAction)($registration, $this->numberInput);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            Flux::toast(heading: 'Nicht gespeichert', text: $invalidArgumentException->getMessage(), variant: 'danger');

            return;
        }

        $this->reset('numberInput', 'editingNumberId');
        $this->resetValidation();
        Flux::modal('start-numbers-set-number')->close();

        Flux::toast(heading: 'Startnummer gespeichert', text: 'Die Startnummer wurde gespeichert.', variant: 'success');
    }

    public function openRoundsEditor(int $registrationId): void
    {
        $this->ensureAuthenticated();

        $registration = $this->findRegistration($registrationId);

        if (! $registration instanceof AthleteRegistration) {
            return;
        }

        $this->editingRoundsId = $registration->getKey();
        $this->roundsInput = $registration->rounds_done;
        $this->resetValidation();
        Flux::modal('start-numbers-set-rounds')->show();
    }

    public function setRounds(): void
    {
        $this->ensureAuthenticated();

        $registration = $this->editingRoundsId === null ? null : $this->findRegistration($this->editingRoundsId);

        if (! $registration instanceof AthleteRegistration) {
            return;
        }

        $this->validateOnly('roundsInput');

        try {
            ($this->setAthleteRoundsAction)($registration, (int) $this->roundsInput);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            Flux::toast(heading: 'Nicht gespeichert', text: $invalidArgumentException->getMessage(), variant: 'danger');

            return;
        }

        $this->reset('roundsInput', 'editingRoundsId');
        $this->resetValidation();
        Flux::modal('start-numbers-set-rounds')->close();

        Flux::toast(heading: 'Runden gespeichert', text: 'Die Runden wurden gespeichert.', variant: 'success');
    }

    public function setStatus(int $registrationId, string $stateValue): void
    {
        $this->ensureAuthenticated();

        $eventState = EventState::tryFrom($stateValue);

        if ($eventState === null) {
            return;
        }

        $registration = $this->findRegistration($registrationId);

        if (! $registration instanceof AthleteRegistration) {
            return;
        }

        ($this->setAthleteEventStateAction)($registration, $eventState);

        Flux::toast(heading: 'Status gesetzt', text: 'Der Status wurde auf «'.$eventState->label().'» gesetzt.', variant: 'success');
    }

    public function exportAll(string $format): ?HttpResponse
    {
        $rows = [];

        foreach ($this->queryForTable(ignoreSearch: true)->get() as $row) {
            $rows[] = $this->exportRow($row);
        }

        return $this->exportRowsToDownload($rows, 'startnummern', $format);
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

        return $this->exportRowsToDownload($rows, 'startnummern_auswahl', $format);
    }

    protected function runBatchAssignment(bool $onlyMissing): void
    {
        $this->ensureAuthenticated();

        $event = $this->selectedEvent();

        if (! $event instanceof DonationEvent) {
            Flux::toast(heading: 'Anlass auswählen', text: 'Bitte wähle einen Anlass aus.', variant: 'warning');

            return;
        }

        try {
            $assigned = ($this->assignStartNumbersAction)($event, $this->firstNumber, $onlyMissing);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            Flux::toast(heading: 'Nicht vergeben', text: $invalidArgumentException->getMessage(), variant: 'danger');

            return;
        }

        Flux::modal('start-numbers-assign-all')->close();

        $this->firstNumber = $this->nextFreeNumber();

        if ($assigned === 0) {
            Flux::toast(heading: 'Keine Startnummern vergeben', text: 'Es gab keine Anmeldungen ohne Startnummer.', variant: 'warning');

            return;
        }

        Flux::toast(heading: 'Startnummern vergeben', text: $assigned.' Startnummern wurden vergeben.', variant: 'success');
    }

    protected function findRegistration(int $registrationId): ?AthleteRegistration
    {
        $registration = $this->baseQuery()->find($registrationId);

        return $registration instanceof AthleteRegistration ? $registration : null;
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(mixed $row): array
    {
        if (! $row instanceof AthleteRegistration) {
            return [
                'Startnummer' => null,
                'Name' => '',
                'Öffentliche ID' => '',
                'Status' => '',
                'Runden' => 0,
                'Geschätzt' => 0,
            ];
        }

        return [
            'Startnummer' => $row->start_number,
            'Name' => $row->externalUser->privacyName(),
            'Öffentliche ID' => $row->externalUser->public_id_string,
            'Status' => $row->event_state->label(),
            'Runden' => $row->rounds_done,
            'Geschätzt' => $row->rounds_estimated,
        ];
    }
}
