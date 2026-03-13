<?php

namespace App\Components;

use App\Components\Concerns\InteractsWithAdminDatatable;
use App\Jobs\CheckDonorInvoicesStatus;
use App\Models\Donator;
use App\Services\DonorInvoiceService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use ZipArchive;

class AdminDonatorTable extends Component
{
    use InteractsWithAdminDatatable;
    use WithPagination;

    public string $sortField = 'first_name';

    protected DonorInvoiceService $donorInvoiceService;

    /**
     * @var array<string, int>
     */
    public array $pendingConfirmations = [];

    public function boot(DonorInvoiceService $donorInvoiceService): void
    {
        $this->donorInvoiceService = $donorInvoiceService;
    }

    public function mount(): void
    {
        $this->initializeVisibleColumns();
    }

    public function render(): View
    {
        $donors = $this->queryForTable(ignoreSearch: false)->paginate($this->perPage);

        return view('components.admin.tables.donator-table', [
            'donors' => $donors,
            'pageIds' => $this->pageIds($donors),
        ]);
    }

    public function checkPaymentStatus(): void
    {
        try {
            CheckDonorInvoicesStatus::dispatchSync();

            $summary = $this->donorInvoiceService->invoiceStatusSummary();

            Flux::toast(
                heading: 'Zahlungsstatus aktualisiert',
                text: 'Bezahlte und überfällige Rechnungen wurden abgeglichen.',
                variant: 'success',
            );

            $this->dispatch('showPaymentStatusSummary', $summary);
            $this->dispatch('$refresh');
        } catch (\Throwable $exception) {
            Log::error('Error checking payment status', ['error' => $exception->getMessage()]);

            Flux::toast(
                heading: 'Fehler beim Prüfen des Zahlungsstatus',
                text: $exception->getMessage(),
                variant: 'danger',
                duration: 0,
            );
        }
    }

    public function createDonorInvoice(int $donorId): void
    {
        try {
            $donor = Donator::query()->findOrFail($donorId);

            $this->toastActionResult($this->donorInvoiceService->createInvoice($donor));
        } catch (\Throwable $exception) {
            Log::error('Error creating donor invoice', ['error' => $exception->getMessage(), 'donor_id' => $donorId]);

            Flux::toast(
                heading: 'Fehler beim Erstellen der Rechnung',
                text: $exception->getMessage(),
                variant: 'danger',
                duration: 0,
            );
        }
    }

    public function confirmDeleteDonorInvoice(int $donorId): void
    {
        $donor = Donator::query()->find($donorId);
        $name = $donor ? $donor->privacy_name : 'diese:n Spender:in';

        if ($this->requiresSecondClick(
            key: 'delete-invoice-'.$donorId,
            heading: 'Bitte bestätigen',
            text: "Klicke erneut auf Löschen für {$name}, um die Rechnung inklusive Webling-Buchungen zu entfernen.",
        )) {
            return;
        }

        $this->deleteDonorInvoice($donorId);
    }

    public function deleteDonorInvoice(int $donorId): void
    {
        try {
            $donor = Donator::query()->findOrFail($donorId);

            Log::info('Deleting donor invoice debitor', ['donor_id' => $donorId]);
            $this->toastActionResult($this->donorInvoiceService->deleteInvoice($donor));
        } catch (\Throwable $exception) {
            Log::error('Error deleting donor invoice', ['error' => $exception->getMessage(), 'donor_id' => $donorId]);

            Flux::toast(
                heading: 'Fehler beim Löschen der Rechnung',
                text: $exception->getMessage(),
                variant: 'danger',
                duration: 0,
            );
        }
    }

