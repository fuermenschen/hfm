<?php

namespace App\Components;

use App\Models\AssociationMember;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Responsive;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use WireUi\Traits\Actions;

class AdminAssociationMemberTable extends PowerGridComponent
{
    use Actions;
    use WithExport;

    public string $sortField = 'first_name';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            Responsive::make(),
            Exportable::make('member')
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
        return AssociationMember::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields();
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

            Column::make('Anmeldung', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::make('Telefon', 'phone_number')
                ->sortable(),

            Column::make('E-Mail', 'email')
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

            Column::make('Kommentar', 'comment')
                ->sortable()
                ->searchable(),
        ];
    }

    public function filters(): array
    {
        return [
        ];
    }
}
