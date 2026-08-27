<form wire:submit="save" class="space-y-6">
    @if ($errors->any())
        <flux:callout
            variant="danger"
            icon="exclamation-triangle"
            heading="Bitte überprüfe die FAQs"
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
        $assignedFaqRows = collect($faqRows)->filter(fn (array $row): bool => $row['attached'] || $row['was_attached']);
        $availableFaqRows = collect($faqRows)->filter(fn (array $row): bool => ! $row['attached'] && ! $row['was_attached']);
    @endphp

    <flux:card class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">Zugeordnete FAQs</flux:heading>
            <flux:subheading>Gruppe, Reihenfolge und Veröffentlichung gelten pro Anlass.</flux:subheading>
        </div>

        <flux:callout icon="information-circle">
            FAQs werden innerhalb ihrer Gruppe nach Reihenfolge dargestellt. FAQs ohne Anlasszuordnung erscheinen als
            allgemeine FAQs auf allen Anlassseiten.
        </flux:callout>

        @forelse ($assignedFaqRows as $index => $faqRow)
            <div
                wire:key="event-faq-assigned-{{ $faqRow['id'] }}"
                class="grid gap-4 border-t border-zinc-200 pt-5 lg:grid-cols-[minmax(12rem,1fr)_2fr] dark:border-zinc-700"
            >
                <div class="space-y-3">
                    <div>
                        <flux:heading>{{ $faqRow['title'] }}</flux:heading>
                        @if ($faqRow['excerpt'] !== '')
                            <flux:text class="line-clamp-1 text-zinc-500">{{ $faqRow['excerpt'] }}</flux:text>
                        @endif
                    </div>

                    @if ($faqRow['was_attached'])
                        <flux:switch
                            wire:model="faqRows.{{ $index }}.attached"
                            label="Zugeordnet"
                            description="Ausschalten, um die FAQ beim Speichern zu entfernen."
                        />
                        <flux:callout
                            x-cloak
                            x-show="! ($wire.faqRows.find(row => row.id == {{ $faqRow['id'] }})?.attached ?? true)"
                            variant="warning"
                            icon="exclamation-triangle"
                            heading="Vom Anlass entfernen"
                        >
                            Beim Speichern gehen Gruppe, Reihenfolge und Veröffentlichungsstatus verloren.
                        </flux:callout>
                    @endif

                    @if (! $faqRow['was_attached'])
                        <flux:button type="button" size="sm" variant="ghost" wire:click="detachFaq({{ $index }})">
                            Zuordnung rückgängig
                        </flux:button>
                    @endif
                </div>

                <div class="grid items-start gap-4 sm:grid-cols-2">
                    <flux:switch
                        wire:model="faqRows.{{ $index }}.is_published"
                        x-bind:disabled="! ($wire.faqRows.find(row => row.id == {{ $faqRow['id'] }})?.attached ?? true)"
                        label="Veröffentlicht"
                        description="Zeigt die FAQ auf der Fragen-und-Antworten-Seite."
                    />

                    <flux:field>
                        <flux:label badge="Pflichtfeld">Gruppe</flux:label>
                        <flux:select
                            wire:model="faqRows.{{ $index }}.group"
                            x-bind:disabled="! ($wire.faqRows.find(row => row.id == {{ $faqRow['id'] }})?.attached ?? true)"
                            required
                        >
                            @foreach ($this->groupOptions() as $groupValue => $groupLabel)
                                <flux:select.option value="{{ $groupValue }}">{{ $groupLabel }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="faqRows.{{ $index }}.group" />
                    </flux:field>

                    <flux:field class="sm:col-span-2">
                        <div class="flex items-center gap-1">
                            <flux:label badge="Pflichtfeld">Reihenfolge</flux:label>
                            <x-admin.field-info
                                label="Reihenfolge"
                                text="Kleinere Zahlen erscheinen zuerst. Bei gleicher Zahl wird nach Frage sortiert."
                            />
                        </div>
                        <flux:input
                            type="number"
                            min="0"
                            step="1"
                            wire:model="faqRows.{{ $index }}.sort_order"
                            x-bind:disabled="! ($wire.faqRows.find(row => row.id == {{ $faqRow['id'] }})?.attached ?? true)"
                            required
                        />
                        <flux:error name="faqRows.{{ $index }}.sort_order" />
                    </flux:field>
                </div>

                <flux:error name="faqRows.{{ $index }}.id" />
                <flux:error name="faqRows.{{ $index }}.attached" />
                <flux:error name="faqRows.{{ $index }}.is_published" />
            </div>
        @empty
            <flux:text>Noch keine FAQs zugeordnet.</flux:text>
        @endforelse
    </flux:card>

    <flux:card class="space-y-4">
        <div>
            <flux:heading size="lg">Verfügbare FAQs</flux:heading>
            <flux:subheading>Neue Zuordnungen sind zunächst nicht veröffentlicht.</flux:subheading>
        </div>

        @forelse ($availableFaqRows as $index => $faqRow)
            <div
                wire:key="event-faq-available-{{ $faqRow['id'] }}"
                class="flex flex-wrap items-center gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700"
            >
                <div class="min-w-0 flex-1 space-y-0.5">
                    <flux:text class="font-medium">{{ $faqRow['title'] }}</flux:text>
                    @if ($faqRow['excerpt'] !== '')
                        <flux:text class="line-clamp-1 text-zinc-500">{{ $faqRow['excerpt'] }}</flux:text>
                    @endif
                </div>
                <flux:spacer />
                <flux:button type="button" size="sm" variant="primary" wire:click="attachFaq({{ $index }})"
                    >Zuordnen</flux:button>
            </div>
        @empty
            @if ($faqRows === [])
                <flux:callout icon="question-mark-circle" heading="Keine FAQs vorhanden">
                    <flux:callout.text>
                        Erfasse zuerst FAQs in der FAQ-Verwaltung.
                        <flux:callout.link href="{{ route('admin.faqs.index') }}" wire:navigate.hover>
                            FAQs verwalten</flux:callout.link>
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:text>Alle FAQs sind zugeordnet.</flux:text>
            @endif
        @endforelse
    </flux:card>

    <div class="sticky bottom-0 z-20 flex items-center gap-3 border-t border-zinc-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
        <div x-cloak x-show="$wire.$dirty() || $wire.hasUnsavedChanges" class="space-y-1.5">
            <flux:text class="text-accent text-sm">Ungespeicherte Änderungen</flux:text>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($faqRows as $index => $faqRow)
                    <flux:badge
                        size="sm"
                        x-cloak
                        x-show="$wire.$dirty('faqRows.{{ $index }}')"
                    >{{ $faqRow['title'] }}</flux:badge>
                @endforeach
                <flux:badge size="sm" x-cloak x-show="$wire.hasUnsavedChanges && ! $wire.$dirty()"
                    >Zuordnungen</flux:badge>
            </div>
        </div>
        <flux:spacer />
        <flux:button type="submit" variant="primary" wire:target="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">FAQs speichern</span>
            <span wire:loading wire:target="save">Speichert...</span>
        </flux:button>
    </div>
</form>
