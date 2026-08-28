<div>
    <flux:modal name="{{ $this->modalName() }}" wire:model.self="modalOpen" class="md:w-xl" wire:close="close">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Sportler:innen-Anmeldung bearbeiten</flux:heading>
                <flux:text class="mt-2">Anlass, Person, Sportart, Partner:in und Gruppe bleiben unverändert.</flux:text>
            </div>

            <flux:callout icon="exclamation-triangle" variant="warning">
                <flux:callout.text>
                    Vorsicht: Änderungen betreffen reale Personen. Prüfe jede Anpassung sorgfältig.
                </flux:callout.text>
            </flux:callout>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:input
                        label="Geschätzte Runden"
                        wire:model.live.blur="roundsEstimated"
                        type="number"
                        min="1"
                        max="255"
                        required
                    />
                </flux:field>
                <flux:field>
                    <flux:input
                        label="Absolvierte Runden"
                        wire:model.live.blur="roundsDone"
                        type="number"
                        min="0"
                        max="255"
                        required
                    />
                </flux:field>
                <flux:field class="sm:col-span-2">
                    <flux:textarea label="Kommentar" wire:model.live.blur="comment" rows="4" />
                </flux:field>
                <flux:field variant="inline">
                    <flux:switch wire:model.live="adult" label="Volljährig" />
                </flux:field>
                <flux:field variant="inline">
                    <flux:switch wire:model.live="verified" label="Bestätigt" />
                </flux:field>
                <flux:field variant="inline" class="sm:col-span-2">
                    <flux:switch
                        wire:model.live="notifyPreviousDonors"
                        label="Frühere Spender:innen bei Bestätigung informieren"
                    />
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
                    Diese Änderung kann nicht rückgängig gemacht werden. Bestätigungen benachrichtigen früheren
                    Spender:innen, falls aktiviert.
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