    public function downloadDonorInvoice(int $donorId): ?HttpResponse
    {
        try {
            $donor = Donator::query()->findOrFail($donorId);
            $downloadData = $this->donorInvoiceService->getDownloadData($donor);

            if (! is_array($downloadData)) {
                Flux::toast(
                    heading: 'Kein PDF gefunden',
                    text: 'Für '.$donor->privacy_name.' ist noch kein gültiges Rechnungs-PDF vorhanden.',
                    variant: 'danger',
                    duration: 0,
                );

                return null;
            }

            return response()->download($downloadData['absolute_path'], $downloadData['file_name'], [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Throwable $exception) {
            Log::error('Error downloading donor invoice', ['error' => $exception->getMessage(), 'donor_id' => $donorId]);

            Flux::toast(
                heading: 'Fehler beim Herunterladen',
                text: $exception->getMessage(),
                variant: 'danger',
                duration: 0,
            );

            return null;
        }
    }

    public function sendDonorInvoice(int $donorId): void
    {
        $donor = $this->findDonorOrToast($donorId);

        if (! $donor) {
            return;
        }

        if ($donor->invoice_sent_at) {
            $name = $donor->privacy_name ?? 'diese:n Spender:in';

            if ($this->requiresSecondClick(
                key: 'resend-invoice-'.$donorId,
                heading: 'Rechnung erneut senden?',
                text: "Die Rechnung für {$name} wurde bereits gesendet. Klicke erneut, um sie nochmals zu senden.",
            )) {
                return;
            }
        }

        $this->sendDonorInvoiceConfirmed($donorId);
    }

    public function sendDonorInvoiceConfirmed(int $donorId): bool
    {
        try {
            $donor = Donator::query()->findOrFail($donorId);
            $result = $this->donorInvoiceService->sendInvoice($donor);
            $this->toastActionResult($result);

            return $result['variant'] === 'success';
        } catch (\Throwable $exception) {
            Log::error('Error sending donor invoice', ['error' => $exception->getMessage(), 'donor_id' => $donorId]);

            Flux::toast(
                heading: 'Fehler beim Senden',
                text: $exception->getMessage(),
                variant: 'danger',
                duration: 0,
            );

            return false;
        }
    }

    public function sendDonorInvoiceReminder(int $donorId): void
    {
        $donor = $this->findDonorOrToast($donorId);

        if (! $donor) {
            return;
        }

        if (! empty($donor->invoice_reminder_sent_at)) {
            $name = $donor->privacy_name ?? 'diese:n Spender:in';

            if ($this->requiresSecondClick(
                key: 'resend-reminder-'.$donorId,
                heading: 'Zahlungserinnerung erneut senden?',
                text: "Für {$name} wurde bereits eine Erinnerung gesendet. Klicke erneut, um sie nochmals zu senden.",
            )) {
                return;
            }
        }

        $this->sendDonorInvoiceReminderConfirmed($donorId);
    }

    public function sendDonorInvoiceReminderConfirmed(int $donorId): bool
    {
        try {
            $donor = Donator::query()->findOrFail($donorId);
            $result = $this->donorInvoiceService->sendReminder($donor);
            $this->toastActionResult($result);

            return $result['variant'] === 'success';
        } catch (\Throwable $exception) {
            Log::error('Error sending donor invoice reminder', ['error' => $exception->getMessage(), 'donor_id' => $donorId]);

            Flux::toast(
                heading: 'Fehler beim Senden der Erinnerung',
                text: $exception->getMessage(),
                variant: 'danger',
                duration: 0,
            );

            return false;
        }
    }

    public function bulkCreateInvoice(): void
    {
        $ids = $this->selectedIds();

        if ($ids === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine:n Spender:in aus.');

            return;
        }

        $processed = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            try {
                $donor = Donator::query()->find($id);

                if (! $donor) {
                    $skipped++;

                    continue;
                }

                if (! $this->donorInvoiceService->canCreateInvoiceInBulk($donor)) {
                    $skipped++;

                    continue;
                }

                $this->createDonorInvoice($id);
                $processed++;
            } catch (\Throwable $exception) {
                Log::error('Bulk create invoice failed', ['donor_id' => $id, 'error' => $exception->getMessage()]);
                $skipped++;
            }
        }

        $this->clearSelection();

        Flux::toast(
            heading: 'Aktion abgeschlossen',
            text: $processed.' Rechnung(en) erstellt, '.$skipped.' übersprungen.',
            variant: 'success',
        );
    }

    public function bulkDownloadInvoice(): ?HttpResponse
    {
        $ids = $this->selectedIds();

        if ($ids === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine:n Spender:in aus.');

            return null;
        }

        $files = [];

        foreach ($ids as $id) {
            $donor = Donator::query()->find($id);

            if (! $donor) {
                continue;
            }

            $downloadData = $this->donorInvoiceService->getDownloadData($donor);

            if (! is_array($downloadData)) {
                continue;
            }

            $files[] = ['path' => $downloadData['absolute_path'], 'name' => $downloadData['file_name']];
        }

        if ($files === []) {
            Flux::toast(
                heading: 'Keine PDFs gefunden',
                text: 'Für die ausgewählten Spender:innen wurden keine Rechnungs-PDFs gefunden.',
                variant: 'danger',
                duration: 0,
            );

            return null;
        }

        Storage::disk('local')->makeDirectory('tmp');

        $timestamp = now()->format('Ymd_His');
        $zipRelative = 'tmp/rechnungen_'.$timestamp.'.zip';
        $zipPath = Storage::disk('local')->path($zipRelative);

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Flux::toast(
                heading: 'Fehler',
                text: 'ZIP-Datei konnte nicht erstellt werden.',
                variant: 'danger',
                duration: 0,
            );

            return null;
        }

        foreach ($files as $file) {
            $zip->addFile($file['path'], $file['name']);
        }

        $zip->close();
        $this->clearSelection();

        return response()->download($zipPath, 'Rechnungen_'.$timestamp.'.zip')->deleteFileAfterSend(true);
    }

