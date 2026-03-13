<?php

namespace App\Components;

use App\Models\Athlete;
use App\Services\AthleteDocumentService;
use App\Services\DonationService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminAthleteTable extends AbstractAdminDatatableComponent
{
    public string $sortField = 'first_name';

    /**
     * @var array<int, int|string|null>
     */
    public array $roundsDoneInputs = [];

    protected DonationService $donationService;

    protected AthleteDocumentService $athleteDocumentService;

    public function boot(DonationService $donationService, AthleteDocumentService $athleteDocumentService): void
    {
        $this->donationService = $donationService;
        $this->athleteDocumentService = $athleteDocumentService;
    }

    protected function tableView(): string
    {
        return 'components.admin.tables.athlete-table';
    }

    protected function tableDataKey(): string
    {
        return 'athletes';
    }

    public function saveRoundsDone(int $athleteId): void
    {
        $value = $this->roundsDoneInputs[$athleteId] ?? null;

        $validator = Validator::make(
            ['rounds_done' => $value],
            ['rounds_done' => ['required', 'integer', 'min:0']],
            ['rounds_done.required' => 'Bitte gib eine Anzahl Runden ein.']
        );

        if ($validator->fails()) {
            Flux::toast(
                text: $validator->errors()->first('rounds_done'),
                heading: 'Fehler beim Speichern',
                variant: 'danger',
            );

            return;
        }

        try {
            $athlete = Athlete::query()->findOrFail($athleteId);
            $athlete->rounds_done = (int) $validator->validated()['rounds_done'];
            $athlete->save();

            Flux::toast(text: 'Die Änderungen wurden gespeichert.', heading: 'Erfolgreich gespeichert', variant: 'success');
        } catch (\Throwable $exception) {
            Flux::toast(text: 'Die Änderungen konnten nicht gespeichert werden.', heading: 'Fehler beim Speichern', variant: 'danger');
        }
    }

    public function downloadWelcomeLetter(int $athleteId): HttpResponse
    {
        $athlete = Athlete::query()->findOrFail($athleteId);
        $document = $this->athleteDocumentService->buildWelcomeLetter($athlete);

        return response()->streamDownload(function () use ($document): void {
            echo $document['pdf']->stream();
        }, $document['filename']);
    }

    public function downloadPersonalizedFlyerTemplate(int $athleteId): HttpResponse
    {
        $athlete = Athlete::query()->findOrFail($athleteId);
        $document = $this->athleteDocumentService->buildPersonalizedFlyer($athlete);

        return response()->streamDownload(function () use ($document): void {
            echo $document['pdf']->stream();
        }, $document['filename']);
    }

    public function estimatedDonationsTotal(Athlete $athlete): float
    {
        return $this->donationService->calculateEstimatedTotalForAthlete($athlete);
    }

    public function actualDonationsTotal(Athlete $athlete): float
    {
        return $this->donationService->calculateActualTotalForAthlete($athlete);
    }

    public function exportAll(string $format): ?HttpResponse
    {
        $rows = [];

        foreach ($this->queryForTable(ignoreSearch: true)->get() as $athlete) {
            if (! $athlete instanceof Athlete) {
                continue;
            }

            $rows[] = $this->exportRow($athlete);
        }

        return $this->exportRowsToDownload($rows, 'sportlerinnen_gesamt', $format);
    }

    public function exportSelected(string $format): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine Sportler:in aus.');

            return null;
        }

        $rows = [];

        foreach ($this->baseQuery()->whereKey($selectedIds)->orderBy('id')->get() as $athlete) {
            if (! $athlete instanceof Athlete) {
                continue;
            }

            $rows[] = $this->exportRow($athlete);
        }

        return $this->exportRowsToDownload($rows, 'sportlerinnen_auswahl', $format);
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $builder) use ($search): void {
            $builder->where('first_name', 'like', $search)
                ->orWhere('last_name', 'like', $search)
                ->orWhere('email', 'like', $search)
                ->orWhere('phone_number', 'like', $search)
                ->orWhere('address', 'like', $search)
                ->orWhere('zip_code', 'like', $search)
                ->orWhere('city', 'like', $search)
                ->orWhere('comment', 'like', $search)
                ->orWhereHas('sportType', fn (Builder $sportTypeQuery): Builder => $sportTypeQuery->where('name', 'like', $search))
                ->orWhereHas('partner', fn (Builder $partnerQuery): Builder => $partnerQuery->where('name', 'like', $search));
        });
    }

    protected function baseQuery(): Builder
    {
        return Athlete::query()->with(['sportType', 'partner', 'donations'])->withCount('donations');
    }

    protected function defaultSortColumn(): string
    {
        return 'athletes.first_name';
    }

    /**
     * @return array<string, string>
     */
    protected function sortColumns(): array
    {
        return [
            'first_name' => 'athletes.first_name',
            'last_name' => 'athletes.last_name',
            'verified' => 'athletes.verified',
            'sport_type_id' => 'athletes.sport_type_id',
            'partner_id' => 'athletes.partner_id',
            'rounds_estimated' => 'athletes.rounds_estimated',
            'rounds_done' => 'athletes.rounds_done',
            'donations_count' => 'donations_count',
            'created_at' => 'athletes.created_at',
            'adult' => 'athletes.adult',
            'phone_number' => 'athletes.phone_number',
            'email' => 'athletes.email',
            'address' => 'athletes.address',
            'zip_code' => 'athletes.zip_code',
            'city' => 'athletes.city',
            'comment' => 'athletes.comment',
        ];
    }

    protected function hydrateTableState(LengthAwarePaginator $paginator): void
    {
        foreach ($paginator->items() as $athlete) {
            if (! $athlete instanceof Athlete) {
                continue;
            }

            if (! array_key_exists($athlete->id, $this->roundsDoneInputs)) {
                $this->roundsDoneInputs[$athlete->id] = $athlete->rounds_done;
            }
        }
    }

    /**
     * @return array<string, array{label:string, sortable:bool, sort_field?:string}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'first_name' => ['label' => 'Vorname', 'sortable' => true],
            'last_name' => ['label' => 'Nachname', 'sortable' => true],
            'verified' => ['label' => 'Bestätigt', 'sortable' => true],
            'sport_type' => ['label' => 'Sportart', 'sortable' => true, 'sort_field' => 'sport_type_id'],
            'partner' => ['label' => 'Partner', 'sortable' => true, 'sort_field' => 'partner_id'],
            'rounds_estimated' => ['label' => 'Runden geschätzt', 'sortable' => true],
            'rounds_done' => ['label' => 'Runden gemacht', 'sortable' => true],
            'donations_count' => ['label' => 'Spenden', 'sortable' => true],
            'estimated_total' => ['label' => 'Geschätzte Spenden', 'sortable' => false],
            'actual_total' => ['label' => 'Tatsächliche Spenden', 'sortable' => false],
            'created_at' => ['label' => 'Anmeldung', 'sortable' => true],
            'adult' => ['label' => 'Erwachsen', 'sortable' => true],
            'phone_number' => ['label' => 'Telefon', 'sortable' => true],
            'email' => ['label' => 'E-Mail', 'sortable' => true],
            'address' => ['label' => 'Adresse', 'sortable' => true],
            'zip_code' => ['label' => 'PLZ', 'sortable' => true],
            'city' => ['label' => 'Ort', 'sortable' => true],
            'comment' => ['label' => 'Kommentar', 'sortable' => true],
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
            'verified',
            'sport_type',
            'partner',
            'rounds_estimated',
            'rounds_done',
            'donations_count',
            'estimated_total',
            'actual_total',
            'created_at',
            'email',
        ];
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(Athlete $athlete): array
    {
        return [
            'Vorname' => $athlete->first_name,
            'Nachname' => $athlete->last_name,
            'Bestätigt' => $athlete->verified ? 'Ja' : 'Nein',
            'Sportart' => $athlete->sportType->name,
            'Partner' => $athlete->partner->name,
            'Runden geschätzt' => $athlete->rounds_estimated,
            'Runden gemacht' => $athlete->rounds_done,
            'Spenden' => $athlete->donations_count,
            'Geschätzte Spenden' => $this->estimatedDonationsTotal($athlete),
            'Tatsächliche Spenden' => $this->actualDonationsTotal($athlete),
            'Anmeldung' => Carbon::parse($athlete->created_at)->format('d.m.Y'),
            'Erwachsen' => $athlete->adult ? 'Ja' : 'Nein',
            'Telefon' => $athlete->phone_number,
            'E-Mail' => $athlete->email,
            'Adresse' => $athlete->address,
            'PLZ' => $athlete->zip_code,
            'Ort' => $athlete->city,
            'Kommentar' => $athlete->comment,
        ];
    }
}
