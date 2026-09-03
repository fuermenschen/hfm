<flux:dropdown>
    <flux:button
        variant="ghost"
        size="sm"
        icon="document-text"
        wire:loading.attr="disabled"
        wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments,downloadAllAthleteStoryImages,downloadSelectedAthleteStoryImages"
        :disabled="! $this->documentDownloadsEnabled()"
    >
        <span
            wire:loading.remove
            wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments,downloadAllAthleteStoryImages,downloadSelectedAthleteStoryImages"
        >Dokumente</span>
        <span
            wire:loading
            wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments,downloadAllAthleteStoryImages,downloadSelectedAthleteStoryImages"
        >Wird erstellt...</span>
    </flux:button>
    <flux:menu>
        <flux:menu.group heading="Willkommensbrief">
            <flux:menu.item
                wire:click="downloadAllAthleteDocuments('welcome-letter')"
                wire:loading.attr="disabled"
                wire:target="downloadAllAthleteDocuments"
                icon="document-text"
                :disabled="! $this->documentDownloadsEnabled()"
            >
                Alle Sportler:innen
            </flux:menu.item>
            <flux:menu.item
                wire:click="downloadSelectedAthleteDocuments('welcome-letter')"
                wire:loading.attr="disabled"
                wire:target="downloadSelectedAthleteDocuments"
                icon="check-circle"
                :disabled="! $this->documentDownloadsEnabled() || $this->selectedCount() === 0"
            >
                Ausgewählte Sportler:innen
            </flux:menu.item>
        </flux:menu.group>
        <flux:menu.group heading="Personalisierter Flyer">
            <flux:menu.item
                wire:click="downloadAllAthleteDocuments('personalized-flyer')"
                wire:loading.attr="disabled"
                wire:target="downloadAllAthleteDocuments"
                icon="document-text"
                :disabled="! $this->documentDownloadsEnabled()"
            >
                Alle Sportler:innen
            </flux:menu.item>
            <flux:menu.item
                wire:click="downloadSelectedAthleteDocuments('personalized-flyer')"
                wire:loading.attr="disabled"
                wire:target="downloadSelectedAthleteDocuments"
                icon="check-circle"
                :disabled="! $this->documentDownloadsEnabled() || $this->selectedCount() === 0"
            >
                Ausgewählte Sportler:innen
            </flux:menu.item>
        </flux:menu.group>
        <flux:menu.group heading="Story-Bilder">
            <flux:menu.item
                wire:click="downloadAllAthleteStoryImages"
                wire:loading.attr="disabled"
                wire:target="downloadAllAthleteStoryImages"
                icon="photo"
                :disabled="! $this->documentDownloadsEnabled()"
            >
                Alle Sportler:innen
            </flux:menu.item>
            <flux:menu.item
                wire:click="downloadSelectedAthleteStoryImages"
                wire:loading.attr="disabled"
                wire:target="downloadSelectedAthleteStoryImages"
                icon="check-circle"
                :disabled="! $this->documentDownloadsEnabled() || $this->selectedCount() === 0"
            >
                Ausgewählte Sportler:innen
            </flux:menu.item>
        </flux:menu.group>
    </flux:menu>
</flux:dropdown>
@if (! $this->documentDownloadsEnabled())
    <flux:callout icon="information-circle" variant="secondary" class="py-1.5">
        <flux:callout.text>Für Dokumente bitte genau einen Anlass auswählen.</flux:callout.text>
    </flux:callout>
@endif
<flux:text
    wire:loading.flex
    wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments,downloadAllAthleteStoryImages,downloadSelectedAthleteStoryImages"
    class="items-center gap-1 text-sm text-zinc-500"
>
    <flux:icon.arrow-path class="size-4 animate-spin" />
    Dokumente werden erstellt...
</flux:text>
