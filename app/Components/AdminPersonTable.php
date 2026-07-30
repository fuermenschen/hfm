<?php

declare(strict_types=1);

namespace App\Components;

use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Services\AthleteService;
use App\Services\DonorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminPersonTable extends AbstractDatatableComponent
{
    public string $sortField = 'first_name';

    #[Locked]
    public string $role = '';

    #[Url(as: 'anlass', except: '')]
    public ?string $eventId = '';

    protected AthleteService $athleteService;

    protected DonorService $donorService;

    public function boot(AthleteService $athleteService, DonorService $donorService): void
    {
        $this->athleteService = $athleteService;
        $this->donorService = $donorService;
    }

    public function mount(string $role = ''): void
    {
        throw_unless(in_array($role, ['athlete', 'donor'], true), \InvalidArgumentException::class, 'Invalid person role.');

        $this->role = $role;

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
        if ($this->eventId === null || $this->eventId === '') {
            return;
        }

        $eventId = ctype_digit($this->eventId) ? (int) $this->eventId : 0;

        if ($eventId < 1) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($this->role === 'athlete') {
            $query->whereHas('athleteRegistrations', fn (Builder $registrations): Builder => $registrations->where('donation_event_id', $eventId));

            return;
        }

        $query->whereHas('donationsAsDonor.athleteRegistration', fn (Builder $registration): Builder => $registration->where('donation_event_id', $eventId));
    }

    protected function tableFilterProperties(): array
    {
        return ['eventId'];
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
