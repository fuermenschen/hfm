<?php

namespace App\Components;

use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Services\Webling\Letter\LetterBuilder;
use App\Services\Webling\Letter\LetterService;
use App\Settings\WeblingApiSettings;
use Carbon\Carbon;
use Exception;
use Flux;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class AdminWeblingInterfaceTest extends Component
{
    /**
     * Current wizard step.
     *
     * Possible values: intro | running | inspect_pdf | inspect_link | cleanup | done | error
     */
    public string $step = 'intro';

    /** The Webling debitor ID created during the test. */
    public ?int $debitorId = null;

    /** Direct Webling admin URL for the test debitor. */
    public ?string $debitorUrl = null;

    /** Local storage path of the downloaded PDF (temp file). */
    public ?string $tempPdfPath = null;

    /** Size of the downloaded PDF in bytes. */
    public ?int $tempPdfSize = null;

    /** Error message shown when $step === 'error'. */
    public ?string $errorMessage = null;

    /** Which step failed, so we can show partial cleanup options. */
    public ?string $errorStep = null;

    /**
     * PDF inspection checklist — each item is null (undecided), true (ok) or false (failed).
     *
     * @var array<string, bool|null>
     */
    public array $checklist = [
        'name_correct' => null,
        'address_correct' => null,
        'amount_correct' => null,
        'qr_present' => null,
        'date_correct' => null,
    ];

    /**
     * Result of the Webling direct-link check.
     * null = not yet answered, true = ok, false = problem found.
     */
    public ?bool $linkCheckResult = null;

    /**
     * Whether the wizard completed the full happy path (all steps through confirmLink).
     * False when cleanup is triggered from the error screen mid-wizard.
     */
    public bool $completedFullRun = false;

    /**
     * Fake test data generated for the invoice (shown in intro step).
     *
     * @var array<string, mixed>
     */
    public array $testData = [];

    public function mount(): void
    {
        $this->generateTestData();
    }

    /** Progress value (0–100) for the flux:progress bar. */
    public function getProgressProperty(): int
    {
        return match ($this->step) {
            'intro' => 0,
            'running' => 15,
            'inspect_pdf' => 50,
            'inspect_link' => 75,
            'cleanup' => 88,
            'done' => 100,
            'error' => 0,
            default => 0,
        };
    }

    /**
     * Human-readable label for each checklist item.
     *
     * @return array<string, string>
     */
    public function getChecklistLabelsProperty(): array
    {
        return [
            'name_correct' => 'Name korrekt ('.$this->testData['first_name'].' '.$this->testData['last_name'].')',
            'address_correct' => 'Adresse korrekt ('.$this->testData['address'].', '.$this->testData['zip'].' '.$this->testData['city'].')',
            'amount_correct' => 'Betrag korrekt (Fr. '.number_format((float) $this->testData['amount'], 2, '.', '\'').')',
            'qr_present' => 'QR-Einzahlungsschein vorhanden',
            'date_correct' => 'Datum und Fälligkeit korrekt',
        ];
    }

    /** Whether all checklist items have been decided (none still null). */
    public function getChecklistDecidedProperty(): bool
    {
        return ! in_array(null, $this->checklist, true);
    }

    /** Whether any checklist item was marked as failed. */
    public function getChecklistHasFailuresProperty(): bool
    {
        return in_array(false, $this->checklist, true);
    }

    /**
     * Start the test: create debitor and letter in one go.
     * On success transitions to inspect_pdf.
     * On failure transitions to error.
     */
    public function start(WeblingInvoiceService $invoiceService, LetterService $letterService, WeblingApiSettings $settings): void
    {
        $this->step = 'running';
        $this->errorMessage = null;
        $this->errorStep = null;

        // --- Step 1: Create debitor ---
        try {
            $dueDate = Carbon::now()->addDays(14);

            $addressLines = array_values(array_filter([
                $this->testData['first_name'].' '.$this->testData['last_name'],
                $this->testData['address'],
                $this->testData['zip'].' '.$this->testData['city'],
            ]));

            $response = $invoiceService->createInvoiceFromParams(
                title: 'TEST Spendenrechnung HfM – '.$this->testData['first_name'].' '.$this->testData['last_name'],
                date: Carbon::now(),
                dueDate: $dueDate,
                addressLines: $addressLines,
                periodId: $settings->accounting_period_id,
                invoiceLines: [
                    [
                        'amount' => (float) $this->testData['amount'],
                        'title' => 'Testspende – '.$this->testData['sport'].' für '.fake()->word(),
                    ],
                ],
                accountingPeriodId: $settings->accounting_period_id,
                debitAccountId: $settings->debit_account_id,
                creditAccountId: $settings->credit_account_id,
            );

            if ($response->status() !== 201) {
                $this->failWith(
                    'debitor_creation',
                    'Debitor konnte nicht erstellt werden. Webling antwortete mit Status '.$response->status().': '.substr($response->body(), 0, 300)
                );

                return;
            }

            $debitorId = $response->json();
            if (is_array($debitorId) && isset($debitorId['id'])) {
                $debitorId = $debitorId['id'];
            }
            $this->debitorId = (int) $debitorId;

            $baseUrl = rtrim($settings->api_url, '/');
            $periodId = (int) $settings->accounting_period_id;
            $this->debitorUrl = sprintf('%s/admin#/accounting/%d/debitor/:debitor/editor/%d', $baseUrl, $periodId, $this->debitorId);

        } catch (Exception $e) {
            $this->failWith('debitor_creation', 'Fehler beim Erstellen des Debitors: '.$e->getMessage());

            return;
        }

        // --- Step 2: Create letter / PDF ---
        try {
            $firstName = $this->testData['first_name'];
            $amountStr = 'Fr. '.number_format((float) $this->testData['amount'], 2, '.', '\'');
            $dueStr = $dueDate->format('d.m.Y');

            $letterResponse = $letterService->createInvoiceLetter(
                'TEST Spendenrechnung HfM',
                function (LetterBuilder $b) use ($firstName, $amountStr, $dueStr): void {
                    $b->body1("Liebe:r {$firstName}\n\nDies ist ein automatischer Schnittstellentest. Diese Rechnung wird umgehend wieder gelöscht.\n\n")
                        ->body2("Testbetrag: {$amountStr}\nFällig bis: {$dueStr}\n\nDiese Rechnung wird nach dem Test automatisch gelöscht.\n\nHerzliche Grüsse\nDas Team von Höhenmeter für Menschen")
                        ->withQrInvoice(function ($q) use ($firstName, $amountStr): void {
                            $q->debtorName = [$firstName.' '.$this->testData['last_name']];
                            $q->debtorAddress1 = [$this->testData['address']];
                            $q->debtorAddress2 = [$this->testData['zip'].' '.$this->testData['city']];
                            $q->additionalInformation = 'Schnittstellentest HfM – '.$amountStr;
                        });
                },
                $this->debitorId
            );

            if (! $letterResponse->successful()) {
                $this->failWith(
                    'letter_creation',
                    'Brief/PDF konnte nicht erstellt werden. Webling antwortete mit Status '.$letterResponse->status().': '.substr($letterResponse->body(), 0, 300)
                );

                return;
            }

            $pdfBinary = $letterResponse->body();
            if (! $pdfBinary) {
                $this->failWith('letter_creation', 'Brief/PDF wurde erstellt, aber der Inhalt ist leer. Bitte Webling-Konfiguration prüfen.');

                return;
            }

            $this->tempPdfPath = 'webling/test-'.Str::uuid().'.pdf';
            Storage::disk('local')->put($this->tempPdfPath, $pdfBinary);
            $this->tempPdfSize = strlen($pdfBinary);

        } catch (Exception $e) {
            $this->failWith('letter_creation', 'Fehler beim Erstellen des Briefes/PDFs: '.$e->getMessage());

            return;
        }

        $this->step = 'inspect_pdf';

        Flux::toast(variant: 'success', heading: 'Schritt 1 erfolgreich', text: 'Debitor und PDF wurden erstellt.');
    }

    /** Stream the test PDF to the browser. */
    public function downloadPdf(): mixed
    {
        if (! $this->tempPdfPath || ! Storage::disk('local')->exists($this->tempPdfPath)) {
            Flux::toast(variant: 'danger', heading: 'Fehler', text: 'PDF nicht gefunden.');

            return null;
        }

        $pdf = Storage::disk('local')->get($this->tempPdfPath);
        $filename = 'webling-schnittstellentest-'.now()->format('Ymd-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    /** Advance from inspect_pdf to inspect_link step. */
    public function confirmPdf(): void
    {
        if (! $this->getChecklistDecidedProperty()) {
            Flux::toast(variant: 'danger', heading: 'Checkliste unvollständig', text: 'Bitte jeden Punkt als «OK» oder «Fehler» markieren, bevor du fortfährst.');

            return;
        }

        if ($this->getChecklistHasFailuresProperty()) {
            $failedLabels = array_keys(array_filter($this->checklist, fn ($v) => $v === false));
            Log::warning('Webling interface test: PDF checklist has failures', [
                'failed_items' => $failedLabels,
                'debitor_id' => $this->debitorId,
                'test_data' => $this->testData,
            ]);
        }

        $this->step = 'inspect_link';
    }

    /**
     * Record the direct-link check result and advance to cleanup.
     * $result: true = link works correctly, false = problem found.
     */
    public function confirmLink(bool $result): void
    {
        $this->linkCheckResult = $result;
        $this->completedFullRun = true;

        if (! $result) {
            Log::warning('Webling interface test: direct link check failed', [
                'debitor_id' => $this->debitorId,
                'debitor_url' => $this->debitorUrl,
                'test_data' => $this->testData,
            ]);
        }

        $this->step = 'cleanup';
        $this->runCleanup();
    }

    /**
     * Called when the modal is dismissed mid-test (backdrop click, Escape, or X button).
     * Runs cleanup silently and resets the wizard.
     */
    #[On('modal-cancelled')]
    public function handleModalCancel(): void
    {
        if (in_array($this->step, ['intro', 'done', 'error'], true)) {
            $this->restartWizard();

            return;
        }

        // A test is in progress — clean up silently
        if ($this->debitorId) {
            try {
                $invoiceService = app(WeblingInvoiceService::class);
                $invoiceService->deleteInvoice($this->debitorId);
            } catch (Exception $e) {
                Log::error('Webling interface test: silent cleanup failed after modal dismiss', [
                    'exception' => $e->getMessage(),
                    'debitor_id' => $this->debitorId,
                ]);
            }
        }

        $this->deleteLocalPdf();
        $this->restartWizard();
    }

    /** Delete the test debitor and temp PDF. */
    public function runCleanup(): void
    {
        $invoiceService = app(WeblingInvoiceService::class);
        $cleanupErrors = [];

        if ($this->debitorId) {
            try {
                $deleteResponse = $invoiceService->deleteInvoice($this->debitorId);

                if (! $deleteResponse->successful()) {
                    $cleanupErrors[] = 'Debitor konnte nicht gelöscht werden (Status '.$deleteResponse->status().'). Bitte manuell in Webling löschen (ID: '.$this->debitorId.').';
                } else {
                    $this->debitorId = null;
                    $this->debitorUrl = null;
                }
            } catch (Exception $e) {
                Log::error('Webling interface test: debitor deletion failed', ['exception' => $e->getMessage()]);
                $cleanupErrors[] = 'Fehler beim Löschen des Debitors: '.$e->getMessage();
            }
        }

        $this->deleteLocalPdf();

        if (! empty($cleanupErrors)) {
            $this->step = 'error';
            $this->errorStep = 'cleanup';
            $this->errorMessage = implode(' ', $cleanupErrors);

            return;
        }

        $this->step = 'done';

        $hasAnyIssue = $this->completedFullRun && ($this->getChecklistHasFailuresProperty() || $this->linkCheckResult === false);

        if (! $this->completedFullRun) {
            Flux::toast(variant: 'warning', heading: 'Test unvollständig', text: 'Der Test wurde nicht vollständig durchgeführt. Testdaten wurden bereinigt.');
        } elseif ($hasAnyIssue) {
            Log::error('Webling interface test completed with issues', [
                'checklist' => $this->checklist,
                'link_check_result' => $this->linkCheckResult,
                'test_data' => $this->testData,
            ]);
            Flux::toast(variant: 'warning', heading: 'Test mit Problemen abgeschlossen', text: 'Es wurden Probleme festgestellt. Bitte den Admin kontaktieren.');
        } else {
            Flux::toast(variant: 'success', heading: 'Test abgeschlossen', text: 'Alle Testdaten wurden bereinigt.');
        }
    }

    /** Reset the wizard to start a new test. */
    public function restartWizard(): void
    {
        $this->reset(['step', 'debitorId', 'debitorUrl', 'tempPdfPath', 'tempPdfSize', 'errorMessage', 'errorStep', 'linkCheckResult', 'completedFullRun']);
        $this->checklist = [
            'name_correct' => null,
            'address_correct' => null,
            'amount_correct' => null,
            'qr_present' => null,
            'date_correct' => null,
        ];
        $this->generateTestData();
        $this->step = 'intro';
    }

    public function render(): View
    {
        return view('components.admin.webling-interface-test', [
            'progress' => $this->getProgressProperty(),
            'checklistLabels' => $this->getChecklistLabelsProperty(),
            'checklistDecided' => $this->getChecklistDecidedProperty(),
            'checklistHasFailures' => $this->getChecklistHasFailuresProperty(),
        ]);
    }

    /** Generate realistic-but-fake Swiss test data. */
    protected function generateTestData(): void
    {
        $faker = fake('de_CH');

        $sports = ['Laufen', 'Radfahren', 'Schwimmen', 'Skifahren', 'Wandern'];
        $cities = [['zip' => '8001', 'city' => 'Zürich'], ['zip' => '3001', 'city' => 'Bern'], ['zip' => '4001', 'city' => 'Basel'], ['zip' => '8400', 'city' => 'Winterthur'], ['zip' => '6001', 'city' => 'Luzern']];
        $location = $faker->randomElement($cities);

        $this->testData = [
            'first_name' => $faker->firstName(),
            'last_name' => $faker->lastName(),
            'address' => $faker->streetAddress(),
            'zip' => $location['zip'],
            'city' => $location['city'],
            'amount' => round($faker->numberBetween(5000, 50000) / 100, 2),
            'sport' => $faker->randomElement($sports),
        ];
    }

    /** Transition to the error state, storing context. */
    protected function failWith(string $step, string $message): void
    {
        $this->step = 'error';
        $this->errorStep = $step;
        $this->errorMessage = $message;

        Log::error('Webling interface test failed at step: '.$step, ['message' => $message, 'debitor_id' => $this->debitorId]);
    }

    /** Delete local temp PDF if it exists. */
    protected function deleteLocalPdf(): void
    {
        if ($this->tempPdfPath && Storage::disk('local')->exists($this->tempPdfPath)) {
            Storage::disk('local')->delete($this->tempPdfPath);
            $this->tempPdfPath = null;
            $this->tempPdfSize = null;
        }
    }
}
