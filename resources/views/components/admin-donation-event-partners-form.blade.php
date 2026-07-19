<form wire:submit="save" class="space-y-6">
    <flux:card class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">Partner:innen zuordnen</flux:heading>
            <flux:subheading>Zuordnung, Sichtbarkeit und Reihenfolge gelten nur für diesen Anlass.</flux:subheading>
        </div>

        <flux:callout icon="information-circle">
            Veröffentlichte Partner:innen erscheinen auf der Startseite und stehen Sportler:innen bei der Anmeldung zur Auswahl. Partner:innen mit bestehenden Anmeldungen können nicht entfernt werden.
        </flux:callout>

        @forelse ($partnerRows as $index => $partnerRow)
            <div wire:key="event-partner-{{ $partnerRow['id'] }}" class="grid gap-4 border-t border-zinc-200 pt-5 dark:border-zinc-700 lg:grid-cols-[minmax(12rem,1fr)_2fr]">
                <div class="space-y-2">
                    <flux:heading>{{ $partnerRow['name'] }}</flux:heading>

                    @if ($partnerRow['is_locked'])
                        <flux:badge size="sm" icon="lock-closed" color="amber">
                            {{ $partnerRow['registration_count'] }} {{ $partnerRow['registration_count'] === 1 ? 'Anmeldung' : 'Anmeldungen' }}
                        </flux:badge>
                        <flux:text class="text-xs">Bleibt zugeordnet, solange Anmeldungen diese Partner:in verwenden.</flux:text>
                    @endif
                </div>

                <div class="grid items-start gap-4 sm:grid-cols-3">
                    <flux:switch
                        wire:model.live="partnerRows.{{ $index }}.attached"
                        label="Zugeordnet"
                        description="Verknüpft diese Partner:in mit dem Anlass."
                        :disabled="$partnerRow['is_locked']"
                    />

                    <flux:switch
                        wire:model="partnerRows.{{ $index }}.is_published"
                        label="Veröffentlicht"
                        description="Sichtbar auf Startseite und in Anmeldung."
                        :disabled="! $partnerRow['attached']"
                    />

                    <flux:field>
                        <div class="flex items-center gap-1">
                            <flux:label>Reihenfolge</flux:label>
                            <x-admin.field-info label="Reihenfolge" text="Kleinere Zahlen erscheinen zuerst. Bei gleicher Zahl wird nach Name sortiert." />
                        </div>
                        <flux:input
                            type="number"
                            min="0"
                            step="1"
                            wire:model="partnerRows.{{ $index }}.sort_order"
                            :disabled="! $partnerRow['attached']"
                        />
                        <flux:error name="partnerRows.{{ $index }}.sort_order" />
                    </flux:field>
                </div>

                <flux:error name="partnerRows.{{ $index }}.id" />
                <flux:error name="partnerRows.{{ $index }}.attached" />
                <flux:error name="partnerRows.{{ $index }}.is_published" />
            </div>
        @empty
            <flux:callout icon="user-group" heading="Keine Partner:innen vorhanden">
                Erfasse zuerst Partner:innen in der Partner:innen-Verwaltung.
                <flux:callout.link href="{{ route('admin.partners.index') }}" wire:navigate.hover>Partner:innen verwalten</flux:callout.link>
            </flux:callout>
        @endforelse
    </flux:card>

    <div class="flex items-center gap-3">
        <flux:text wire:dirty class="text-sm text-accent">Ungespeicherte Änderungen</flux:text>
        <flux:spacer />
        <flux:button type="submit" variant="primary" wire:target="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Partner:innen speichern</span>
            <span wire:loading wire:target="save">Speichert...</span>
        </flux:button>
    </div>
</form>
