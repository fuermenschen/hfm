<flux:modal name="{{ $this->modalName() }}" wire:model.self="modalOpen" class="md:w-xl" wire:close="close">
    <form wire:submit="save" class="space-y-6">
        <div>
            @if ($partnerId === null)
                <flux:heading size="lg">Partner:in erstellen</flux:heading>
                <flux:text class="mt-2">Neuen Partner:innen-Eintrag speichern.</flux:text>
            @else
                <flux:heading size="lg">Partner:in bearbeiten</flux:heading>
                <flux:text class="mt-2">Änderungen am Partner:innen-Eintrag speichern.</flux:text>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:field class="sm:col-span-2">
                <flux:input label="Name" wire:model.live.blur="name" />
                <flux:error name="name" />
            </flux:field>

            <x-admin.file-select
                directory="partners"
                extensions="svg,png,jpg,jpeg,webp"
                recursive
                label="Logo hell"
                help="Dieses Logo wird auf der öffentlichen Startseite in der hellen Darstellung verwendet. Für jede Partnerorganisation werden eine helle und eine dunkle Variante benötigt."
                wire:model.live="logoLightFilename"
                :selected="$logoLightFilename"
            />

            <x-admin.file-select
                directory="partners"
                extensions="svg,png,jpg,jpeg,webp"
                recursive
                label="Logo dunkel"
                help="Dieses Logo wird auf der öffentlichen Startseite in der dunklen Darstellung verwendet. Für jede Partnerorganisation werden eine helle und eine dunkle Variante benötigt."
                wire:model.live="logoDarkFilename"
                :selected="$logoDarkFilename"
            />

            <flux:field class="sm:col-span-2">
                <div class="flex items-center gap-1">
                    <flux:label>Kurztext</flux:label>
                    <x-admin.field-info
                        label="Kurztext"
                        text="Dieser allgemeine Kurztext beschreibt die begünstigte Organisation. Er erscheint auf der öffentlichen Startseite und gilt unabhängig vom Anlass."
                    />
                </div>
                <flux:textarea wire:model.live.blur="beneficiaryBlurb" />
                <flux:error name="beneficiaryBlurb" />
            </flux:field>

            <flux:field class="sm:col-span-2">
                <div class="flex items-center gap-1">
                    <flux:label>URL</flux:label>
                    <x-admin.field-info
                        label="URL"
                        text="Diese Adresse wird auf der öffentlichen Startseite mit der Partnerorganisation und ihrem Logo verlinkt."
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
