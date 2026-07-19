<form wire:submit="save" class="space-y-6">
    <flux:card class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">Sponsor:innen zuordnen</flux:heading>
            <flux:subheading>Darstellung und Anlassbeitrag werden pro Anlass festgelegt.</flux:subheading>
        </div>

        <flux:callout icon="information-circle">
            Veröffentlichte Sponsor:innen erscheinen auf der Startseite. Beschreibung, Logo und Website werden in der Sponsor:innen-Verwaltung gepflegt; Anlassbeitrag, Grösse und Reihenfolge hier.
        </flux:callout>

        @forelse ($sponsorRows as $index => $sponsorRow)
            <div wire:key="event-sponsor-{{ $sponsorRow['id'] }}" class="grid gap-4 border-t border-zinc-200 pt-5 dark:border-zinc-700 lg:grid-cols-[minmax(12rem,1fr)_2fr]">
                <flux:heading>{{ $sponsorRow['name'] }}</flux:heading>

                <div class="grid items-start gap-4 sm:grid-cols-2">
                    <flux:switch
                        wire:model.live="sponsorRows.{{ $index }}.attached"
                        label="Zugeordnet"
                        description="Verknüpft diese Sponsor:in mit dem Anlass."
                    />

                    <flux:switch
                        wire:model="sponsorRows.{{ $index }}.is_published"
                        label="Veröffentlicht"
                        description="Zeigt die Sponsor:in auf der Startseite."
                        :disabled="! $sponsorRow['attached']"
                    />

                    <flux:field>
                        <div class="flex items-center gap-1">
                            <flux:label>Grösse</flux:label>
                            <x-admin.field-info label="Grösse" text="Steuert die Breite des Sponsor:innen-Logos auf der Startseite." />
                        </div>
                        <flux:select wire:model="sponsorRows.{{ $index }}.size" :disabled="! $sponsorRow['attached']">
                            <flux:select.option value="small">Klein</flux:select.option>
                            <flux:select.option value="medium">Mittel</flux:select.option>
                            <flux:select.option value="large">Gross</flux:select.option>
                        </flux:select>
                        <flux:error name="sponsorRows.{{ $index }}.size" />
                    </flux:field>

                    <flux:field>
                        <div class="flex items-center gap-1">
                            <flux:label>Reihenfolge</flux:label>
                            <x-admin.field-info label="Reihenfolge" text="Kleinere Zahlen erscheinen zuerst. Bei gleicher Zahl wird nach Name sortiert." />
                        </div>
                        <flux:input
                            type="number"
                            min="0"
                            step="1"
                            wire:model="sponsorRows.{{ $index }}.sort_order"
                            :disabled="! $sponsorRow['attached']"
                        />
                        <flux:error name="sponsorRows.{{ $index }}.sort_order" />
                    </flux:field>

                    <flux:field class="sm:col-span-2">
                        <div class="flex items-center gap-1">
                            <flux:label>Beitrag an diesem Anlass</flux:label>
                            <x-admin.field-info label="Beitrag an diesem Anlass" text="Erscheint im Detailfenster der Sponsor:innen-Karte auf der Startseite und beschreibt die konkrete Unterstützung dieses Anlasses." />
                        </div>
                        <flux:textarea
                            rows="3"
                            wire:model="sponsorRows.{{ $index }}.contribution_text"
                            :disabled="! $sponsorRow['attached']"
                        />
                        <flux:error name="sponsorRows.{{ $index }}.contribution_text" />
                    </flux:field>
                </div>

                <flux:error name="sponsorRows.{{ $index }}.id" />
                <flux:error name="sponsorRows.{{ $index }}.attached" />
                <flux:error name="sponsorRows.{{ $index }}.is_published" />
            </div>
        @empty
            <flux:callout icon="user-group" heading="Keine Sponsor:innen vorhanden">
                <flux:callout.text>
                    Erfasse zuerst Sponsor:innen in der Sponsor:innen-Verwaltung.
                    <flux:callout.link href="{{ route('admin.sponsors.index') }}" wire:navigate.hover>Sponsor:innen verwalten</flux:callout.link>
                </flux:callout.text>
            </flux:callout>
        @endforelse
    </flux:card>

    <div class="flex items-center gap-3">
        <flux:text wire:dirty class="text-sm text-accent">Ungespeicherte Änderungen</flux:text>
        <flux:spacer />
        <flux:button type="submit" variant="primary" wire:target="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Sponsor:innen speichern</span>
            <span wire:loading wire:target="save">Speichert...</span>
        </flux:button>
    </div>
</form>
