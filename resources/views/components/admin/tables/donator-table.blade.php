<div>
    <x-admin.datatable>
        <x-slot:toolbar>
            <x-admin.tables.partials.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Spender:innen suchen..." icon="magnifying-glass" />
                </x-slot:topLeft>

                <x-slot:topRight>
                    <x-admin.tables.partials.selection-toolbar :selected-count="$this->selectedCount()" />
                </x-slot:topRight>

                <x-slot:bottomLeft>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="arrow-down-tray">Export</flux:button>
                            <flux:menu>
                                <flux:menu.group heading="Kompletter Datensatz">
                                    <flux:menu.item wire:click="exportAll('xlsx')" icon="document-text">Excel</flux:menu.item>
                                    <flux:menu.item wire:click="exportAll('csv')" icon="document-text">CSV</flux:menu.item>
                                </flux:menu.group>
                                <flux:menu.group heading="Ausgewählte Zeilen">
                                    <flux:menu.item wire:click="exportSelected('xlsx')" icon="check-circle">Excel</flux:menu.item>
                                    <flux:menu.item wire:click="exportSelected('csv')" icon="check-circle">CSV</flux:menu.item>
                                </flux:menu.group>
                            </flux:menu>
                        </flux:dropdown>

                        <flux:button variant="ghost" size="sm" icon="banknotes" wire:click="checkPaymentStatus">
                            Zahlungsstatus prüfen
                        </flux:button>

                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="adjustments-horizontal">Spalten</flux:button>
                            <flux:menu keep-open>
                                @foreach ($this->visibleColumnOptions() as $columnKey => $columnLabel)
                                    <flux:menu.item keep-open wire:click="toggleColumn('{{ $columnKey }}')">
                                        {{ $this->isColumnVisible($columnKey) ? '✓ ' : '' }}{{ $columnLabel }}
                                    </flux:menu.item>
                                @endforeach
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </x-slot:bottomLeft>

                <x-slot:bottomRight>
                    <x-admin.tables.partials.bulk-actions>
                        <flux:button size="sm" wire:click="bulkCreateInvoice">
                            Rechnungen erstellen
                        </flux:button>
                        <flux:button size="sm" wire:click="bulkDownloadInvoice">
                            Rechnungen herunterladen
                        </flux:button>
                        <flux:button size="sm" wire:click="bulkSendInvoice">
                            Rechnungen senden
                        </flux:button>
                        <flux:button size="sm" wire:click="bulkSendInvoiceReminder">
                            Erinnerungen senden
                        </flux:button>
                    </x-admin.tables.partials.bulk-actions>
                </x-slot:bottomRight>
            </x-admin.tables.partials.toolbar-grid>
        </x-slot:toolbar>

        <flux:checkbox.group wire:model.live="checkboxValues">
            <flux:table class="min-w-max">
            <flux:table.columns>
                <flux:table.column>
                    <flux:field variant="inline">
                        <flux:checkbox.all />
                    </flux:field>
                </flux:table.column>
                @if ($this->isColumnVisible('don_id'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'don_id', 'label' => 'DON-ID'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('first_name'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'first_name', 'label' => 'Vorname'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('last_name'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'last_name', 'label' => 'Nachname'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('donations_count'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'donations_count', 'label' => 'Anzahl Spenden'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('invoice_total'))
                    <flux:table.column>Rechnungsbetrag</flux:table.column>
                @endif
                @if ($this->isColumnVisible('created_at'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'created_at', 'label' => 'Anmeldung'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('email'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'email', 'label' => 'E-Mail'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('phone_number'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'phone_number', 'label' => 'Telefon'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('country'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'country', 'label' => 'Land'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('address'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'address', 'label' => 'Adresse'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('zip_code'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'zip_code', 'label' => 'PLZ'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('city'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'city', 'label' => 'Ort'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('invoice_status'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'invoice_status', 'label' => 'Rechnung'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('invoice_sent_at'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'invoice_sent_at', 'label' => 'Rechnung gesendet am'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('invoice_reminder_sent_at'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'invoice_reminder_sent_at', 'label' => 'Erinnerung gesendet am'])</flux:table.column>
                @endif
                <flux:table.column class="sticky right-0 z-10 w-14 min-w-14 align-middle border-l border-zinc-200 bg-white text-center dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-center">
                        <flux:icon.cog-6-tooth class="size-4" />
                    </div>
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($donors as $donor)
                    @php($invoiceTotal = (float) ($donor->invoice_total ?? 0))
                    @php($rowClass = $loop->odd ? 'bg-zinc-50/60 dark:bg-zinc-800/40' : 'bg-white dark:bg-zinc-900')
                    <flux:table.row wire:key="donor-{{ $donor->id }}" class="{{ $rowClass }}">
                        <flux:table.cell>
                            <flux:field variant="inline">
                                <flux:checkbox value="{{ $donor->id }}" />
                            </flux:field>
                        </flux:table.cell>
                        @if ($this->isColumnVisible('don_id'))
                            <flux:table.cell>DON-{{ sprintf('25%04d', $donor->id) }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('first_name'))
                            <flux:table.cell>{{ $donor->first_name }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('last_name'))
                            <flux:table.cell>{{ $donor->last_name }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('donations_count'))
                            <flux:table.cell>{{ $donor->donations_count }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('invoice_total'))
                            <flux:table.cell>Fr. {{ number_format($invoiceTotal, 2, '.', "'") }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('created_at'))
                            <flux:table.cell>{{ \Illuminate\Support\Carbon::parse($donor->created_at)->format('d.m.Y') }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('email'))
                            <flux:table.cell>
                                <flux:tooltip content="{{ $donor->email }}">
                                    <span class="block max-w-52 truncate">{{ $donor->email }}</span>
                                </flux:tooltip>
                            </flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('phone_number'))
                            <flux:table.cell>{{ $donor->phone_number }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('country'))
                            <flux:table.cell>{{ $donor->country_of_residence }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('address'))
                            <flux:table.cell>
                                <flux:tooltip content="{{ $donor->address }}">
                                    <span class="block max-w-56 truncate">{{ $this->truncateText($donor->address, 44) }}</span>
                                </flux:tooltip>
                            </flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('zip_code'))
                            <flux:table.cell>{{ $donor->zip_code }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('city'))
                            <flux:table.cell>{{ $donor->city }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('invoice_status'))
                            <flux:table.cell>{{ $this->invoiceStatusLabel($donor) }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('invoice_sent_at'))
                            <flux:table.cell>{{ $donor->invoice_sent_at ? \Illuminate\Support\Carbon::parse($donor->invoice_sent_at)->format('d.m.Y H:i') : '-' }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('invoice_reminder_sent_at'))
                            <flux:table.cell>{{ $donor->invoice_reminder_sent_at ? \Illuminate\Support\Carbon::parse($donor->invoice_reminder_sent_at)->format('d.m.Y H:i') : '-' }}</flux:table.cell>
                        @endif
                        <flux:table.cell class="sticky right-0 z-10 w-14 min-w-14 align-middle border-l border-zinc-200 {{ $rowClass }} text-center dark:border-zinc-700">
                            @include('components.admin.tables.partials.donator-row-actions', ['row' => $donor])
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="99" class="text-center text-zinc-500">Keine Spender:innen gefunden.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
            </flux:table>
        </flux:checkbox.group>

        <x-slot:footer>
            <div class="flex items-center gap-2">
                <flux:text>Pro Seite</flux:text>
                <flux:select wire:model.live="perPage" class="w-24">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </flux:select>
            </div>

            <flux:pagination :paginator="$donors" />
        </x-slot:footer>
    </x-admin.datatable>
</div>
