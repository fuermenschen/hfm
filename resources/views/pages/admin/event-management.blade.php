@component('layouts.admin', ['title' => 'Rundenbüro'])
    @section('content')
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:subheading>
                    Startnummern vergeben und Runden zählen – für den Einsatz am Anlass-Tag.
                </flux:subheading>
            </div>

            <flux:tab.group>
                <flux:tabs>
                    <flux:tab name="start-numbers">Startnummern</flux:tab>
                    <flux:tab name="rounds">Runden zählen</flux:tab>
                </flux:tabs>

                <flux:tab.panel name="start-numbers" class="pt-6">
                    @livewire('admin-start-numbers')
                </flux:tab.panel>

                <flux:tab.panel name="rounds" class="pt-6">
                    @livewire('admin-round-counter')
                </flux:tab.panel>
            </flux:tab.group>
        </div>
    @endsection
@endcomponent
