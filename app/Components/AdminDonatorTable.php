<?php

namespace App\Components;

use App\Jobs\CreateDonorInvoiceDebitor;
use App\Models\Donator;
use App\Services\DonorService;
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
                $lines = $this->donorService->collectInvoiceData($donator);
                $sum = array_sum(array_column($lines, 'total'));

                return 'Fr. '.number_format($sum, 2, '.', "'");
            })
            ->add('created_at_formatted', fn ($donator) => Carbon::parse($donator->created_at)->format('d.m.Y'))
            ->add('invoice_sent_at_formatted', fn ($donator) => $donator->invoice_sent_at ? Carbon::parse($donator->invoice_sent_at)->format('d.m.Y H:i') : null)
            ->add('country_of_residence', fn ($donator) => $donator->country_of_residence);
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
    public function createDonorInvoice(int $donator_id): void
    {
        try {
            $donor = Donator::findOrFail($donator_id);
            $this->notification()->info(title: 'Einen Moment bitte...', description: 'Die Rechnung für '.$donor->privacy_name.' wird erstellt.');
            \Log::info('Creating donor invoice', ['donator_id' => $donator_id]);
            CreateDonorInvoiceDebitor::dispatchSync($donor);
            $this->notification()->success('Rechnung erstellt', 'Die Rechnung für '.$donor->privacy_name.' wurde erfolgreich erstellt.');
        } catch (\Throwable $e) {
            \Log::error('Error creating donor invoice', ['error' => $e->getMessage(), 'donator_id' => $donator_id]);
            $this->notification()->error(title: 'Fehler beim Erstellen der Rechnung', description: $e->getMessage());
        }
    }

    public function actions(Donator $row): array
    {
        return [
            Button::add('createInvoice')
                ->slot('Rechnung erstellen')
                ->class('pg-btn-white dark:ring-pg-primary-600 dark:border-pg-primary-600 dark:hover:bg-pg-primary-700 dark:ring-offset-pg-primary-800 dark:text-pg-primary-300 dark:bg-pg-primary-700')
                ->dispatch('createDonorInvoice', ['donator_id' => $row->id])
                ->tooltip('Rechnung erstellen'),
            Button::add('loginAsDonator')
                ->slot('Login')
                ->class('pg-btn-white dark:ring-pg-primary-600 dark:border-pg-primary-600 dark:hover:bg-pg-primary-700 dark:ring-offset-pg-primary-800 dark:text-pg-primary-300 dark:bg-pg-primary-700')
                ->route('show-donator', ['login_token' => $row->login_token], '_blank')
                ->tooltip('Als Spender einloggen'),
        ];
    }
}
