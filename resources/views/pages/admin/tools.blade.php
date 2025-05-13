@php

    use App\Models\Athlete;
    use App\Models\SportType;

@endphp

@component('layouts.admin', ['title' => "Werkzeuge"])

    @section('content')

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
            <flux:card>
                <div class="mb-6">
                    <flux:heading size="lg">Spendenrechnung erstellen</flux:heading>
                    <flux:subheading>Für Spenden, die direkt an den Trägerverein gehen, kannst du hier eine Rechnung
                        erstellen
                    </flux:subheading>
                </div>

                <flux:modal.trigger name="create-association-donation-invoice">
                    <flux:button>Rechnung erstellen</flux:button>
                </flux:modal.trigger>

                <flux:modal name="create-association-donation-invoice" class="space-y-6 sm:w-full md:w-xl">
                    <div>
                        <flux:heading size="lg">Spendenrechnung erstellen</flux:heading>
                        <flux:subheading>Erstellt eine Spendenrechnung zu Gunsten des Trägervereins.</flux:subheading>
                    </div>

                    @livewire('admin-association-donation-invoice-form')
                </flux:modal>
            </flux:card>
        </div>
    @endsection

@endcomponent
