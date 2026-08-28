<div>
    <flux:modal name="{{ $this->modalName() }}" wire:model.self="modalOpen" class="md:w-xl" wire:close="close">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Spende bearbeiten</flux:heading>
                <flux:text class="mt-2">Spender:in und unterstützte Sportler:in bleiben unverändert.</flux:text>
            </div>

            <flux:callout icon="exclamation-triangle" variant="warning">
                <flux:callout.text>
                    Vorsicht: Änderungen betreffen reale Personen. Prüfe jede Anpassung sorgfältig.
                </flux:callout.text>
            </flux:callout>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:input
                        label="Betrag pro Runde"
                        wire:model.live.blur="amountPerRound"
                        prefix="Fr."
                        type="number"
                        step="0.01"
                        min="0.05"
                        required
                    />
                </flux:field>
                <flux:field>
                    <flux:input
                        label="Minimaler Betrag"
                        wire:model.live.blur="amountMin"
                        prefix="Fr."
                        type="number"
                        step="0.01"
                        min="0.05"
                    />
                </flux:field>
                <flux:field>
                    <flux:input
                        label="Maximaler Betrag"
                        wire:model.live.blur="amountMax"
                        prefix="Fr."
                        type="number"
                        step="0.01"
                        min="1"
                    />
                </flux:field>
                <flux:field class="sm:col-span-2">
                    <flux:textarea label="Kommentar" wire:model.live.blur="comment" rows="4" />
                </flux:field>
                <flux:field variant="inline" class="sm:col-span-2">
                    <flux:switch wire:model.live="verified" label="Bestätigt" />
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="close">Abbrechen</flux:button>
                <flux:button type="submit" variant="primary" wire:target="save" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Speichern</span>
                    <span wire:loading wire:target="save">Speichert...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="{{ $this->confirmModalName() }}" class="sm:w-md" wire:close="cancelSave">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Änderungen bestätigen</flux:heading>
                <flux:text class="mt-2">Folgende Angaben werden bei {{ $this->confirmSubject() }} geändert:</flux:text>
            </div>

            <div class="space-y-1.5">
                @foreach ($this->changedFields() as $change)
                    <p class="text-sm">
                        <span class="font-medium">{{ $change['label'] }}:</span>
                        <span class="text-zinc-500 line-through">{{ $change['before'] }}</span>
                        → {{ $change['after'] }}
                    </p>
                @endforeach
            </div>

            <flux:callout icon="exclamation-triangle" variant="warning">
                <flux:callout.text>
                    Diese Änderung kann nicht rückgängig gemacht werden. Bitte bestätige nur, wenn alle Angaben korrekt
                    sind.
                </flux:callout.text>
            </flux:callout>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="cancelSave">Zurück</flux:button>
                <flux:button
                    type="button"
                    variant="primary"
                    wire:click="confirmSave"
                    wire:loading.attr="disabled"
                    wire:target="confirmSave"
                >
                    <span wire:loading.remove wire:target="confirmSave">Änderungen übernehmen</span>
                    <span wire:loading wire:target="confirmSave">Speichert...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
