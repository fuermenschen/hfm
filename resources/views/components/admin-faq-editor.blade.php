<div>
    <flux:modal name="{{ $this->modalName() }}" wire:model.self="modalOpen" class="md:w-xl" wire:close="close">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $faqId === null ? 'FAQ erstellen' : 'FAQ bearbeiten' }}</flux:heading>
                <flux:text class="mt-2">Die Antwort wird als Markdown dargestellt.</flux:text>
            </div>

            <flux:callout icon="exclamation-triangle" variant="warning">
                <flux:callout.text>
                    FAQs ohne Anlasszuordnung erscheinen als allgemeine FAQs auf allen Anlassseiten. Prüfe jede
                    Anpassung sorgfältig.
                </flux:callout.text>
            </flux:callout>

            <flux:field>
                <flux:input
                    label="Frage"
                    wire:model.live.blur="title"
                    placeholder="Wann und wo findet der Anlass statt?"
                    required
                />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label badge="Pflichtfeld">Antwort</flux:label>
                    <x-admin.field-info
                        label="Antwort"
                        text="Die Antwort wird als Markdown formatiert. Nutze die Vorschau, um die Darstellung zu prüfen. HTML wird entfernt."
                    />
                    <flux:spacer />
                    <flux:dropdown>
                        <flux:button type="button" size="xs" variant="ghost" icon="eye" icon:variant="outline"
                            >Vorschau</flux:button>
                        <flux:popover class="w-80 space-y-2">
                            <flux:heading>{{ $title === '' ? 'Antwort-Vorschau' : $title }}</flux:heading>
                            @if (trim($contentMd) === '')
                                <flux:text>Noch keine Antwort erfasst.</flux:text>
                            @else
                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                    {!! $this->contentHtml !!}
                                </div>
                            @endif
                        </flux:popover>
                    </flux:dropdown>
                </div>
                <flux:textarea
                    wire:model.live.blur="contentMd"
                    rows="8"
                    placeholder="Allgemeine Beschreibung der Antwort.&#10;&#10;- **fett**, *kursiv* und [Linktext](https://example.org) sind möglich&#10;- Listeneinträge mit -"
                    required
                />
                <flux:error name="contentMd" />
            </flux:field>

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
                <flux:text class="mt-2">Folgende Angaben dieser FAQ werden geändert:</flux:text>
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
