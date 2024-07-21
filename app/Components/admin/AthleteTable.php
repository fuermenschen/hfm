<?php

namespace App\Components\admin;

use App\Models\Athlete;
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

final class AthleteTable extends PowerGridComponent
{
    use WithExport;

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            Responsive::make(),
            Exportable::make('athlete')
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
        return Athlete::query()->with('sportType', 'partner', 'donations');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('verified', function (Athlete $athlete) {
                return $athlete->verified ? 'Ja' : 'Nein';
            })
            ->add('sportType.name')
            ->add('partner.name')
            ->add('number_of_donations', fn($athlete) => $athlete->donations->count())
            ->add('created_at_formatted', fn($athlete) => Carbon::parse($athlete->created_at)->format('d.m.Y'));
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

            Column::make('Bestätigt', 'verified')
                ->sortable(),

            Column::make('Sportart', 'sportType.name')
                ->sortable(),

            Column::make('Partner', 'partner.name')
                ->sortable(),

            Column::make('Runden', 'rounds_estimated')
                ->sortable()
                ->fixedOnResponsive(),

            Column::make('Spenden', 'number_of_donations')
                ->sortable()
                ->fixedOnResponsive(),

            Column::make('Anmeldung', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Action')
                ->fixedOnResponsive()
        ];
    }

    public function filters(): array
    {
        return [
        ];
    }

    #[On('downloadWelcomeLetter')]
    public function downloadWelcomeLetter($athlete_id)
    {
        $athlete = Athlete::findOrfail($athlete_id);
        $pdf = Pdf::loadView('printables.athlete_welcome_letter', $athlete->toArray(), [], "UTF-8");
        //return $pdf->download('aaa.pdf');
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'aaa.pdf');
    }

    public function actions(Athlete $row): array
    {
        return [
            Button::add('edit')
                ->slot('Brief')
                ->id()
                ->class('pg-btn-white dark:ring-pg-primary-600 dark:border-pg-primary-600 dark:hover:bg-pg-primary-700 dark:ring-offset-pg-primary-800 dark:text-pg-primary-300 dark:bg-pg-primary-700')
                ->dispatch('downloadWelcomeLetter', ['athlete_id' => $row->id])
        ];
    }

    /*
    public function actionRules($row): array
    {
       return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
}
