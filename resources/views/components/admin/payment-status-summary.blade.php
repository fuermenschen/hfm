<div>
    <flux:modal name="payment-status-summary" class="min-w-104 sm:w-full md:w-xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Zahlungsstatus – Zusammenfassung</flux:heading>
                <flux:text class="mt-1 text-sm opacity-80">Aktueller Stand nach dem Abgleich.</flux:text>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <flux:card class="p-4">
                    <flux:heading>Nicht erstellt</flux:heading>
                    <div class="mt-2 text-2xl font-semibold">{{ $notCreated }}</div>
                </flux:card>
                <flux:card class="p-4">
                    <flux:heading>Erstellt</flux:heading>
                    <div class="mt-2 text-2xl font-semibold">{{ $created }}</div>
                </flux:card>
                <flux:card class="p-4">
                    <flux:heading>Gesendet</flux:heading>
                    <div class="mt-2 text-2xl font-semibold">{{ $sent }}</div>
                </flux:card>
                <flux:card class="p-4">
                    <flux:heading>Überfällig</flux:heading>
                    <div class="mt-2 text-2xl font-semibold">{{ $overdue }}</div>
                </flux:card>
                <flux:card class="p-4">
                    <flux:heading>Teilbezahlt</flux:heading>
                    <div class="mt-2 text-2xl font-semibold">{{ $partiallyPaid }}</div>
                </flux:card>
                <flux:card class="p-4">
                    <flux:heading>Bezahlt</flux:heading>
                    <div class="mt-2 text-2xl font-semibold">{{ $paid }}</div>
                </flux:card>
                <flux:card class="p-4">
                    <flux:heading>Abgeschrieben</flux:heading>
                    <div class="mt-2 text-2xl font-semibold">{{ $writeoff }}</div>
                </flux:card>
                <flux:card class="p-4">
                    <flux:heading>Gelöscht</flux:heading>
                    <div class="mt-2 text-2xl font-semibold">{{ $remoteDeleted }}</div>
                </flux:card>
                <flux:card class="p-4 sm:col-span-2">
                    <flux:heading>Unbekannt</flux:heading>
                    <div class="mt-2 text-2xl font-semibold">{{ $unknown }}</div>
                </flux:card>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="primary" x-on:click="$flux.modal('payment-status-summary').close()"
                    >Schliessen</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