    public function bulkSendInvoice(): void
    {
        $ids = $this->selectedIds();

        if ($ids === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine:n Spender:in aus.');

            return;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            try {
                $donor = Donator::query()->find($id);

                if (! $donor) {
                    $skipped++;

                    continue;
                }

                if (! $this->donorInvoiceService->canSendInvoiceInBulk($donor)) {
                    $skipped++;

                    continue;
                }

                $result = $this->sendDonorInvoiceConfirmed($id);

                if ($result) {
                    $sent++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $exception) {
                Log::error('Bulk send invoice failed', ['donor_id' => $id, 'error' => $exception->getMessage()]);
                $skipped++;
            }
        }

        $this->clearSelection();

        Flux::toast(
            heading: 'Aktion abgeschlossen',
            text: $sent.' E-Mail(s) gesendet, '.$skipped.' übersprungen.',
            variant: 'success',
        );
    }

    public function bulkSendInvoiceReminder(): void
    {
        $ids = $this->selectedIds();

        if ($ids === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine:n Spender:in aus.');

            return;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            try {
                $donor = Donator::query()->find($id);

                if (! $donor) {
                    $skipped++;

                    continue;
                }

                if (! $this->donorInvoiceService->canSendReminderInBulk($donor)) {
                    $skipped++;

                    continue;
                }

                $result = $this->sendDonorInvoiceReminderConfirmed($id);

                if ($result) {
                    $sent++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $exception) {
                Log::error('Bulk send invoice reminder failed', ['donor_id' => $id, 'error' => $exception->getMessage()]);
                $skipped++;
            }
        }

        $this->clearSelection();

        Flux::toast(
            heading: 'Aktion abgeschlossen',
            text: $sent.' Erinnerung(s)-E-Mail(s) gesendet, '.$skipped.' übersprungen.',
            variant: 'success',
        );
    }

    public function exportAll(string $format): ?HttpResponse
    {
        $rows = [];

        foreach ($this->queryForTable(ignoreSearch: true)->get() as $donor) {
            if (! $donor instanceof Donator) {
                continue;
            }

            $rows[] = $this->exportRow($donor);
        }

        return $this->exportRowsToDownload($rows, 'spenderinnen_gesamt', $format);
    }

    public function exportSelected(string $format): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine:n Spender:in aus.');

            return null;
        }

        $rows = [];

        foreach ($this->baseQuery()->whereKey($selectedIds)->orderBy('id')->get() as $donor) {
            if (! $donor instanceof Donator) {
                continue;
            }

            $rows[] = $this->exportRow($donor);
        }

        return $this->exportRowsToDownload($rows, 'spenderinnen_auswahl', $format);
    }

