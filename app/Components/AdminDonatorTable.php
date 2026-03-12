<?php

namespace App\Components;

use App\Jobs\CheckDonorInvoicesStatus;
use App\Jobs\CreateDonorInvoice;
use App\Jobs\DeleteDonorInvoiceDebitor;
use App\Mail\GenericMailMessage;
use App\Models\Donator;
use App\Services\DonorService;
use Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

    /** @var array<string, int> */
    public array $pendingConfirmations = [];

    public function boot(DonorService $donorService): void
    {
        $this->donorService = $donorService;
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
                $this->invoiceStatusCaseSql()
            );
    }

    protected function invoiceStatusCaseSql(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $paymentExpr = "JSON_UNQUOTE(JSON_EXTRACT(webling_data, '$.payment_status'))";
            $letterExpr = "JSON_EXTRACT(webling_data, '$.letter_pdf')";
        } else {
            // SQLite / dqlite and other drivers fall back to SQLite-compatible JSON1 functions
            $paymentExpr = "json_extract(webling_data, '$.payment_status')";
            $letterExpr = "json_extract(webling_data, '$.letter_pdf')";
        }

        return "CASE\n"
            ."                WHEN {$paymentExpr} = 'paid' THEN 'bezahlt'\n"
            ."                WHEN {$paymentExpr} = 'overdue' THEN 'überfällig'\n"
            ."                WHEN invoice_sent_at IS NOT NULL THEN 'gesendet'\n"
            ."                WHEN {$letterExpr} IS NOT NULL THEN 'erstellt'\n"
            ."                ELSE '-'\n"
            .'                END AS invoice_status';
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
                $weblingData = $donor->webling_data ?? [];
                $payment = $weblingData['payment_status'] ?? null;
                if ($payment === 'paid') {
                    return 'bezahlt';
                }
                if ($payment === 'overdue') {
                    return 'überfällig';
                }
                if (! empty($donor->invoice_sent_at)) {
                    return 'gesendet';
                }
                if (! empty($weblingData['letter_pdf'])) {
                    return 'erstellt';
                }

                return '-';
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

            // If a debitor already exists AND a letter PDF is present, there's nothing to do
            $weblingData = $donor->webling_data ?? [];
            $hasDebitor = ! empty($weblingData['debitor_id']);
            $hasLetterPdf = ! empty($weblingData['letter_pdf']);
            if ($hasDebitor && $hasLetterPdf) {
                Flux::toast(
                    heading: 'Bereits vorhanden',
                    text: 'Für '.$donor->privacy_name.' ist bereits eine Rechnung erstellt worden. Es gibt nichts zu tun.',
                    variant: 'warning',
                );

                return;
            }
            CreateDonorInvoice::dispatchSync($donor);
            Flux::toast(
                heading: 'Rechnung erstellt',
                text: 'Die Rechnung für '.$donor->privacy_name.' wurde erfolgreich erstellt.',
                variant: 'success',
            );
            $this->dispatch('$refresh');
        } catch (\Throwable $e) {
            \Log::error('Error creating donor invoice', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
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

            // If neither a debitor nor a letter PDF exists, there's nothing to delete
            $weblingData = $donor->webling_data ?? [];
            $hasDebitor = ! empty($weblingData['debitor_id']);
            $hasLetterPdf = ! empty($weblingData['letter_pdf']);
            if (! $hasDebitor && ! $hasLetterPdf) {
                Flux::toast(
                    heading: 'Nichts zu löschen',
                    text: 'Für '.$donor->privacy_name.' sind keine Rechnungseinträge vorhanden.',
                    variant: 'warning',
                );

                return;
            }

            \Log::info('Deleting donor invoice debitor', ['donor_id' => $donor_id]);
            DeleteDonorInvoiceDebitor::dispatchSync($donor);
            Flux::toast(
                heading: 'Rechnung gelöscht',
                text: 'Die Rechnungseinträge für '.$donor->privacy_name.' wurden gelöscht.',
                variant: 'success',
            );
            $this->dispatch('$refresh');
        } catch (\Throwable $e) {
            \Log::error('Error deleting donor invoice', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
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
            $weblingData = $donor->webling_data ?? [];

            if (! isset($weblingData['letter_pdf']) || ! is_array($weblingData['letter_pdf'])) {
                Flux::toast(
                    heading: 'Kein PDF gefunden',
                    text: 'Für '.$donor->privacy_name.' ist noch kein Rechnungs-PDF vorhanden.',
                    variant: 'danger',
                    duration: 0,
                );

                return null;
            }

            $disk = (string) ($weblingData['letter_pdf']['disk'] ?? 'local');
            $path = (string) ($weblingData['letter_pdf']['path'] ?? '');

            if ($path === '' || ! Storage::disk($disk)->exists($path)) {
                Flux::toast(
                    heading: 'Datei nicht gefunden',
                    text: 'Das gespeicherte Rechnungs-PDF konnte nicht gefunden werden.',
                    variant: 'danger',
                    duration: 0,
                );

                return null;
            }

            $absolutePath = Storage::disk($disk)->path($path);

            $donId = 'DON-'.sprintf('25%04d', $donor->id);
            $fileName = 'Rechnung_'.$donId.'.pdf';

            return response()->download($absolutePath, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error downloading donor invoice', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
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

            // Compute a summary of current invoice statuses (exclusive buckets)
            $summary = $this->invoiceStatusSummary();

            // Notify UI
            Flux::toast(
                heading: 'Zahlungsstatus aktualisiert',
                text: 'Bezahlte und überfällige Rechnungen wurden abgeglichen.',
                variant: 'success',
            );

            // Ask a sibling component to show the summary modal
            $this->dispatch('showPaymentStatusSummary', $summary);

            $this->dispatch('$refresh');
        } catch (\Throwable $e) {
            \Log::error('Error checking payment status', ['error' => $e->getMessage()]);
            Flux::toast(
                heading: 'Fehler beim Prüfen des Zahlungsstatus',
                text: $e->getMessage(),
                variant: 'danger',
                duration: 0,
            );
        }
    }

    /**
     * Build an exclusive summary matching the status precedence used in the table:
     * paid > overdue > sent > created > not created.
     *
     * @return array{paid:int,overdue:int,sent:int,created:int,not_created:int}
     */
    protected function invoiceStatusSummary(): array
    {
        // Paid
        $paid = Donator::query()
            ->where('webling_data->payment_status', 'paid')
            ->count();

        // Overdue (but not paid)
        $overdue = Donator::query()
            ->where('webling_data->payment_status', 'overdue')
            ->count();

        // Sent but neither paid nor overdue
        $sent = Donator::query()
            ->whereNotNull('invoice_sent_at')
            ->where(function ($q) {
                $q->whereNull('webling_data->payment_status')
                    ->orWhereNotIn('webling_data->payment_status', ['paid', 'overdue']);
            })
            ->count();

        // Created (letter exists) but not sent and not paid/overdue
        $created = Donator::query()
            ->whereNull('invoice_sent_at')
            ->whereNotNull('webling_data->letter_pdf')
            ->where(function ($q) {
                $q->whereNull('webling_data->payment_status')
                    ->orWhereNotIn('webling_data->payment_status', ['paid', 'overdue']);
            })
            ->count();

        // Not created: no letter, not sent, and no payment status
        $notCreated = Donator::query()
            ->whereNull('invoice_sent_at')
            ->whereNull('webling_data->letter_pdf')
            ->where(function ($q) {
                $q->whereNull('webling_data->payment_status')
                    ->orWhereNotIn('webling_data->payment_status', ['paid', 'overdue']);
            })
            ->count();

        return [
            'paid' => $paid,
            'overdue' => $overdue,
            'sent' => $sent,
            'created' => $created,
            'not_created' => $notCreated,
        ];
    }

    public function actionsFromView(mixed $row): View
    {

        return view('powergrid.admin-donor-actions', [
            'row' => $row,
        ]);
    }

    public function sendDonorInvoice(int $donor_id): void
    {
        $donor = Donator::find($donor_id);
        if (! $donor) {
            Flux::toast(
                heading: 'Nicht gefunden',
                text: 'Die/der ausgewählte Spender:in wurde nicht gefunden.',
                variant: 'danger',
                duration: 0,
            );

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

            if (empty($donor->email)) {
                Flux::toast(
                    heading: 'Keine E-Mail-Adresse',
                    text: 'Für '.$donor->privacy_name.' ist keine E-Mail-Adresse hinterlegt.',
                    variant: 'danger',
                    duration: 0,
                );

                return false;
            }

            $weblingData = $donor->webling_data ?? [];
            if (! isset($weblingData['letter_pdf']) || ! is_array($weblingData['letter_pdf'])) {
                Flux::toast(
                    heading: 'Kein PDF gefunden',
                    text: 'Für '.$donor->privacy_name.' ist noch kein Rechnungs-PDF vorhanden.',
                    variant: 'danger',
                    duration: 0,
                );

                return false;
            }

            $disk = (string) ($weblingData['letter_pdf']['disk'] ?? 'local');
            $path = (string) ($weblingData['letter_pdf']['path'] ?? '');

            if ($path === '' || ! Storage::disk($disk)->exists($path)) {
                Flux::toast(
                    heading: 'Datei nicht gefunden',
                    text: 'Das gespeicherte Rechnungs-PDF konnte nicht gefunden werden.',
                    variant: 'danger',
                    duration: 0,
                );

                return false;
            }

            $donId = 'DON-'.sprintf('25%04d', $donor->id);
            $fileName = 'Rechnung_'.$donId.'.pdf';

            $subject = 'Rechnung Höhenmeter für Menschen';
            $html = '<p>Liebe:r '.$donor->first_name.'</p>'
                .'<p>Im Anhang findest du deine Rechnung. Vielen Dank für deine Unterstützung!</p>'
                .'<p>Herzliche Grüsse<br>Das Team von Höhenmeter für Menschen</p>';

            $mailable = new GenericMailMessage(
                subject: $subject,
                html: $html,
                storageAttachments: [[
                    'disk' => $disk,
                    'path' => $path,
                    'name' => $fileName,
                    'mime' => 'application/pdf',
                ]]
            );

            Mail::to($donor)->queue($mailable);

            $donor->invoice_sent_at = now();
            $donor->save();

            Flux::toast(
                heading: 'Rechnung gesendet',
                text: 'Die Rechnung wurde an '.$donor->email.' gesendet.',
                variant: 'success',
            );
            $this->dispatch('$refresh');

            return true;
        } catch (\Throwable $e) {
            \Log::error('Error sending donor invoice', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
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
        $donor = Donator::find($donor_id);
        if (! $donor) {
            Flux::toast(
                heading: 'Nicht gefunden',
                text: 'Die/der ausgewählte Spender:in wurde nicht gefunden.',
                variant: 'danger',
                duration: 0,
            );

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

            if (empty($donor->invoice_sent_at)) {
                Flux::toast(
                    heading: 'Rechnung nicht gesendet',
                    text: 'Die Rechnung wurde für '.$donor->privacy_name.' noch nicht gesendet.',
                    variant: 'danger',
                    duration: 0,
                );

                return false;
            }

            $paymentStatus = data_get($donor->webling_data, 'payment_status');
            if ($paymentStatus !== 'overdue') {
                Flux::toast(
                    heading: 'Nicht überfällig',
                    text: 'Die Rechnung von '.$donor->privacy_name.' ist nicht als überfällig markiert.',
                    variant: 'warning',
                );

                return false;
            }

            if (empty($donor->email)) {
                Flux::toast(
                    heading: 'Keine E-Mail-Adresse',
                    text: 'Für '.$donor->privacy_name.' ist keine E-Mail-Adresse hinterlegt.',
                    variant: 'danger',
                    duration: 0,
                );

                return false;
            }

            $weblingData = $donor->webling_data ?? [];
            if (! isset($weblingData['letter_pdf']) || ! is_array($weblingData['letter_pdf'])) {
                Flux::toast(
                    heading: 'Kein PDF gefunden',
                    text: 'Für '.$donor->privacy_name.' ist kein Rechnungs-PDF vorhanden.',
                    variant: 'danger',
                    duration: 0,
                );

                return false;
            }

            $disk = (string) ($weblingData['letter_pdf']['disk'] ?? 'local');
            $path = (string) ($weblingData['letter_pdf']['path'] ?? '');

            if ($path === '' || ! Storage::disk($disk)->exists($path)) {
                Flux::toast(
                    heading: 'Datei nicht gefunden',
                    text: 'Das gespeicherte Rechnungs-PDF konnte nicht gefunden werden.',
                    variant: 'danger',
                    duration: 0,
                );

                return false;
            }

            $donId = 'DON-'.sprintf('25%04d', $donor->id);
            $fileName = 'Rechnung_'.$donId.'.pdf';

            $subject = 'Zahlungserinnerung – Höhenmeter für Menschen';
            $html = '<p>Liebe:r '.$donor->first_name.'</p>'
                .'<p>Wir möchten dich freundlich an die offene Spendenrechnung erinnern. Im Anhang findest du die Rechnung nochmals. Der Versand der Rechnung erfolgte am '.Carbon::parse($donor->invoice_sent_at)->format('d.m.Y').'.</p>'
                .'<p>Sollte sich diese Erinnerung mit deiner Zahlung gekreuzt haben, kannst du diese Nachricht ignorieren.</p>'
                .'<p>Vielen Dank und herzliche Grüsse<br>Das Team von Höhenmeter für Menschen</p>';

            $mailable = new GenericMailMessage(
                subject: $subject,
                html: $html,
                storageAttachments: [[
                    'disk' => $disk,
                    'path' => $path,
                    'name' => $fileName,
                    'mime' => 'application/pdf',
                ]]
            );

            Mail::to($donor)->queue($mailable);

            $donor->invoice_reminder_sent_at = now();
            $donor->save();

            Flux::toast(
                heading: 'Zahlungserinnerung gesendet',
                text: 'Die Zahlungserinnerung wurde an '.$donor->email.' gesendet.',
                variant: 'success',
            );
            $this->dispatch('$refresh');

            return true;
        } catch (\Throwable $e) {
            \Log::error('Error sending donor invoice reminder', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
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
        $ids = $this->checkboxValues ?? [];
        if (empty($ids)) {
            Flux::toast(
                heading: 'Keine Auswahl',
                text: 'Bitte wähle mindestens eine:n Spender:in aus.',
                variant: 'warning',
            );

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

                // Skip donors without any donations (only for bulk action)
                if ($donor->donations()->count() === 0) {
                    $skipped++;

                    continue;
                }

                $this->createDonorInvoice((int) $id);
                $processed++;
            } catch (\Throwable $e) {
                \Log::error('Bulk create invoice failed', ['donor_id' => $id, 'error' => $e->getMessage()]);
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
        $ids = $this->checkboxValues ?? [];
        if (empty($ids)) {
            Flux::toast(
                heading: 'Keine Auswahl',
                text: 'Bitte wähle mindestens eine:n Spender:in aus.',
                variant: 'warning',
            );

            return null;
        }

        $files = [];
        foreach ($ids as $id) {
            $donor = Donator::find((int) $id);
            if (! $donor) {
                continue;
            }
            $weblingData = $donor->webling_data ?? [];
            if (! isset($weblingData['letter_pdf']) || ! is_array($weblingData['letter_pdf'])) {
                continue;
            }
            $disk = (string) ($weblingData['letter_pdf']['disk'] ?? 'local');
            $path = (string) ($weblingData['letter_pdf']['path'] ?? '');
            if ($path === '' || ! Storage::disk($disk)->exists($path)) {
                continue;
            }
            $absolutePath = Storage::disk($disk)->path($path);
            $donId = 'DON-'.sprintf('25%04d', $donor->id);
            $destName = 'Rechnung_'.$donId.'.pdf';
            $files[] = ['path' => $absolutePath, 'name' => $destName];
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
        $ids = $this->checkboxValues ?? [];
        if (empty($ids)) {
            Flux::toast(
                heading: 'Keine Auswahl',
                text: 'Bitte wähle mindestens eine:n Spender:in aus.',
                variant: 'warning',
            );

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

                // Skip donors that already have an invoice marked as sent to avoid unintended resends in bulk
                if ($donor->invoice_sent_at) {
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
                \Log::error('Bulk send invoice failed', ['donor_id' => $id, 'error' => $e->getMessage()]);
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
        $ids = $this->checkboxValues ?? [];
        if (empty($ids)) {
            Flux::toast(
                heading: 'Keine Auswahl',
                text: 'Bitte wähle mindestens eine:n Spender:in aus.',
                variant: 'warning',
            );

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

                // Skip donors not meeting reminder criteria
                $paymentStatus = data_get($donor->webling_data, 'payment_status');
                if (! empty($donor->invoice_reminder_sent_at) || empty($donor->invoice_sent_at) || $paymentStatus !== 'overdue') {
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
                \Log::error('Bulk send invoice reminder failed', ['donor_id' => $id, 'error' => $e->getMessage()]);
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
}
