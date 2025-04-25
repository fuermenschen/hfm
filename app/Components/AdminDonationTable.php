<?php

namespace App\Components;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Responsive;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

class AdminDonationTable extends PowerGridComponent
{
    use WithExport;

    public string $sortField = 'created_at';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            Responsive::make(),
            Exportable::make('donation')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            Header::make()
                ->showSearchInput()
                ->showToggleColumns(),
            Footer::make()
                ->showPerPage(10, [10, 25, 50, 100, 200])
                ->showRecordCount(mode: 'short'),
        ];
    }

    public function datasource(): Builder
    {
        return Donation::query()->with('athlete', 'donator');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('athlete', function (Donation $donation) {
                return $donation->athlete->privacy_name;
            })
            ->add('donator', function (Donation $donation) {
                return $donation->donator->privacy_name;
            })
            ->add('verified', function (Donation $donation) {
                return $donation->verified ? 'Ja' : 'Nein';
            })
            ->add('amount_per_round', function (Donation $donation) {
                return 'Fr. '.number_format($donation->amount_per_round, 2, '.', "'");
            })
            ->add('estimated_amount', function (Donation $donation) {
                return 'Fr. '.number_format($donation->amount_per_round * $donation->athlete->rounds_estimated, 2, '.', "'");
            })
            ->add('min_amount', function (Donation $donation) {

                if ($donation->amount_min) {
                    return 'Fr. '.number_format($donation->amount_min, 2, '.', "'");
                } else {
                    return 'unbegrenzt';
                }
            })
            ->add('max_amount', function (Donation $donation) {

                if ($donation->amount_max) {
                    return 'Fr. '.number_format($donation->amount_max, 2, '.', "'");
                } else {
                    return 'unbegrenzt';
                }
            })
            ->add('created_at_formatted', fn ($donator) => Carbon::parse($donator->created_at)->format('d.m.Y'));
    }

    public function columns(): array
    {
        return [

            Column::make('Spender:in', 'donator', 'donator_id')
                ->sortable()
                ->searchable()
                ->fixedOnResponsive(),

            Column::make('Sportler:in', 'athlete', 'athlete_id')
                ->sortable()
                ->searchable()
                ->fixedOnResponsive(),

            Column::make('Bestätigt', 'verified')
                ->sortable(),

            Column::make('Betrag pro Runde', 'amount_per_round')
                ->sortable(),

            Column::make('Geschätzter Betrag', 'estimated_amount')
                ->fixedOnResponsive(),

            Column::make('Minimaler Betrag', 'min_amount'),

            Column::make('Maximaler Betrag', 'max_amount'),

            Column::make('Erstellt am', 'created_at_formatted')
                ->sortable(),

            Column::make('Kommentar', 'comment')
                ->sortable()
                ->searchable()
                ->fixedOnResponsive(),

        ];
    }

    public function filters(): array
    {
        return [
        ];
    }
}
