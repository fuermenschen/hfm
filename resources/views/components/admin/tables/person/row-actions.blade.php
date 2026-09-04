@props(['row', 'role'])

<div class="flex justify-end gap-1">
    @if ($role === 'donor' && $this->invoiceActionsEnabled())
        <flux:dropdown align="end">
            <flux:button variant="subtle" size="xs" icon="ellipsis-horizontal" square aria-label="Rechnungsaktionen" />
            <flux:menu>
                @if ($this->canCreateInvoiceForRow($row))
                    <flux:menu.item wire:click="confirmCreateInvoice({{ $row->id }})" icon="document-plus">
                        Rechnung erstellen
                    </flux:menu.item>
                @endif
                @if ($this->canDownloadInvoiceForRow($row))
                    <flux:menu.item
                        wire:click="downloadInvoicePdf({{ $row->id }})"
                        wire:loading.attr="disabled"
                        wire:target="downloadInvoicePdf"
                        icon="document-arrow-down"
                    >
                        Rechnung herunterladen
                    </flux:menu.item>
                @endif
                @if ($this->canSendInvoiceForRow($row))
                    <flux:menu.item wire:click="sendInvoice({{ $row->id }})" icon="paper-airplane">
                        Rechnung senden
                    </flux:menu.item>
                @endif
                @if ($this->canRemindInvoiceForRow($row))
                    <flux:menu.item wire:click="sendInvoiceReminder({{ $row->id }})" icon="bell-alert">
                        Zahlungserinnerung senden
                    </flux:menu.item>
                @endif
                @if (($weblingUrl = $this->invoiceWeblingUrl($row)) !== null)
                    <flux:menu.item href="{{ $weblingUrl }}" target="_blank" icon="arrow-top-right-on-square">
                        Rechnung in Webling öffnen
                    </flux:menu.item>
                @endif
                @if ($this->canDeleteInvoiceForRow($row))
                    <flux:menu.item wire:click="confirmDeleteInvoice({{ $row->id }})" icon="trash" variant="danger">
                        Rechnung löschen
                    </flux:menu.item>
                @endif
            </flux:menu>
        </flux:dropdown>
    @endif
    <flux:button
        size="xs"
        variant="ghost"
        icon="pencil-square"
        square
        tooltip="Person bearbeiten"
        wire:click="$dispatchTo('admin-external-user-editor', 'open-external-user-editor', { externalUserId: {{ $row->id }} })"
    />
    @if ($role === 'athlete' && ($registration = $this->selectedAthleteRegistration($row)))
        <flux:button
            size="xs"
            variant="ghost"
            icon="clipboard-document"
            square
            tooltip="Anmeldung bearbeiten"
            wire:click="$dispatchTo('admin-athlete-registration-editor', 'open-athlete-registration-editor', { athleteRegistrationId: {{ $registration->id }} })"
        />
    @endif
</div>
