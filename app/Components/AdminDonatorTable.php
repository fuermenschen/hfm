<?php

namespace App\Components;

use App\Models\Donator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use WireUi\Traits\Actions;

class AdminDonatorTable extends PowerGridComponent
{
    use Actions;
    use WithExport;

    public string $sortField = 'first_name';

    public string $tableName = 'admin-donator-table';

    public function header(): array
    {
        return [
            Button::add('download')
                ->slot('Download')
                ->class('focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 rounded-md border-0 bg-transparent py-2 px-3 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-auto')
                ->dispatch('batchDownload', [])
                ->tooltip('Rechnung für ausgewählte Spender:innen herunterladen'),
            Button::add('gesendet')
                ->slot('gesendet')
                ->class('focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 rounded-md border-0 bg-transparent py-2 px-3 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-auto')
                ->dispatch('invoiceSent', ['sent' => true])
                ->tooltip('Rechnungen als gesendet markieren'),
            Button::add('nicht gesendet')
                ->slot('nicht gesendet')
                ->class('focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 rounded-md border-0 bg-transparent py-2 px-3 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-auto')
                ->dispatch('invoiceSent', ['sent' => false])
                ->tooltip('Rechnungen als nicht gesendet markieren'),
            Button::add('bezahlt')
                ->slot('bezahlt')
                ->class('focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 rounded-md border-0 bg-transparent py-2 px-3 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-auto')
                ->dispatch('invoicePaid', ['paid' => true])
                ->tooltip('Rechnungen als bezahlt markieren'),
            Button::add('nicht bezahlt')
                ->slot('nicht bezahlt')
                ->class('focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 rounded-md border-0 bg-transparent py-2 px-3 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-auto')
                ->dispatch('invoicePaid', ['paid' => false])
                ->tooltip('Rechnungen als nicht bezahlt markieren'),
        ];
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::responsive(),
            PowerGrid::exportable('donator')
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
            ->add('don_id', function (Donator $donator) {
                return 'DON-'.sprintf('25%04d', $donator->id);
            })
            ->add('numOfDonations', function (Donator $donator) {
                return $donator->donations->count();
            })
            ->add('donations_sum', function (Donator $donator) {
                $this_sum = 0;
                foreach ($donator->donations as $donation) {
                    $athlete_sum = $donation->athlete->rounds_done * $donation->amount_per_round;
                    if ($donation->amount_min) {
                        if ($athlete_sum < $donation->amount_min) {
                            $athlete_sum = $donation->amount_min;
                        }
                    }
                    if ($donation->amount_max) {
                        if ($athlete_sum > $donation->amount_max) {
                            $athlete_sum = $donation->amount_max;
                        }
                    }
                    $this_sum += $athlete_sum;
                }

                return 'Fr. '.number_format($this_sum, 2, '.', "'");
            })
            ->add('created_at_formatted', fn ($donator) => Carbon::parse($donator->created_at)->format('d.m.Y'))
            ->add('invoice_sent_at_formatted', fn ($donator) => $donator->invoice_sent_at ? Carbon::parse($donator->invoice_sent_at)->format('d.m.Y') : null)
            ->add('invoice_paid_at_formatted', fn ($donator) => $donator->invoice_paid_at ? Carbon::parse($donator->invoice_paid_at)->format('d.m.Y') : null);
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

            Column::make('Adresse', 'address')
                ->sortable()
                ->searchable(),

            Column::make('PLZ', 'zip_code')
                ->sortable()
                ->searchable(),

            Column::make('Ort', 'city')
                ->sortable()
                ->searchable(),

            Column::make('Rechnung gesendet', 'invoice_sent')
                ->sortable()
                ->toggleable()
                ->fixedOnResponsive(),

            Column::make('Rechnung gesendet am', 'invoice_sent_at_formatted', 'invoice_sent_at')
                ->sortable(),

            Column::make('Rechnung bezahlt', 'invoice_paid')
                ->sortable()
                ->toggleable()
                ->fixedOnResponsive(),

            Column::make('Rechnung bezahlt am', 'invoice_paid_at_formatted', 'invoice_paid_at')
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

    // Actions
    public function onUpdatedToggleable(string|int $id, string $field, string $value): void
    {
        $worked = false;

        try {

            $donator = Donator::findOrFail($id);

            switch ($field) {
                case 'invoice_sent':
                    $donator->invoice_sent = (bool) $value;
                    if ($value) {
                        $donator->invoice_sent_at = Carbon::now();
                    } else {
                        $donator->invoice_sent_at = null;
                    }
                    $donator->save();
                    $worked = true;
                    break;

                case 'invoice_paid':
                    $donator->invoice_paid = (bool) $value;
                    if ($value) {
                        $donator->invoice_paid_at = Carbon::now();
                    } else {
                        $donator->invoice_paid_at = null;
                    }
                    $donator->save();
                    $worked = true;
                    break;
            }

            $this->skipRender();
        } catch (\Exception $e) {
            $worked = false;
        }

        if ($worked) {
            $this->notification()->success('Erfolgreich gespeichert.');
        } else {
            $this->notification()->error('Fehler beim Speichern.');
        }
    }

    #[On('invoiceSent')]
    public function invoiceSent(bool $sent)
    {
        if ($this->checkboxValues) {
            foreach ($this->checkboxValues as $id) {
                $donator = Donator::findOrfail($id);
                $donator->invoice_sent = $sent;
                if ($sent) {
                    $donator->invoice_sent_at = Carbon::now();
                } else {
                    $donator->invoice_sent_at = null;
                }
                $donator->save();
            }
            $this->notification()->success('Rechnungen als gesendet markiert (Seite muss aktualisiert werden)');
        } else {
            $this->notification()->error('Keine Spender:innen ausgewählt.');
        }

        $this->checkboxValues = [];

    }

    #[On('invoicePaid')]
    public function invoicePaid(bool $paid)
    {
        if ($this->checkboxValues) {
            foreach ($this->checkboxValues as $id) {
                $donator = Donator::findOrfail($id);
                $donator->invoice_paid = $paid;
                if ($paid) {
                    $donator->invoice_paid_at = Carbon::now();
                } else {
                    $donator->invoice_paid_at = null;
                }
                $donator->save();
            }
            $this->notification()->success('Rechnungen als bezahlt markiert (Seite muss aktualisiert werden)');
        } else {
            $this->notification()->error('Keine Spender:innen ausgewählt.');
        }

        $this->checkboxValues = [];

    }

    #[On('batchDownload')]
    public function batchDownload()
    {
        // create a zip file with all invoices
        $zip = new \ZipArchive;

        $filename = 'Rechnungen_'.Carbon::now()->format('Y-m-d').'.zip';

        if ($zip->open(storage_path('app/'.$filename), \ZipArchive::CREATE) === true) {
            if ($this->checkboxValues) {
                foreach ($this->checkboxValues as $id) {
                    $this_invoice = $this->downloadInvoice($id, false);
                    $zip->addFromString($this_invoice['filename'], $this_invoice['pdf']->output());
                }
            }
            $zip->close();
        }

        return response()->download(storage_path('app/'.$filename))->deleteFileAfterSend(true);
    }

    #[On('downloadInvoice')]
    public function downloadInvoice($donator_id, $download = true)
    {
        $donator = Donator::findOrfail($donator_id);
        $donations = $donator->donations()->with(['athlete', 'athlete.partner'])->get();
        $filename = sprintf('DON-24%04d_', $donator_id).$donator->first_name.'_'.$donator->last_name.'_Rechnung.pdf';
        $pdf = Pdf::loadView('printables.donator_invoice', ['donator' => $donator, 'donations' => $donations])
            ->setPaper('a4', 'portrait');

        // dd($pdf);

        if ($download) {
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, $filename);
        } else {
            return ['pdf' => $pdf, 'filename' => $filename];
        }
    }

    public function actions(Donator $row): array
    {
        return [
            Button::add('Rechnung')
                ->slot('PDF Rechnung')
                ->class('pg-btn-white dark:ring-pg-primary-600 dark:border-pg-primary-600 dark:hover:bg-pg-primary-700 dark:ring-offset-pg-primary-800 dark:text-pg-primary-300 dark:bg-pg-primary-700')
                ->dispatch('downloadInvoice', ['donator_id' => $row->id])
                ->tooltip('Rechnung herunterladen'),
            Button::add('loginAsDonator')
                ->slot('Login')
                ->class('pg-btn-white dark:ring-pg-primary-600 dark:border-pg-primary-600 dark:hover:bg-pg-primary-700 dark:ring-offset-pg-primary-800 dark:text-pg-primary-300 dark:bg-pg-primary-700')
                ->route('show-donator', ['login_token' => $row->login_token], '_blank')
                ->tooltip('Als Spender einloggen'),
        ];
    }
}
