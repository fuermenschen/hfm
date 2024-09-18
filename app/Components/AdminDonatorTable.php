<?php

namespace App\Components;

use App\Models\Donator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Responsive;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class AdminDonatorTable extends PowerGridComponent
{
    use WithExport;

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            Responsive::make(),
            Exportable::make('donator')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            Header::make()
                ->showSearchInput()
                ->showToggleColumns(),
            Footer::make(),
        ];
    }

    public function datasource(): Builder
    {
        return Donator::query()->with(['donations', 'donations.athlete']);
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('numOfDonations', function (Donator $donator) {
                return $donator->donations->count();
            })
            ->add('expectedDonation', function (Donator $donator) {
                $sum = 0;
                foreach ($donator->donations as $donation) {
                    $sum += $donation->amount_per_round * $donation->athlete->rounds_estimated;
                }
                return "Fr. " . number_format($sum, 2, ".", "'");
            })
            ->add('minDonation', function (Donator $donator) {
                $sum = 0;
                foreach ($donator->donations as $donation) {
                    $sum += $donation->amount_min;
                }

                return "Fr. " . number_format($sum, 2, ".", "'");
            })
            ->add('maxDonation', function (Donator $donator) {
                $sum = 0;
                foreach ($donator->donations as $donation) {
                    $sum += $donation->amount_max;
                }

                if ($sum > 0) {
                    return "Fr. " . number_format($sum, 2, ".", "'");
                } else {
                    return "unbegrenzt";
                }
            })
            ->add('created_at_formatted', fn($donator) => Carbon::parse($donator->created_at)->format('d.m.Y'));
    }

    public function columns(): array
    {
        return [

            Column::make('Vorname', 'first_name')
                ->sortable()
                ->searchable()
                ->fixedOnResponsive(),

            Column::make('Nachname', 'last_name')
                ->sortable()
                ->searchable()
                ->fixedOnResponsive(),

            Column::make('Anzahl Spenden', 'numOfDonations')
                ->sortable()
                ->fixedOnResponsive(),

            Column::make('Erwartete Spende', 'expectedDonation')
                ->sortable()
                ->fixedOnResponsive(),

            Column::make('Min. Spende', 'minDonation')
                ->sortable(),

            Column::make('Max. Spende', 'maxDonation')
                ->sortable(),

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

            Column::action('Aktionen')
                ->fixedOnResponsive()

        ];
    }

    public function filters(): array
    {
        return [
        ];
    }

    // Actions
    #[On('downloadInvoice')]
    public function downloadWelcomeLetter($donator_id)
    {
        $donator = Donator::findOrfail($donator_id);
        $donations = $donator->donations()->with(['athlete', 'athlete.partner'])->get();
        $filename = $donator->first_name . '_' . $donator->last_name . '_Rechnung.pdf';
        $pdf = Pdf::loadView('printables.donator_invoice', ['donator' => $donator, 'donations' => $donations])
            ->setPaper('a4', 'portrait');
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $filename);
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
                ->route('show-donator', ['login_token' => $row->login_token])
                ->target('_blank')
                ->tooltip('Als Spender einloggen')
        ];
    }
}
