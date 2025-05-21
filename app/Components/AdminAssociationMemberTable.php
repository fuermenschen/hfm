<?php

namespace App\Components;

use App\Models\AssociationMember;
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

class AdminAssociationMemberTable extends PowerGridComponent
{
    use Actions;
    use WithExport;

    public string $tableName = 'admin-association-member-table';

    public string $sortField = 'first_name';

    public function header(): array
    {
        return [
            Button::add('batchMessage')
                ->slot('Nachricht Senden')
                ->class('focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 rounded-md border-0 bg-transparent py-2 px-3 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-auto')
                ->dispatch('sendBatchMail', [])
                ->tooltip('Nachricht an ausgewählte Mitglieder senden'),
        ];
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::responsive(),
            PowerGrid::exportable('member')
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
        return AssociationMember::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('created_at_formatted', fn ($athlete) => Carbon::parse($athlete->created_at)->format('d.m.Y'));
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

            Column::action('Aktionen')
                ->fixedOnResponsive(),
        ];
    }

    #[On('sendMail')]
    public function sendMail($member_ids)
    {
        $this->dispatch('openMemberMessageModal', member_ids: $member_ids)->to('admin-association-member-message');
    }

    #[On('sendBatchMail')]
    public function sendBatchMail()
    {
        $this->dispatch('openMemberMessageModal', member_ids: $this->checkboxValues)->to('admin-association-member-message');
    }

    public function actions(AssociationMember $row): array
    {
        return [
            Button::add('Nachricht')
                ->slot('Nachricht')
                ->class('pg-btn-white dark:ring-pg-primary-600 dark:border-pg-primary-600 dark:hover:bg-pg-primary-700 dark:ring-offset-pg-primary-800 dark:text-pg-primary-300 dark:bg-pg-primary-700')
                ->dispatch('sendMail', ['member_ids' => [$row->id]])
                ->tooltip('E-Mail-Nachricht versenden'),
        ];
    }
}
