<flux:dropdown>
    <flux:button
        variant="ghost"
        size="sm"
        icon="document-text"
        wire:loading.attr="disabled"
        wire:target="confirmBulkCreateInvoices,confirmBulkSendInvoices,confirmBulkSendInvoiceReminders,downloadSelectedInvoiceArchive,refreshInvoiceStatuses,paymentStatusSummary"
        :disabled="! $this->invoiceActionsEnabled()"
    >
        Rechnungen
    </flux:button>
    <flux:menu>
        <flux:menu.item
            wire:click="confirmBulkCreateInvoices"
            icon="document-plus"
            :disabled="! $this->invoiceActionsEnabled() || $this->selectedCount() === 0"
        >
            Für Auswahl erstellen
        </flux:menu.item>
        <flux:menu.item
            wire:click="confirmBulkSendInvoices"
            icon="paper-airplane"
            :disabled="! $this->invoiceActionsEnabled() || $this->selectedCount() === 0"
        >
            Für Auswahl senden
        </flux:menu.item>
        <flux:menu.item
            wire:click="confirmBulkSendInvoiceReminders"
            icon="bell-alert"
            :disabled="! $this->invoiceActionsEnabled() || $this->selectedCount() === 0"
        >
            Zahlungserinnerung für Auswahl
        </flux:menu.item>
        <flux:menu.item
            wire:click="downloadSelectedInvoiceArchive"
            icon="document-arrow-down"
            :disabled="! $this->invoiceActionsEnabled() || $this->selectedCount() === 0"
        >
            PDFs herunterladen (ZIP)
        </flux:menu.item>
    </flux:menu>
</flux:dropdown>
<flux:button
    variant="ghost"
    size="sm"
    icon="arrow-path"
    wire:click="refreshInvoiceStatuses"
    wire:loading.attr="disabled"
    wire:target="refreshInvoiceStatuses"
    :disabled="! $this->invoiceActionsEnabled()"
>
    Status aus Webling laden
</flux:button>
<flux:button
    variant="ghost"
    size="sm"
    icon="chart-bar"
    wire:click="paymentStatusSummary"
    wire:loading.attr="disabled"
    wire:target="paymentStatusSummary"
    :disabled="! $this->invoiceActionsEnabled()"
>
    Zahlungsstatus
</flux:button>
@if (! $this->invoiceActionsEnabled())
    <flux:callout icon="information-circle" variant="secondary" class="py-1.5">
        <flux:callout.text>Für Rechnungen bitte genau einen Anlass auswählen.</flux:callout.text>
    </flux:callout>
@endif
