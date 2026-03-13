<?php

namespace App\Components;

use App\Jobs\CheckDonorInvoicesStatus;
use App\Models\Donator;
use App\Services\DonorInvoiceService;
use App\Services\DonorService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use ZipArchive;

class AdminDonatorTable extends PowerGridComponent
{
    use WithExport;

    public string $sortField = 'first_name';

    public string $tableName = 'admin-donator-table';

    protected DonorService $donorService;

    protected DonorInvoiceService $donorInvoiceService;

    /** @var array<string, int> */
    public array $pendingConfirmations = [];

    public function boot(DonorService $donorService, DonorInvoiceService $donorInvoiceService): void
    {
        $this->donorService = $donorService;
        $this->donorInvoiceService = $donorInvoiceService;
    }

    public function header(): array
    {
        return [
            Button::add('bulk-create-invoices')
                ->slot(__('Rechnungen erstellen (<span x-text="window.pgBulkActions.count(\''.$this->tableName.'\')"></span>)'))
                ->class('px-3 py-2 text-sm rounded-md bg-hfm-dark text-hfm-white dark:bg-hfm-white dark:text-hfm-dark hover:bg-hfm-dark/90 dark:hover:bg-hfm-white/90')
                ->dispatch('bulkCreateInvoice.'.$this->tableName, []),

            Button::add('bulk-download-invoices')
                ->slot(__('Rechnungen herunterladen (<span x-text="window.pgBulkActions.count(\''.$this->tableName.'\')"></span>)'))
                ->class('ml-1 px-3 py-2 text-sm rounded-md bg-hfm-dark text-hfm-white dark:bg-hfm-white dark:text-hfm-dark hover:bg-hfm-dark/90 dark:hover:bg-hfm-white/90')
                ->dispatch('bulkDownloadInvoice.'.$this->tableName, []),

            Button::add('bulk-send-invoices')
                ->slot(__('Rechnungen per E-Mail senden (<span x-text="window.pgBulkActions.count(\''.$this->tableName.'\')"></span>)'))
                ->class('ml-1 px-3 py-2 text-sm rounded-md bg-hfm-dark text-hfm-white dark:bg-hfm-white dark:text-hfm-dark hover:bg-hfm-dark/90 dark:hover:bg-hfm-white/90')
                ->dispatch('bulkSendInvoice.'.$this->tableName, []),

            Button::add('bulk-send-invoice-reminders')
                ->slot(__('Zahlungserinnerungen senden (<span x-text="window.pgBulkActions.count(\''.$this->tableName.'\')"></span>)'))
                ->class('ml-1 px-3 py-2 text-sm rounded-md bg-hfm-dark text-hfm-white dark:bg-hfm-white dark:text-hfm-dark hover:bg-hfm-dark/90 dark:hover:bg-hfm-white/90')
                ->dispatch('bulkSendInvoiceReminder.'.$this->tableName, []),

            Button::add('check-payment-status')
                ->slot(__('Zahlungsstatus prüfen'))
                ->class('ml-1 px-3 py-2 text-sm rounded-md bg-hfm-dark text-hfm-white dark:bg-hfm-white dark:text-hfm-dark hover:bg-hfm-dark/90 dark:hover:bg-hfm-white/90')
                ->dispatch('checkPaymentStatus.'.$this->tableName, []),
        ];
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::responsive(),
            PowerGrid::exportable('donor')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage(10, [10, 25, 50, 100, 200])
                ->showRecordCount(mode: 'short'),
        ];
    }

    public function datasource(): Builder
    {
        return Donator::query()
            ->with(['donations', 'donations.athlete', 'donations.athlete.partner'])
            ->select('donators.*')
            ->selectRaw(
                $this->donorInvoiceService->invoiceStatusCaseSql()
            );
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('don_id', function (Donator $donor) {
                return 'DON-'.sprintf('25%04d', $donor->id);
            })
            ->add('numOfDonations', function (Donator $donor) {
                return $donor->donations->count();
            })
            ->add('donations_sum', function (Donator $donor) {
                $lines = $this->donorService->collectInvoiceData($donor);
                $sum = array_sum(array_column($lines, 'total'));

                return 'Fr. '.number_format($sum, 2, '.', "'");
            })
            ->add('created_at_formatted', fn ($donor) => Carbon::parse($donor->created_at)->format('d.m.Y'))
            ->add('invoice_sent_at_formatted', fn ($donor) => $donor->invoice_sent_at ? Carbon::parse($donor->invoice_sent_at)->format('d.m.Y H:i') : null)
            ->add('invoice_reminder_sent_at_formatted', fn ($donor) => $donor->invoice_reminder_sent_at ? Carbon::parse($donor->invoice_reminder_sent_at)->format('d.m.Y H:i') : null)
            ->add('invoice_status', function (Donator $donor) {
                return $this->donorInvoiceService->formatInvoiceStatus($donor);
            })
            ->add('country_of_residence', fn ($donor) => $donor->country_of_residence);
    }

    public function columns(): array
    {
        return [

            Column::make('DON-ID', 'don_id', 'id')
                ->sortable()
                ->searchable()
                ->fixedOnResponsive(),

            Column::make('Vorname', 'first_name')
                ->sortable()
                ->searchable()
                ->fixedOnResponsive(),

            Column::make('Nachname', 'last_name')
                ->sortable()
                ->searchable()
                ->fixedOnResponsive(),

            Column::make('Anzahl Spenden', 'numOfDonations')
                ->fixedOnResponsive(),

            Column::make('Rechnungsbetrag', 'donations_sum'),

            Column::make('Anmeldung', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::make('E-Mail', 'email')
                ->sortable(),

            Column::make('Telefon', 'phone_number')
                ->sortable(),

            Column::make('Land', 'country_of_residence')
                ->sortable(),

            Column::make('Adresse', 'address')
                ->sortable()
                ->searchable(),

            Column::make('PLZ', 'zip_code')
                ->sortable()
                ->searchable(),

            Column::make('Ort', 'city')
                ->sortable()
                ->searchable(),

            Column::make('Rechnung', 'invoice_status')
                ->sortable(),

            Column::make('Rechnung gesendet am', 'invoice_sent_at_formatted', 'invoice_sent_at')
                ->sortable(),

            Column::make('Zahlungserinnerung gesendet am', 'invoice_reminder_sent_at_formatted', 'invoice_reminder_sent_at')
                ->sortable()
                ->bodyAttribute('class', 'whitespace-nowrap'),

            Column::action('Aktionen')
                ->fixedOnResponsive(),

        ];
    }

    public function filters(): array
    {
        return [
        ];
    }

    #[On('createDonorInvoice')]
    public function createDonorInvoice(int $donor_id): void
    {
        try {
            $donor = Donator::findOrFail($donor_id);

            $this->toastActionResult($this->donorInvoiceService->createInvoice($donor));
        } catch (\Throwable $e) {
            Log::error('Error creating donor invoice', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
            Flux::toast(
                heading: 'Fehler beim Erstellen der Rechnung',
                text: $e->getMessage(),
                variant: 'danger',
                duration: 0,
            );
        }
    }

    #[On('confirmDeleteDonorInvoice')]
    public function confirmDeleteDonorInvoice(int $donor_id): void
    {
        $donor = Donator::find($donor_id);
        $name = $donor ? $donor->privacy_name : 'diese:n Spender:in';

        if ($this->requiresSecondClick(
            key: 'delete-invoice-'.$donor_id,
            heading: 'Bitte bestätigen',
            text: "Klicke erneut auf Löschen für {$name}, um die Rechnung inklusive Webling-Buchungen zu entfernen.",
        )) {
            return;
        }

        $this->deleteDonorInvoice($donor_id);
    }

    public function deleteDonorInvoice(int $donor_id): void
    {
        try {
            $donor = Donator::findOrFail($donor_id);

            Log::info('Deleting donor invoice debitor', ['donor_id' => $donor_id]);
            $this->toastActionResult($this->donorInvoiceService->deleteInvoice($donor));
        } catch (\Throwable $e) {
            Log::error('Error deleting donor invoice', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
            Flux::toast(
                heading: 'Fehler beim Löschen der Rechnung',
                text: $e->getMessage(),
                variant: 'danger',
                duration: 0,
            );
        }
    }

    /**
     * Download the generated donor invoice letter PDF if available.
     */
    public function downloadDonorInvoice(int $donor_id): ?HttpResponse
    {
        try {
            $donor = Donator::findOrFail($donor_id);
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
        } catch (\Throwable $e) {
            Log::error('Error downloading donor invoice', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
            Flux::toast(
                heading: 'Fehler beim Herunterladen',
                text: $e->getMessage(),
                variant: 'danger',
                duration: 0,
            );

            return null;
        }
    }

    public function actions(Donator $row): array
    {
        // Actions are rendered from a dedicated Blade view via actionsFromView()
        return [];
    }

    #[On('checkPaymentStatus.{tableName}')]
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
        } catch (\Throwable $e) {
            Log::error('Error checking payment status', ['error' => $e->getMessage()]);
            Flux::toast(
                heading: 'Fehler beim Prüfen des Zahlungsstatus',
                text: $e->getMessage(),
                variant: 'danger',
                duration: 0,
            );
        }
    }

    public function actionsFromView(mixed $row): View
    {

        return view('powergrid.admin-donor-actions', [
            'row' => $row,
        ]);
    }

    public function sendDonorInvoice(int $donor_id): void
    {
        $donor = $this->findDonorOrToast((int) $donor_id);
        if (! $donor) {
            return;
        }

        if ($donor->invoice_sent_at) {
            $name = $donor->privacy_name ?? 'diese:n Spender:in';

            if ($this->requiresSecondClick(
                key: 'resend-invoice-'.$donor_id,
                heading: 'Rechnung erneut senden?',
                text: "Die Rechnung für {$name} wurde bereits gesendet. Klicke erneut, um sie nochmals zu senden.",
            )) {
                return;
            }

        }

        $this->sendDonorInvoiceConfirmed($donor_id);
    }

    public function sendDonorInvoiceConfirmed(int $donor_id): bool
    {
        try {
            $donor = Donator::findOrFail($donor_id);

            $result = $this->donorInvoiceService->sendInvoice($donor);
            $this->toastActionResult($result);

            return $result['variant'] === 'success';
        } catch (\Throwable $e) {
            Log::error('Error sending donor invoice', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
            Flux::toast(
                heading: 'Fehler beim Senden',
                text: $e->getMessage(),
                variant: 'danger',
                duration: 0,
            );

            return false;
        }
    }

    public function sendDonorInvoiceReminder(int $donor_id): void
    {
        $donor = $this->findDonorOrToast((int) $donor_id);
        if (! $donor) {
            return;
        }

        if (! empty($donor->invoice_reminder_sent_at)) {
            $name = $donor->privacy_name ?? 'diese:n Spender:in';

            if ($this->requiresSecondClick(
                key: 'resend-reminder-'.$donor_id,
                heading: 'Zahlungserinnerung erneut senden?',
                text: "Für {$name} wurde bereits eine Erinnerung gesendet. Klicke erneut, um sie nochmals zu senden.",
            )) {
                return;
            }

        }

        $this->sendDonorInvoiceReminderConfirmed($donor_id);
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

    public function sendDonorInvoiceReminderConfirmed(int $donor_id): bool
    {
        try {
            $donor = Donator::findOrFail($donor_id);

            $result = $this->donorInvoiceService->sendReminder($donor);
            $this->toastActionResult($result);

            return $result['variant'] === 'success';
        } catch (\Throwable $e) {
            Log::error('Error sending donor invoice reminder', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
            Flux::toast(
                heading: 'Fehler beim Senden der Erinnerung',
                text: $e->getMessage(),
                variant: 'danger',
                duration: 0,
            );

            return false;
        }
    }

    #[On('bulkCreateInvoice.{tableName}')]
    public function bulkCreateInvoice(): void
    {
        $ids = $this->selectedIds();
        if ($ids === []) {
            $this->toastNoSelection();

            return;
        }

        $processed = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            try {
                $donor = Donator::find((int) $id);
                if (! $donor) {
                    $skipped++;

                    continue;
                }

                if (! $this->donorInvoiceService->canCreateInvoiceInBulk($donor)) {
                    $skipped++;

                    continue;
                }

                $this->createDonorInvoice((int) $id);
                $processed++;
            } catch (\Throwable $e) {
                Log::error('Bulk create invoice failed', ['donor_id' => $id, 'error' => $e->getMessage()]);
                $skipped++;
            }
        }

        $this->js('window.pgBulkActions.clearAll()');
        Flux::toast(
            heading: 'Aktion abgeschlossen',
            text: $processed.' Rechnung(en) erstellt, '.$skipped.' übersprungen.',
            variant: 'success',
        );
    }

    #[On('bulkDownloadInvoice.{tableName}')]
    public function bulkDownloadInvoice(): ?HttpResponse
    {
        $ids = $this->selectedIds();
        if ($ids === []) {
            $this->toastNoSelection();

            return null;
        }

        $files = [];
        foreach ($ids as $id) {
            $donor = Donator::find((int) $id);
            if (! $donor) {
                continue;
            }

            $downloadData = $this->donorInvoiceService->getDownloadData($donor);
            if (! is_array($downloadData)) {
                continue;
            }

            $files[] = ['path' => $downloadData['absolute_path'], 'name' => $downloadData['file_name']];
        }

        if (empty($files)) {
            Flux::toast(
                heading: 'Keine PDFs gefunden',
                text: 'Für die ausgewählten Spender:innen wurden keine Rechnungs-PDFs gefunden.',
                variant: 'danger',
                duration: 0,
            );

            return null;
        }

        // Ensure temp directory exists
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

        $this->js('window.pgBulkActions.clearAll()');

        return response()->download($zipPath, 'Rechnungen_'.$timestamp.'.zip')->deleteFileAfterSend(true);
    }

    #[On('bulkSendInvoice.{tableName}')]
    public function bulkSendInvoice(): void
    {
        $ids = $this->selectedIds();
        if ($ids === []) {
            $this->toastNoSelection();

            return;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            try {
                $donor = Donator::find((int) $id);
                if (! $donor) {
                    $skipped++;

                    continue;
                }

                if (! $this->donorInvoiceService->canSendInvoiceInBulk($donor)) {
                    $skipped++;

                    continue;
                }

                $result = $this->sendDonorInvoiceConfirmed((int) $id);
                if ($result === true) {
                    $sent++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                Log::error('Bulk send invoice failed', ['donor_id' => $id, 'error' => $e->getMessage()]);
                $skipped++;
            }
        }

        $this->js('window.pgBulkActions.clearAll()');
        Flux::toast(
            heading: 'Aktion abgeschlossen',
            text: $sent.' E-Mail(s) gesendet, '.$skipped.' übersprungen.',
            variant: 'success',
        );
    }

    #[On('bulkSendInvoiceReminder.{tableName}')]
    public function bulkSendInvoiceReminder(): void
    {
        $ids = $this->selectedIds();
        if ($ids === []) {
            $this->toastNoSelection();

            return;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            try {
                $donor = Donator::find((int) $id);
                if (! $donor) {
                    $skipped++;

                    continue;
                }

                if (! $this->donorInvoiceService->canSendReminderInBulk($donor)) {
                    $skipped++;

                    continue;
                }

                $result = $this->sendDonorInvoiceReminderConfirmed((int) $id);
                if ($result === true) {
                    $sent++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                Log::error('Bulk send invoice reminder failed', ['donor_id' => $id, 'error' => $e->getMessage()]);
                $skipped++;
            }
        }

        $this->js('window.pgBulkActions.clearAll()');
        Flux::toast(
            heading: 'Aktion abgeschlossen',
            text: $sent.' Erinnerung(s)-E-Mail(s) gesendet, '.$skipped.' übersprungen.',
            variant: 'success',
        );
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

    /**
     * @return array<int,mixed>
     */
    protected function selectedIds(): array
    {
        return $this->checkboxValues;
    }

    protected function toastNoSelection(): void
    {
        Flux::toast(
            heading: 'Keine Auswahl',
            text: 'Bitte wähle mindestens eine:n Spender:in aus.',
            variant: 'warning',
        );
    }

    protected function findDonorOrToast(int $donorId): ?Donator
    {
        $donor = Donator::find($donorId);
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
}
