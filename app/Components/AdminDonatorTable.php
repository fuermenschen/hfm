<?php

namespace App\Components;

use App\Jobs\CheckDonorInvoicesStatus;
use App\Models\Donator;
use App\Services\DonorInvoiceService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use ZipArchive;

class AdminDonatorTable extends AbstractAdminDatatableComponent
{
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

    protected function tableView(): string
    {
        return 'components.admin.tables.donator-table';
    }

    protected function tableDataKey(): string
    {
        return 'donors';
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

    protected function applySearch(Builder $query, string $search): void
    {
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

    protected function baseQuery(): Builder
    {
        return Donator::query()
            ->withCount('donations')
            ->select('donators.*')
            ->selectSub($this->donorInvoiceService->invoiceTotalSubquery(), 'invoice_total')
            ->selectRaw($this->donorInvoiceService->invoiceStatusCaseSql());
    }

    protected function defaultSortColumn(): string
    {
        return 'donators.first_name';
    }

    /**
     * @return array<string, string>
     */
    protected function sortColumns(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<string, array{label:string, sortable:bool, sort_field?:string, align?:string, width?:string, tooltip?:bool, truncate?:int, export_key?:string, formatter?:string}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'don_id' => ['label' => 'DON-ID', 'sortable' => true, 'sort_field' => 'id', 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'DON-ID'],
            'first_name' => ['label' => 'Vorname', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Vorname'],
            'last_name' => ['label' => 'Nachname', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Nachname'],
            'donations_count' => ['label' => 'Anzahl Spenden', 'sortable' => true, 'align' => 'right', 'width' => 'min-w-28', 'export_key' => 'Anzahl Spenden'],
            'invoice_total' => ['label' => 'Rechnungsbetrag', 'sortable' => false, 'align' => 'right', 'width' => 'min-w-40', 'export_key' => 'Rechnungsbetrag', 'formatter' => 'money'],
            'created_at' => ['label' => 'Anmeldung', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'Anmeldung', 'formatter' => 'date'],
            'email' => ['label' => 'E-Mail', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-56', 'tooltip' => true, 'truncate' => 52, 'export_key' => 'E-Mail'],
            'phone_number' => ['label' => 'Telefon', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'Telefon'],
            'country' => ['label' => 'Land', 'sortable' => true, 'sort_field' => 'country_of_residence', 'align' => 'left', 'width' => 'min-w-32', 'export_key' => 'Land'],
            'address' => ['label' => 'Adresse', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-60', 'tooltip' => true, 'truncate' => 44, 'export_key' => 'Adresse'],
            'zip_code' => ['label' => 'PLZ', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-24', 'export_key' => 'PLZ'],
            'city' => ['label' => 'Ort', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'Ort'],
            'invoice_status' => ['label' => 'Rechnung', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Rechnung'],
            'invoice_sent_at' => ['label' => 'Rechnung gesendet am', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-48', 'export_key' => 'Rechnung gesendet am', 'formatter' => 'datetime_or_dash'],
            'invoice_reminder_sent_at' => ['label' => 'Erinnerung gesendet am', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-48', 'export_key' => 'Erinnerung gesendet am', 'formatter' => 'datetime_or_dash'],
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
}
