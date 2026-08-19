<flux:card>
    <div class="mb-6">
        <flux:heading size="lg">SVGs normalisieren</flux:heading>
        <flux:subheading>Ergänzt bei SVGs ohne definierte Kontur <code>stroke="none"</code>. Das verhindert schwarze Ränder bei der Story-Bild-Erstellung.</flux:subheading>
    </div>

    <flux:button wire:click="normalize" icon="wrench-screwdriver" wire:confirm="Alle SVGs im öffentlichen Speicher normalisieren?">SVGs normalisieren</flux:button>

    @if ($result !== null)
        <flux:text class="mt-4">{{ $result['normalized'] }} angepasst, {{ $result['unchanged'] }} bereits korrekt, {{ count($result['failed']) }} fehlgeschlagen.</flux:text>

        @if ($result['failed'] !== [])
            <flux:callout class="mt-4" variant="warning" heading="Nicht normalisierte SVGs">
                <flux:callout.text>
                    @foreach ($result['failed'] as $failure)
                        <div>{{ $failure['path'] }}: {{ $failure['message'] }}</div>
                    @endforeach
                </flux:callout.text>
            </flux:callout>
        @endif
    @endif
</flux:card>
