<div class="flex items-center justify-center">
    <flux:dropdown>
        <flux:button variant="subtle" size="xs" icon="ellipsis-horizontal" />
        <flux:menu>
            <flux:menu.group heading="Spender:in">
                <flux:menu.item icon="user" :href="route('show-donator', ['login_token' => $row->login_token])" target="_blank">
                    Als Spender einloggen
                </flux:menu.item>
            </flux:menu.group>

            @php
                $letterPath = data_get($row->webling_data, 'letter_pdf.path');
                $hasPdf = ! empty($letterPath);
                $hasDebitor = ! empty(data_get($row->webling_data, 'debitor_id'));
                $debitorUrl = data_get($row->webling_data, 'debitor_url');
                $canDownload = $hasPdf;
                $canSend = $hasPdf && ! empty($row->email);
                $canCreate = (! $hasDebitor) || (! $hasPdf);
                $canDelete = $hasDebitor || $hasPdf;
                $paymentStatus = data_get($row->webling_data, 'payment_status');
                $canSendReminder = $hasPdf && ! empty($row->email) && ! empty($row->invoice_sent_at) && $paymentStatus === 'overdue';
            @endphp

            <flux:menu.group heading="Rechnung">
                @if ($canCreate)
                    <flux:menu.item icon="document-plus" wire:click="createDonorInvoice({{ $row->id }})">
                        Rechnung erstellen
                    </flux:menu.item>
                @endif

                @if ($canDownload)
                    <flux:menu.item icon="document-arrow-down" wire:click="downloadDonorInvoice({{ $row->id }})">
                        Rechnung herunterladen
                    </flux:menu.item>
                @endif

                @if ($canSend)
                    <flux:menu.item icon="paper-airplane" wire:click="sendDonorInvoice({{ $row->id }})">
                        Rechnung senden
                    </flux:menu.item>
                @endif

                @if ($canSendReminder)
                    <flux:menu.item icon="bell-alert" wire:click="sendDonorInvoiceReminder({{ $row->id }})">
                        Zahlungserinnerung senden
                    </flux:menu.item>
                @endif

                @if (! empty($debitorUrl))
                    <flux:menu.item icon="arrow-top-right-on-square" :href="$debitorUrl" target="_blank">
                        Rechnung in Webling anzeigen
                    </flux:menu.item>
                @endif

                @if ($canDelete)
                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDeleteDonorInvoice({{ $row->id }})">
                        Rechnung löschen
                    </flux:menu.item>
                @endif

                @if (! $canCreate && ! $canDownload && ! $canSend && ! $canSendReminder && ! $canDelete)
                    <flux:menu.item disabled="true">Keine Aktionen verfügbar</flux:menu.item>
                @endif
            </flux:menu.group>
        </flux:menu>
    </flux:dropdown>
</div>
