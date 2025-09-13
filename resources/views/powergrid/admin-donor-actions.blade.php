<div class="flex justify-end">
    <flux:dropdown>
        <flux:button variant="subtle" size="xs" icon="ellipsis-vertical"/>
        <flux:menu>
            <flux:menu.group heading="Spender:in">
                <flux:menu.item icon="user" :href="route('show-donator', ['login_token' => $row->login_token])" target="_blank">
                    Als Spender einloggen
                </flux:menu.item>
            </flux:menu.group>

            <flux:menu.group heading="Rechnung">
                <flux:menu.item icon="document-plus" wire:click="createDonorInvoice({{ $row->id }})">
                    Rechnung erstellen
                </flux:menu.item>
                <flux:menu.item icon="document-arrow-down" wire:click="downloadDonorInvoice({{ $row->id }})">
                    Rechnung herunterladen
                </flux:menu.item>
                <flux:menu.item icon="paper-airplane" wire:click="sendDonorInvoice({{ $row->id }})">
                    Rechnung senden
                </flux:menu.item>
                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDeleteDonorInvoice({{ $row->id }})">
                    Rechnung löschen
                </flux:menu.item>
            </flux:menu.group>
        </flux:menu>
    </flux:dropdown>
</div>