    protected function queryForTable(bool $ignoreSearch): Builder
    {
        $query = $this->baseQuery();

        if (! $ignoreSearch && $this->search !== '') {
            $search = '%'.$this->search.'%';

            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone_number', 'like', $search)
                    ->orWhere('country_of_residence', 'like', $search)
                    ->orWhere('address', 'like', $search)
                    ->orWhere('zip_code', 'like', $search)
                    ->orWhere('city', 'like', $search)
                    ->orWhereRaw("('DON-' || printf('25%04d', id)) like ?", [$search]);
            });
        }

        $sortColumn = $this->resolveSortColumn();

        if ($sortColumn === null) {
            $sortColumn = 'donators.first_name';
        }

        return $query->orderBy($sortColumn, $this->sortDirection);
    }

    protected function baseQuery(): Builder
    {
        return Donator::query()
            ->withCount('donations')
            ->select('donators.*')
            ->selectSub($this->donorInvoiceService->invoiceTotalSubquery(), 'invoice_total')
            ->selectRaw($this->donorInvoiceService->invoiceStatusCaseSql());
    }

    protected function resolveSortColumn(): ?string
    {
        return match ($this->sortField) {
            'id' => 'donators.id',
            'first_name' => 'donators.first_name',
            'last_name' => 'donators.last_name',
            'donations_count' => 'donations_count',
            'created_at' => 'donators.created_at',
            'email' => 'donators.email',
            'phone_number' => 'donators.phone_number',
            'country_of_residence' => 'donators.country_of_residence',
            'address' => 'donators.address',
            'zip_code' => 'donators.zip_code',
            'city' => 'donators.city',
            'invoice_status' => 'invoice_status',
            'invoice_sent_at' => 'donators.invoice_sent_at',
            'invoice_reminder_sent_at' => 'donators.invoice_reminder_sent_at',
            default => null,
        };
    }

    /**
     * @return array<string, array{label:string, sortable:bool, sort_field?:string}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'don_id' => ['label' => 'DON-ID', 'sortable' => true, 'sort_field' => 'id'],
            'first_name' => ['label' => 'Vorname', 'sortable' => true],
            'last_name' => ['label' => 'Nachname', 'sortable' => true],
            'donations_count' => ['label' => 'Anzahl Spenden', 'sortable' => true],
            'invoice_total' => ['label' => 'Rechnungsbetrag', 'sortable' => false],
            'created_at' => ['label' => 'Anmeldung', 'sortable' => true],
            'email' => ['label' => 'E-Mail', 'sortable' => true],
            'phone_number' => ['label' => 'Telefon', 'sortable' => true],
            'country' => ['label' => 'Land', 'sortable' => true, 'sort_field' => 'country_of_residence'],
            'address' => ['label' => 'Adresse', 'sortable' => true],
            'zip_code' => ['label' => 'PLZ', 'sortable' => true],
            'city' => ['label' => 'Ort', 'sortable' => true],
            'invoice_status' => ['label' => 'Rechnung', 'sortable' => true],
            'invoice_sent_at' => ['label' => 'Rechnung gesendet am', 'sortable' => true],
            'invoice_reminder_sent_at' => ['label' => 'Erinnerung gesendet am', 'sortable' => true],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function defaultVisibleColumns(): array
    {
        return [
            'don_id',
            'first_name',
            'last_name',
            'donations_count',
            'invoice_total',
            'created_at',
            'email',
            'invoice_status',
            'invoice_sent_at',
            'invoice_reminder_sent_at',
        ];
    }

    protected function requiresSecondClick(string $key, string $heading, string $text): bool
    {
        $now = now()->timestamp;
        $expiresAt = $this->pendingConfirmations[$key] ?? null;

        if (is_int($expiresAt) && $expiresAt >= $now) {
            unset($this->pendingConfirmations[$key]);

            return false;
        }

        $this->pendingConfirmations[$key] = $now + 15;

        Flux::toast(heading: $heading, text: $text, variant: 'warning');

        return true;
    }

    /**
     * @param  array{heading:string,text:string,variant:string,duration:int|null,refresh:bool}  $result
     */
    protected function toastActionResult(array $result): void
    {
        Flux::toast(
            heading: $result['heading'],
            text: $result['text'],
            variant: $result['variant'],
            duration: $result['duration'],
        );

        if ($result['refresh']) {
            $this->dispatch('$refresh');
        }
    }

    protected function findDonorOrToast(int $donorId): ?Donator
    {
        $donor = Donator::query()->find($donorId);

        if (! $donor) {
            Flux::toast(
                heading: 'Nicht gefunden',
                text: 'Die/der ausgewählte Spender:in wurde nicht gefunden.',
                variant: 'danger',
                duration: 0,
            );

            return null;
        }

        return $donor;
    }

    public function donorInvoiceTotal(Donator $donor): float
    {
        return $this->donorInvoiceService->invoiceTotalForDonor($donor);
    }

    public function invoiceStatusLabel(Donator $donor): string
    {
        return $this->donorInvoiceService->formatInvoiceStatus($donor);
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(Donator $donor): array
    {
        $sum = $this->donorInvoiceTotal($donor);

        return [
            'DON-ID' => 'DON-'.sprintf('25%04d', $donor->id),
            'Vorname' => $donor->first_name,
            'Nachname' => $donor->last_name,
            'Anzahl Spenden' => $donor->donations_count,
            'Rechnungsbetrag' => $sum,
            'Anmeldung' => Carbon::parse($donor->created_at)->format('d.m.Y'),
            'E-Mail' => $donor->email,
            'Telefon' => $donor->phone_number,
            'Land' => $donor->country_of_residence,
            'Adresse' => $donor->address,
            'PLZ' => $donor->zip_code,
            'Ort' => $donor->city,
            'Rechnung' => $this->invoiceStatusLabel($donor),
            'Rechnung gesendet am' => $donor->invoice_sent_at ? Carbon::parse($donor->invoice_sent_at)->format('d.m.Y H:i') : null,
            'Zahlungserinnerung gesendet am' => $donor->invoice_reminder_sent_at ? Carbon::parse($donor->invoice_reminder_sent_at)->format('d.m.Y H:i') : null,
        ];
    }

    /**
     * @return array<int, int>
     */
    protected function pageIds(LengthAwarePaginator $paginator): array
    {
        return $paginator->getCollection()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
    }
}
