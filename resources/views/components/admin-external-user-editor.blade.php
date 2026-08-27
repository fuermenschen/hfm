<div>
    <flux:modal name="{{ $this->modalName() }}" wire:model.self="modalOpen" class="md:w-xl" wire:close="close">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Person bearbeiten</flux:heading>
                <flux:text class="mt-2">Kontaktdaten und persönliche Angaben aktualisieren.</flux:text>
            </div>

            <flux:callout icon="exclamation-triangle" variant="warning">
                <flux:callout.text>
                    Vorsicht: Änderungen betreffen reale Personen. Prüfe jede Anpassung sorgfältig.
                </flux:callout.text>
            </flux:callout>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:input label="Vorname" wire:model.live.blur="firstName" required />
                </flux:field>
                <flux:field>
                    <flux:input label="Nachname" wire:model.live.blur="lastName" required />
                </flux:field>
                <flux:field class="sm:col-span-2">
                    <flux:input label="Adresse" wire:model.live.blur="address" autocomplete="street-address" required />
                </flux:field>
                <flux:field>
                    <flux:input label="PLZ" wire:model.live.blur="zipCode" autocomplete="postal-code" required />
                </flux:field>
                <flux:field>
                    <flux:input label="Ort" wire:model.live.blur="city" autocomplete="address-level2" required />
                </flux:field>
                <flux:field>
                    <flux:select label="Wohnsitzland" wire:model.live="countryOfResidence">
                        <flux:select.option value="CH">Schweiz</flux:select.option>
                        <flux:select.option value="DE">Deutschland</flux:select.option>
                        <flux:select.option value="AT">Österreich</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:input
                        label="Telefon"
                        wire:model.live.blur="phoneNumber"
                        autocomplete="tel"
                        type="tel"
                        placeholder="+41 79 123 45 67"
                        required
                    />
                </flux:field>
                <flux:field class="sm:col-span-2">
                    <flux:input
                        label="E-Mail"
                        wire:model.live.blur="email"
                        autocomplete="email"
                        type="email"
                        required
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
                <flux:text class="mt-2">Folgende Angaben werden bei dieser Person geändert:</flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($this->changedFieldLabels() as $label)
                    <flux:badge variant="warning" size="sm">{{ $label }}</flux:badge>
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
