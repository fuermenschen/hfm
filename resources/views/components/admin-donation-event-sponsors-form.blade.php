<form wire:submit="save" class="space-y-6">
    @if ($errors->any())
        <flux:callout
            variant="danger"
            icon="exclamation-triangle"
            heading="Bitte überprüfe die Sponsor:innen"
            tabindex="-1"
            x-init="
                $nextTick(() => {
                    const field = $el.parentElement.querySelector('[aria-invalid=true]');
                    (field ?? $el).scrollIntoView({ behavior: 'smooth', block: 'center' });
                    (field ?? $el).focus();
                })
            "
        >
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </flux:callout>
    @endif

    @php
        $assignedSponsorRows = collect($sponsorRows)->filter(fn (array $row): bool => $row['attached'] || $row['was_attached']);
        $availableSponsorRows = collect($sponsorRows)->filter(fn (array $row): bool => ! $row['attached'] && ! $row['was_attached']);
    @endphp

    <flux:card class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">Zugeordnete Sponsor:innen</flux:heading>
            <flux:subheading>Darstellung und Anlassbeitrag werden pro Anlass festgelegt.</flux:subheading>
        </div>

        <flux:callout icon="information-circle">
            Veröffentlichte Sponsor:innen erscheinen auf der Startseite. Anlassbeitrag, Grösse und Reihenfolge gelten
            nur für diesen Anlass.
        </flux:callout>

        @forelse ($assignedSponsorRows as $index => $sponsorRow)
            <div
                wire:key="event-sponsor-assigned-{{ $sponsorRow['id'] }}"
                class="grid gap-4 border-t border-zinc-200 pt-5 lg:grid-cols-[minmax(12rem,1fr)_2fr] dark:border-zinc-700"
            >
                <div class="space-y-3">
                    <flux:heading>{{ $sponsorRow['name'] }}</flux:heading>

                    @if ($sponsorRow['was_attached'])
                        <flux:switch
                            wire:model="sponsorRows.{{ $index }}.attached"
                            label="Zugeordnet"
                            description="Ausschalten, um die Sponsor:in beim Speichern zu entfernen."
                        />
                        <flux:callout
                            x-cloak
                            x-show="! $wire.sponsorRows[{{ $index }}].attached"
                            variant="warning"
                            icon="exclamation-triangle"
                            heading="Vom Anlass entfernen"
                        >
                            Beim Speichern gehen Grösse, Anlassbeitrag, Reihenfolge und Veröffentlichungsstatus
                            verloren.
                        </flux:callout>
                    @endif

                    @if (! $sponsorRow['was_attached'])
                        <flux:button type="button" size="sm" variant="ghost" wire:click="detachSponsor({{ $index }})">
                            Zuordnung rückgängig
                        </flux:button>
                    @endif
                </div>

                <div class="grid items-start gap-4 sm:grid-cols-2">
                    <flux:switch
                        wire:model="sponsorRows.{{ $index }}.is_published"
                        x-bind:disabled="! $wire.sponsorRows[{{ $index }}].attached"
                        label="Veröffentlicht"
                        description="Zeigt die Sponsor:in auf der Startseite."
                    />

                    <flux:field>
                        <div class="flex items-center gap-1">
                            <flux:label badge="Pflichtfeld">Grösse</flux:label>
                            <x-admin.field-info
                                label="Grösse"
                                text="Steuert die Breite des Sponsor:innen-Logos auf der Startseite."
                            />
                        </div>
                        <flux:select
                            wire:model="sponsorRows.{{ $index }}.size"
                            x-bind:disabled="! $wire.sponsorRows[{{ $index }}].attached"
                            required
                        >
                            <flux:select.option value="small">Klein</flux:select.option>
                            <flux:select.option value="medium">Mittel</flux:select.option>
                            <flux:select.option value="large">Gross</flux:select.option>
                        </flux:select>
                        <flux:error name="sponsorRows.{{ $index }}.size" />
                    </flux:field>

                    <flux:field>
                        <div class="flex items-center gap-1">
                            <flux:label badge="Pflichtfeld">Reihenfolge</flux:label>
                            <x-admin.field-info
                                label="Reihenfolge"
                                text="Kleinere Zahlen erscheinen zuerst. Bei gleicher Zahl wird nach Name sortiert."
                            />
                        </div>
                        <flux:input
                            type="number"
                            min="0"
                            step="1"
                            wire:model="sponsorRows.{{ $index }}.sort_order"
                            x-bind:disabled="! $wire.sponsorRows[{{ $index }}].attached"
                            required
                        />
                        <flux:error name="sponsorRows.{{ $index }}.sort_order" />
                    </flux:field>

                    <flux:field class="sm:col-span-2">
                        <div class="flex items-center gap-1">
                            <flux:label badge="Pflichtfeld bei Zuordnung">Beitrag an diesem Anlass</flux:label>
                            <x-admin.field-info
                                label="Beitrag an diesem Anlass"
                                text="Erscheint im Detailfenster der Sponsor:innen-Karte auf der Startseite."
                            />
                        </div>
                        <flux:textarea
                            rows="3"
                            wire:model="sponsorRows.{{ $index }}.contribution_text"
                            x-bind:disabled="! $wire.sponsorRows[{{ $index }}].attached"
                            placeholder="Unterstützt die Verpflegung der Teilnehmenden."
                            required
                        />
                        <flux:error name="sponsorRows.{{ $index }}.contribution_text" />
                    </flux:field>
                </div>

                <flux:error name="sponsorRows.{{ $index }}.id" />
                <flux:error name="sponsorRows.{{ $index }}.attached" />
                <flux:error name="sponsorRows.{{ $index }}.is_published" />
            </div>
        @empty
            <flux:text>Noch keine Sponsor:innen zugeordnet.</flux:text>
        @endforelse
    </flux:card>

    <flux:card class="space-y-4">
        <div>
            <flux:heading size="lg">Verfügbare Sponsor:innen</flux:heading>
            <flux:subheading>Neue Zuordnungen sind zunächst nicht veröffentlicht.</flux:subheading>
        </div>

        @forelse ($availableSponsorRows as $index => $sponsorRow)
            <div
                wire:key="event-sponsor-available-{{ $sponsorRow['id'] }}"
                class="flex flex-wrap items-center gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700"
            >
                <flux:text class="font-medium">{{ $sponsorRow['name'] }}</flux:text>
                <flux:spacer />
                <flux:button type="button" size="sm" variant="primary" wire:click="attachSponsor({{ $index }})"
                    >Zuordnen</flux:button>
            </div>
        @empty
            @if ($sponsorRows === [])
                <flux:callout icon="user-group" heading="Keine Sponsor:innen vorhanden">
                    <flux:callout.text>
                        Erfasse zuerst Sponsor:innen in der Sponsor:innen-Verwaltung.
                        <flux:callout.link href="{{ route('admin.sponsors.index') }}" wire:navigate.hover>
                            Sponsor:innen verwalten</flux:callout.link>
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:text>Alle Sponsor:innen sind zugeordnet.</flux:text>
            @endif
        @endforelse
    </flux:card>

    <div class="sticky bottom-0 z-20 flex items-center gap-3 border-t border-zinc-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
        <div x-cloak x-show="$wire.$dirty() || $wire.hasUnsavedChanges" class="space-y-1.5">
            <flux:text class="text-accent text-sm">Ungespeicherte Änderungen</flux:text>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($sponsorRows as $index => $sponsorRow)
                    <flux:badge
                        size="sm"
                        x-cloak
                        x-show="$wire.$dirty('sponsorRows.{{ $index }}')"
                    >{{ $sponsorRow['name'] }}</flux:badge>
                @endforeach
                <flux:badge size="sm" x-cloak x-show="$wire.hasUnsavedChanges && ! $wire.$dirty()"
                    >Zuordnungen</flux:badge>
            </div>
        </div>
        <flux:spacer />
        <flux:button type="submit" variant="primary" wire:target="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Sponsor:innen speichern</span>
            <span wire:loading wire:target="save">Speichert...</span>
        </flux:button>
    </div>
</form>
