<?php

namespace App\Components;

use App\Jobs\CreateDonorInvoice;
use App\Jobs\DeleteDonorInvoiceDebitor;
use App\Models\Donator;
use App\Services\DonorService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use WireUi\Traits\Actions;

class AdminDonatorTable extends PowerGridComponent
{
    use Actions;
    use WithExport;

    public string $sortField = 'first_name';

    public string $tableName = 'admin-donator-table';

    protected DonorService $donorService;

    public function boot(DonorService $donorService): void
    {
        $this->donorService = $donorService;
    }

    public function header(): array
    {
        return [];
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
        return Donator::query()->with(['donations', 'donations.athlete', 'donations.athlete.partner']);
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

            Column::make('Rechnungsbetrag', 'donations_sum')
                ->searchable(),

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

            Column::make('Rechnung gesendet am', 'invoice_sent_at_formatted', 'invoice_sent_at')
                ->sortable(),

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
                $this->notification()->info(title: 'Bereits vorhanden', description: 'Für '.$donor->privacy_name.' ist bereits eine Rechnung erstellt worden. Es gibt nichts zu tun.');

                return;
            }
            CreateDonorInvoice::dispatchSync($donor);
            $this->notification()->success('Rechnung erstellt', 'Die Rechnung für '.$donor->privacy_name.' wurde erfolgreich erstellt.');
        } catch (\Throwable $e) {
            \Log::error('Error creating donor invoice', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
            $this->notification()->error(title: 'Fehler beim Erstellen der Rechnung', description: $e->getMessage());
        }
    }

    #[On('confirmDeleteDonorInvoice')]
    public function confirmDeleteDonorInvoice(int $donor_id): void
    {
        $donor = Donator::find($donor_id);
        $name = $donor?->privacy_name ?? 'diese:n Spender:in';

        $this->dialog()->confirm([
            'title' => 'Rechnung löschen?',
            'description' => "Möchtest du die Rechnung für {$name} wirklich löschen? Dies entfernt auch die lokal gespeicherte PDF und löscht die Rechnung und <strong>sämtliche verknüpften Buchungen auf Webling.</strong>",
            'icon' => 'exclamation',
            'accept' => [
                'label' => 'Ja, löschen',
                'method' => 'deleteDonorInvoice',
                'params' => $donor_id,
            ],
            'reject' => [
                'label' => 'Abbrechen',
            ],
        ]);
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
                $this->notification()->info(title: 'Nichts zu löschen', description: 'Für '.$donor->privacy_name.' sind keine Rechnungseinträge vorhanden.');

                return;
            }

            \Log::info('Deleting donor invoice debitor', ['donor_id' => $donor_id]);
            DeleteDonorInvoiceDebitor::dispatchSync($donor);
            $this->notification()->success('Rechnung gelöscht', 'Die Rechnungseinträge für '.$donor->privacy_name.' wurden gelöscht.');
        } catch (\Throwable $e) {
            \Log::error('Error deleting donor invoice', ['error' => $e->getMessage(), 'donor_id' => $donor_id]);
            $this->notification()->error(title: 'Fehler beim Löschen der Rechnung', description: $e->getMessage());
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
                $this->notification()->error(title: 'Kein PDF gefunden', description: 'Für '.$donor->privacy_name.' ist noch kein Rechnungs-PDF vorhanden.');

                return null;
            }

            $disk = (string) ($weblingData['letter_pdf']['disk'] ?? 'local');
            $path = (string) ($weblingData['letter_pdf']['path'] ?? '');

            if ($path === '' || ! Storage::disk($disk)->exists($path)) {
                $this->notification()->error(title: 'Datei nicht gefunden', description: 'Das gespeicherte Rechnungs-PDF konnte nicht gefunden werden.');

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
            $this->notification()->error(title: 'Fehler beim Herunterladen', description: $e->getMessage());

            return null;
        }
    }

    public function actions(Donator $row): array
    {
        // Actions are rendered from a dedicated Blade view via actionsFromView()
        return [];
    }

    public function actionsFromView(mixed $row): View
    {
        return view('powergrid.admin-donor-actions', [
            'row' => $row,
        ]);
    }
}
