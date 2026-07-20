<form
    wire:submit="save"
    class="space-y-6"
    data-admin-unsaved-form
    x-bind:data-unsaved="($wire.$dirty() || $wire.hasUnsavedChanges) ? 'true' : 'false'"
>
    @if ($errors->any())
        <flux:callout
            variant="danger"
            icon="exclamation-triangle"
            heading="Bitte überprüfe die Partner:innen"
            tabindex="-1"
            x-init="$nextTick(() => { const field = $el.parentElement.querySelector('[aria-invalid=true]'); (field ?? $el).scrollIntoView({ behavior: 'smooth', block: 'center' }); (field ?? $el).focus(); })"
        >
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </flux:callout>
    @endif

    @php
        $assignedPartnerRows = collect($partnerRows)->filter(fn (array $row): bool => $row['attached'] || $row['was_attached']);
        $availablePartnerRows = collect($partnerRows)->filter(fn (array $row): bool => ! $row['attached'] && ! $row['was_attached']);
    @endphp

    <flux:card class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">Zugeordnete Partner:innen</flux:heading>
            <flux:subheading>Sichtbarkeit und Reihenfolge gelten nur für diesen Anlass.</flux:subheading>
        </div>

        <flux:callout icon="information-circle">
            Veröffentlichte Partner:innen erscheinen auf der Startseite und in der Anmeldung. Partner:innen mit bestehenden Anmeldungen können nicht entfernt werden.
        </flux:callout>

        @forelse ($assignedPartnerRows as $index => $partnerRow)
            <div wire:key="event-partner-assigned-{{ $partnerRow['id'] }}" class="grid gap-4 border-t border-zinc-200 pt-5 dark:border-zinc-700 lg:grid-cols-[minmax(12rem,1fr)_2fr]">
                <div class="space-y-2">
                    <flux:heading>{{ $partnerRow['name'] }}</flux:heading>

                    @if ($partnerRow['is_locked'])
                        <flux:badge size="sm" icon="lock-closed" color="amber">
                            {{ $partnerRow['registration_count'] }} {{ $partnerRow['registration_count'] === 1 ? 'Anmeldung' : 'Anmeldungen' }}
                        </flux:badge>
                        <flux:text class="text-xs">Bleibt zugeordnet, solange Anmeldungen diese Partner:in verwenden.</flux:text>
                    @endif

                    @if ($partnerRow['was_attached'] && ! $partnerRow['is_locked'])
                        <flux:switch
                            wire:model="partnerRows.{{ $index }}.attached"
                            label="Zugeordnet"
                            description="Ausschalten, um die Partner:in beim Speichern zu entfernen."
                        />
                    @endif

                    @if ($partnerRow['was_attached'] && ! $partnerRow['is_locked'])
                        <flux:callout
                            x-cloak
                            x-show="!$wire.partnerRows[{{ $index }}].attached"
                            variant="warning"
                            icon="exclamation-triangle"
                            heading="Vom Anlass entfernen"
                        >
                            Beim Speichern gehen Reihenfolge und Veröffentlichungsstatus dieser Zuordnung verloren.
                        </flux:callout>
                    @endif
                </div>

                <div class="grid items-start gap-4 sm:grid-cols-2">
                    <flux:switch
                        wire:model="partnerRows.{{ $index }}.is_published"
                        label="Veröffentlicht"
                        description="Sichtbar auf Startseite und in Anmeldung."
                    />

                    <flux:field>
                        <div class="flex items-center gap-1">
                            <flux:label badge="Pflichtfeld">Reihenfolge</flux:label>
                            <x-admin.field-info label="Reihenfolge" text="Kleinere Zahlen erscheinen zuerst. Bei gleicher Zahl wird nach Name sortiert." />
                        </div>
                        <flux:input type="number" min="0" step="1" wire:model="partnerRows.{{ $index }}.sort_order" required />
                        <flux:error name="partnerRows.{{ $index }}.sort_order" />
                    </flux:field>
                </div>

                <flux:error name="partnerRows.{{ $index }}.id" />
                <flux:error name="partnerRows.{{ $index }}.attached" />
                <flux:error name="partnerRows.{{ $index }}.is_published" />
            </div>
        @empty
            <flux:text>Noch keine Partner:innen zugeordnet.</flux:text>
        @endforelse
    </flux:card>

    <flux:card class="space-y-4">
        <div>
            <flux:heading size="lg">Verfügbare Partner:innen</flux:heading>
            <flux:subheading>Neue Zuordnungen sind zunächst nicht veröffentlicht.</flux:subheading>
        </div>

        @forelse ($availablePartnerRows as $index => $partnerRow)
            <div wire:key="event-partner-available-{{ $partnerRow['id'] }}" class="flex flex-wrap items-center gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:text class="font-medium">{{ $partnerRow['name'] }}</flux:text>
                <flux:spacer />
                <flux:button type="button" size="sm" variant="primary" wire:click="attachPartner({{ $index }})">Zuordnen</flux:button>
            </div>
        @empty
            @if ($partnerRows === [])
                <flux:callout icon="user-group" heading="Keine Partner:innen vorhanden">
                    Erfasse zuerst Partner:innen in der Partner:innen-Verwaltung.
                    <flux:callout.link href="{{ route('admin.partners.index') }}" wire:navigate.hover>Partner:innen verwalten</flux:callout.link>
                </flux:callout>
            @else
                <flux:text>Alle Partner:innen sind zugeordnet.</flux:text>
            @endif
        @endforelse
    </flux:card>

    <div class="sticky bottom-0 z-20 flex items-center gap-3 border-t border-zinc-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
        <div x-cloak x-show="$wire.$dirty() || $wire.hasUnsavedChanges" class="space-y-1.5">
            <flux:text class="text-sm text-accent">Ungespeicherte Änderungen</flux:text>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($partnerRows as $index => $partnerRow)
                    <flux:badge size="sm" x-cloak x-show="$wire.$dirty('partnerRows.{{ $index }}')">{{ $partnerRow['name'] }}</flux:badge>
                @endforeach
                <flux:badge size="sm" x-cloak x-show="$wire.hasUnsavedChanges && !$wire.$dirty()">Zuordnungen</flux:badge>
            </div>
        </div>
        <flux:spacer />
        <flux:button type="submit" variant="primary" wire:target="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Partner:innen speichern</span>
            <span wire:loading wire:target="save">Speichert...</span>
        </flux:button>
    </div>
</form>
