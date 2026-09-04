@props(['row', 'columnKey'])

@if ($columnKey === 'events')
    <div class="flex flex-wrap gap-1">
        @foreach ($this->linkedEvents($row) as $event)
            <flux:badge size="sm" color="zinc">{{ $event->slug }}</flux:badge>
        @endforeach
    </div>
@elseif ($columnKey === 'invoice_status')
    @php($invoiceStatus = $this->invoiceStatus($row))
    @if ($invoiceStatus !== null)
        <flux:badge size="sm" :color="$this->invoiceStatusColor($invoiceStatus)">
            {{ $invoiceStatus->label() }}
        </flux:badge>
    @else
        -
    @endif
@elseif ($columnKey === 'invoice_number')
    {{ $this->invoiceNumber($row) }}
@elseif ($columnKey === 'invoice_total')
    {{ $this->invoiceTotal($row) }}
@elseif ($columnKey === 'invoice_remaining')
    {{ $this->invoiceRemaining($row) }}
@elseif ($columnKey === 'invoice_sent_at')
    {{ $this->invoiceSentAt($row) }}
@elseif ($columnKey === 'invoice_reminder_sent_at')
    {{ $this->invoiceReminderSentAt($row) }}
@elseif ($columnKey === 'invoice_synced_at')
    {{ $this->invoiceSyncedAt($row) }}
@elseif ($columnKey === 'partner')
    {{ $this->selectedAthletePartner($row) }}
@elseif ($columnKey === 'group')
    {{ $this->selectedAthleteGroup($row) }}
@elseif ($columnKey === 'confirmed')
    @php($confirmed = $this->selectedAthleteConfirmed($row))
    @if ($confirmed === null)
        -
    @else
        <flux:badge size="sm" :color="$confirmed ? 'zinc' : 'red'">{{ $confirmed ? 'OK' : 'NOK' }}</flux:badge>
    @endif
@elseif ($columnKey === 'registration_time')
    {{ $this->formatDateTime($this->selectedRegistrationCreatedAt($row)) }}
@elseif ($columnKey === 'donation_count')
    {{ data_get($row, 'selected_donation_count', 0) }}
@else
    {{ $this->displayValue($row, $columnKey) }}
@endif
