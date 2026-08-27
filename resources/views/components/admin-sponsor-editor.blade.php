<flux:modal name="{{ $this->modalName() }}" wire:model.self="modalOpen" class="md:w-xl" wire:close="close">
    <form wire:submit="save" class="space-y-6">
        <div>
            @if ($sponsorId === null)
                <flux:heading size="lg">Sponsor:in erstellen</flux:heading>
                <flux:text class="mt-2">Neuen Sponsor:innen-Eintrag speichern.</flux:text>
            @else
                <flux:heading size="lg">Sponsor:in bearbeiten</flux:heading>
                <flux:text class="mt-2">Änderungen am Sponsor:innen-Eintrag speichern.</flux:text>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:field class="sm:col-span-2">
                <flux:label>Name</flux:label>
                <flux:input wire:model.live.blur="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field class="sm:col-span-2">
                <div class="flex items-center gap-1">
                    <flux:label>Beschreibung</flux:label>
                    <x-admin.field-info
                        label="Beschreibung"
                        text="Diese allgemeine Beschreibung stellt die Sponsororganisation vor und erscheint im Detailfenster der Sponsorenkarte. Anlassspezifische Leistungen werden separat beim jeweiligen Anlass erfasst."
                    />
                </div>
                <flux:textarea wire:model.live.blur="description" />
                <flux:error name="description" />
            </flux:field>

            <x-admin.file-select
                directory="sponsors"
                extensions="svg,png,jpg,jpeg,webp"
                recursive
                label="Logo"
                help="Dieses Logo wird auf der öffentlichen Startseite als Sponsorenkarte angezeigt."
                wire:model.live="logoFilename"
                :selected="$logoFilename"
            />

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>URL</flux:label>
                    <x-admin.field-info
                        label="URL"
                        text="Diese Adresse wird über die Schaltfläche «Zur Website» im Detailfenster der Sponsorenkarte geöffnet."
                    />
                </div>
                <flux:input wire:model.live.blur="url" />
                <flux:error name="url" />
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
