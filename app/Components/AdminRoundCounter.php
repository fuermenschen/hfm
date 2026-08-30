<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\FinishAllAthletesAction;
use App\Actions\RecordAthleteRoundAction;
use App\Actions\ResetAllAthletesAction;
use App\Actions\SetAthleteEventStateAction;
use App\Actions\SetAthleteRoundsAction;
use App\Actions\StartAllAthletesAction;
use App\Enums\EventState;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Services\CurrentDonationEventService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class AdminRoundCounter extends Component
{
    #[Url(as: 'anlass', except: '')]
    public ?string $eventSlug = '';

    #[Url(as: 'filter', except: 'open')]
    public string $statusFilter = 'open';

    #[Url(except: '')]
    public string $search = '';

    public ?int $confirmingFinishId = null;

    public ?int $confirmingResetId = null;

    public string $confirmingBatch = '';

    public int $totalRounds = 0;

    protected CurrentDonationEventService $currentDonationEventService;

    protected RecordAthleteRoundAction $recordAthleteRoundAction;

    protected SetAthleteEventStateAction $setAthleteEventStateAction;

    protected SetAthleteRoundsAction $setAthleteRoundsAction;

    protected StartAllAthletesAction $startAllAthletesAction;

    protected FinishAllAthletesAction $finishAllAthletesAction;

    protected ResetAllAthletesAction $resetAllAthletesAction;

    public function boot(
        CurrentDonationEventService $currentDonationEventService,
        RecordAthleteRoundAction $recordAthleteRoundAction,
        SetAthleteEventStateAction $setAthleteEventStateAction,
        SetAthleteRoundsAction $setAthleteRoundsAction,
        StartAllAthletesAction $startAllAthletesAction,
        FinishAllAthletesAction $finishAllAthletesAction,
        ResetAllAthletesAction $resetAllAthletesAction,
    ): void {
        $this->currentDonationEventService = $currentDonationEventService;
        $this->recordAthleteRoundAction = $recordAthleteRoundAction;
        $this->setAthleteEventStateAction = $setAthleteEventStateAction;
        $this->setAthleteRoundsAction = $setAthleteRoundsAction;
        $this->startAllAthletesAction = $startAllAthletesAction;
        $this->finishAllAthletesAction = $finishAllAthletesAction;
        $this->resetAllAthletesAction = $resetAllAthletesAction;
    }

    public function mount(): void
    {
        if (! request()->query->has('anlass') && ($this->eventSlug === null || $this->eventSlug === '')) {
            $currentEvent = $this->currentDonationEventService->current();
            $this->eventSlug = $currentEvent instanceof DonationEvent ? $currentEvent->slug : '';
        }
    }

    public function render(): View
    {
        $event = $this->selectedEvent();
        $counts = $this->stateCounts($event);
        $this->totalRounds = ! $event instanceof DonationEvent ? 0 : (int) AthleteRegistration::query()->whereBelongsTo($event)->whereHas('externalUser')->sum('rounds_done');

        return view('components.admin.round-counter', [
            'events' => DonationEvent::query()->latest('starts_at')->get(['id', 'title', 'slug', 'is_published']),
            'registrations' => $this->filteredRegistrations($event)->get(),
            'counts' => $counts,
        ]);
    }

    public function start(int $registrationId): void
    {
        $this->transitionState($registrationId, EventState::Running);
    }

    public function confirmBatch(string $batch): void
    {
        $this->ensureAuthenticated();

        if (! in_array($batch, ['start', 'finish', 'reset'], true)) {
            return;
        }

        $this->confirmingBatch = $batch;
        Flux::modal('round-counter-confirm-batch')->show();
    }

    public function runBatch(): void
    {
        $this->ensureAuthenticated();

        $event = $this->selectedEvent();
        $batch = $this->confirmingBatch;

        $this->confirmingBatch = '';
        Flux::modal('round-counter-confirm-batch')->close();

        if (! $event instanceof DonationEvent) {
            Flux::toast(heading: 'Anlass auswählen', text: 'Bitte wähle einen Anlass aus.', variant: 'warning');

            return;
        }

        $count = match ($batch) {
            'start' => ($this->startAllAthletesAction)($event),
            'finish' => ($this->finishAllAthletesAction)($event),
            'reset' => ($this->resetAllAthletesAction)($event),
            default => null,
        };

        if ($count === null) {
            return;
        }

        Flux::toast(heading: 'Erledigt', text: $count.' Anmeldungen wurden aktualisiert.', variant: 'success');
    }

    /**
     * @return array<string, array{heading: string, text: string, button: string}>
     */
    public function batchLabels(): array
    {
        return [
            'start' => ['heading' => 'Alle starten?', 'text' => 'Alle nicht gestarteten Sportler:innen werden gestartet.', 'button' => 'Alle starten'],
            'finish' => ['heading' => 'Alle als fertig markieren?', 'text' => 'Alle Sportler:innen werden als fertig markiert und aus der Standardansicht ausgeblendet.', 'button' => 'Alle fertig markieren'],
            'reset' => ['heading' => 'Alle zurücksetzen?', 'text' => 'Alle Runden werden auf 0 gesetzt und alle Status auf «Nicht gestartet» zurückgesetzt.', 'button' => 'Alle zurücksetzen'],
        ];
    }

    public function confirmFinish(int $registrationId): void
    {
        $this->ensureAuthenticated();

        if (! $this->findRegistration($registrationId) instanceof AthleteRegistration) {
            return;
        }

        $this->confirmingFinishId = $registrationId;
        Flux::modal('round-counter-confirm-finish')->show();
    }

    public function finish(): void
    {
        $this->ensureAuthenticated();

        if ($this->confirmingFinishId === null) {
            return;
        }

        $registration = $this->findRegistration($this->confirmingFinishId);

        if ($registration instanceof AthleteRegistration) {
            ($this->setAthleteEventStateAction)($registration, EventState::Finished);
        }

        $this->confirmingFinishId = null;
        Flux::modal('round-counter-confirm-finish')->close();
    }

    public function reactivate(int $registrationId): void
    {
        $this->transitionState($registrationId, EventState::Running);
    }

    public function addRound(int $registrationId): void
    {
        $this->ensureAuthenticated();

        $registration = $this->findRegistration($registrationId);

        if (! $registration instanceof AthleteRegistration) {
            return;
        }

        if ($registration->event_state === EventState::Finished) {
            return;
        }

        if ($registration->event_state === EventState::NotStarted) {
            ($this->setAthleteEventStateAction)($registration, EventState::Running);
            $registration->refresh();
        }

        ($this->recordAthleteRoundAction)($registration, 1);
    }

    public function removeRound(int $registrationId): void
    {
        $this->ensureAuthenticated();

        $registration = $this->findRegistration($registrationId);

        if (! $registration instanceof AthleteRegistration) {
            return;
        }

        ($this->recordAthleteRoundAction)($registration, -1);
    }

    public function confirmReset(int $registrationId): void
    {
        $this->ensureAuthenticated();

        if (! $this->findRegistration($registrationId) instanceof AthleteRegistration) {
            return;
        }

        $this->confirmingResetId = $registrationId;
        Flux::modal('round-counter-confirm-reset')->show();
    }

    public function resetAthlete(): void
    {
        $this->ensureAuthenticated();

        if ($this->confirmingResetId === null) {
            return;
        }

        $registration = $this->findRegistration($this->confirmingResetId);

        if ($registration instanceof AthleteRegistration) {
            ($this->setAthleteRoundsAction)($registration, 0);
            ($this->setAthleteEventStateAction)($registration, EventState::NotStarted);
        }

        $this->confirmingResetId = null;
        Flux::modal('round-counter-confirm-reset')->close();

        Flux::toast(heading: 'Zurückgesetzt', text: 'Runden und Status wurden zurückgesetzt.', variant: 'success');
    }

    protected function transitionState(int $registrationId, EventState $eventState): void
    {
        $this->ensureAuthenticated();

        $registration = $this->findRegistration($registrationId);

        if (! $registration instanceof AthleteRegistration) {
            return;
        }

        ($this->setAthleteEventStateAction)($registration, $eventState);
    }

    protected function filteredRegistrations(?DonationEvent $event): Builder
    {
        $query = ! $event instanceof DonationEvent
            ? AthleteRegistration::query()->whereRaw('0 = 1')
            : AthleteRegistration::query()->whereBelongsTo($event)->whereHas('externalUser');

        $query->with('externalUser:id,first_name,last_name');

        if ($this->statusFilter === 'open') {
            $query->whereIn('event_state', [EventState::NotStarted->value, EventState::Running->value]);
        } elseif ($this->statusFilter !== 'all') {
            $query->where('event_state', $this->statusFilter);
        }

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->whereHas('externalUser', function (Builder $userQuery) use ($search): void {
                        $userQuery
                            ->whereLike('first_name', '%'.$search.'%')
                            ->orWhereLike('last_name', '%'.$search.'%');
                    })
                    ->orWhereLike('start_number', '%'.$search.'%');
            });
        }

        return $query
            ->orderByRaw('athlete_registrations.start_number IS NULL, athlete_registrations.start_number')
            ->orderBy('athlete_registrations.rounds_done', 'desc');
    }

    /**
     * @return array{open:int, not_started:int, running:int, finished:int, all:int}
     */
    protected function stateCounts(?DonationEvent $event): array
    {
        if (! $event instanceof DonationEvent) {
            return ['open' => 0, 'not_started' => 0, 'running' => 0, 'finished' => 0, 'all' => 0];
        }

        $counts = AthleteRegistration::query()
            ->whereBelongsTo($event)
            ->whereHas('externalUser')
            ->selectRaw('event_state, count(*) as aggregate')
            ->groupBy('event_state')
            ->pluck('aggregate', 'event_state');

        $notStarted = (int) ($counts[EventState::NotStarted->value] ?? 0);
        $running = (int) ($counts[EventState::Running->value] ?? 0);
        $finished = (int) ($counts[EventState::Finished->value] ?? 0);

        return [
            'open' => $notStarted + $running,
            'not_started' => $notStarted,
            'running' => $running,
            'finished' => $finished,
            'all' => $notStarted + $running + $finished,
        ];
    }

    public function updatedEventSlug(): void
    {
        $this->dispatch('anlass-changed', slug: $this->eventSlug ?? '');
    }

    #[On('anlass-changed')]
    public function syncEventSlug(string $slug): void
    {
        if ($this->eventSlug === $slug) {
            return;
        }

        $this->eventSlug = $slug;
    }

    public function selectedEvent(): ?DonationEvent
    {
        if ($this->eventSlug === null || $this->eventSlug === '') {
            return null;
        }

        return DonationEvent::query()->where('slug', $this->eventSlug)->first();
    }

    /**
     * @return array<string, string>
     */
    public function statusFilters(): array
    {
        return [
            'open' => 'Offen',
            'not_started' => 'Nicht gestartet',
            'running' => 'Läuft',
            'finished' => 'Fertig',
            'all' => 'Alle',
        ];
    }

    protected function findRegistration(int $registrationId): ?AthleteRegistration
    {
        $event = $this->selectedEvent();

        if (! $event instanceof DonationEvent) {
            return null;
        }

        $registration = AthleteRegistration::query()
            ->whereBelongsTo($event)
            ->whereHas('externalUser')
            ->find($registrationId);

        return $registration instanceof AthleteRegistration ? $registration : null;
    }

    protected function ensureAuthenticated(): void
    {
        abort_unless(Auth::check(), 403);
    }
}
